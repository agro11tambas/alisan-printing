<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\CustomerLoginLink;
use App\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\PhoneNumber;
use Yajra\DataTables\Facades\DataTables;


class CustomerAccountController extends Controller
{
    public function index()
    {
        return redirect('/erp/customers?tab=accounts');
    }

    public function data(Request $request)
    {
        $customerAccounts = CustomerAccount::with(['latestLoginLink','customers', 'customer'])
            ->orderBy('name');

        if ($request->filled('name')) {
            $keyword = trim($request->name);

            $customerAccounts->where(function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%')
                    ->orWhereHas('customers', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        return DataTables::of($customerAccounts)
            ->addIndexColumn()
            ->addColumn('name', function ($account) {
                return $account->name ?? '-';
            })
            ->addColumn('account_customer_name', function ($account) {
                $customerNames = $account->customers
                    ->pluck('name')
                    ->filter()
                    ->unique();

                if ($customerNames->isEmpty() && $account->customer?->name) {
                    $customerNames->push($account->customer->name);
                }

                return ($account->name ?? '-') . ' - ' . ($customerNames->join(', ') ?: '-');
            })
            ->addColumn('whatsapp_number', function ($account) {
                return '<strong>' . ($account->whatsapp_number ?? '-') . '</strong>';
            })
            ->addColumn('login_link_status', function ($account) {
                $link = $account->latestLoginLink;

                if (! $link) {
                    return '<span class="badge bg-soft-secondary text-secondary">Belum Dibuat</span>';
                }

                $status = $link->status();
                $badge = match ($status) {
                    'active' => '<span class="badge bg-soft-success text-success">Aktif</span>',
                    'used' => '<span class="badge bg-soft-info text-info">Sudah Dipakai</span>',
                    'expired' => '<span class="badge bg-soft-danger text-danger">Kedaluwarsa</span>',
                    'revoked' => '<span class="badge bg-soft-warning text-warning">Dicabut</span>',
                };

                $details = [$link->single_use ? 'Sekali pakai' : 'Berkali-kali'];

                if ($status === 'active') {
                    $details[] = $link->expires_at
                        ? 's/d '.$link->expires_at->format('d M Y H:i')
                        : 'tanpa batas waktu';
                }

                if ($link->use_count > 0) {
                    $details[] = 'dipakai '.$link->use_count.'x';
                }

                return $badge.'<div class="fs-11 text-muted mt-1">'.e(implode(' · ', $details)).'</div>';
            })
            ->addColumn('action', function ($account) {
                return view('erp.pages.customer-accounts.partials.action-button', compact('account'))->render();
            })
            ->rawColumns(['action', 'whatsapp_number', 'login_link_status'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.customer-accounts.create-customer-account');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $phone = PhoneNumber::normalizeIndonesian($request->whatsapp_number);

        if (CustomerAccount::findByEquivalentWhatsapp($phone)) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Nomor WhatsApp sudah terdaftar sebagai Customer Account.'],
            ]);
        }

        DB::beginTransaction();

        try {
            CustomerAccount::create([
                'name' => $request->name,
                'whatsapp_number' => $phone,
                'password' => bcrypt($phone),
                'auth_provider' => 'phone',
                'is_active' => $request->boolean('is_active'),
            ]);

            DB::commit();

            return redirect('/erp/customers?tab=accounts')
                ->with('success', 'Customer account berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan customer account: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $customerAccount = CustomerAccount::findOrFail($id);

        return view('erp.pages.customer-accounts.edit-customer-account', compact('customerAccount'));
    }

    public function generateLoginLink(Request $request, $id)
    {
        return $this->issueLoginLink($request, CustomerAccount::findOrFail($id));
    }

    /**
     * Link login untuk customer yang belum punya akun. Akunnya dibuat dari nama
     * dan nomor HP customer (sama seperti CustomerController::store()), jadi
     * customer langsung masuk lewat link tanpa perlu register di website.
     */
    public function generateLoginLinkForCustomer(Request $request, $id)
    {
        $customer = Customers::with('accounts')->findOrFail($id);

        if ($customer->accounts->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ini sudah punya akun. Buat link login dari akun tersebut.',
            ], 422);
        }

        $phone = PhoneNumber::normalizeIndonesian($customer->phone);

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer belum punya nomor WhatsApp. Lengkapi nomornya dulu sebelum membuat link login.',
            ], 422);
        }

        $customerAccount = DB::transaction(function () use ($customer, $phone) {
            // Nomor yang sudah dipakai akun lain ditautkan, bukan dibuat ulang.
            $account = CustomerAccount::findByEquivalentWhatsapp($phone) ?? CustomerAccount::create([
                'name' => $customer->name,
                'whatsapp_number' => $phone,
                'password' => bcrypt($phone),
                'auth_provider' => 'phone',
                'is_active' => true,
            ]);

            $customer->accounts()->syncWithoutDetaching([$account->id]);

            return $account;
        });

        return $this->issueLoginLink($request, $customerAccount);
    }

    private function issueLoginLink(Request $request, CustomerAccount $customerAccount)
    {
        if (! $customerAccount->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Link login hanya dapat dibuat untuk akun yang aktif.',
            ], 422);
        }

        // expires_in_hours yang kosong berarti link tidak pernah kedaluwarsa —
        // link dimatikan lewat tombol cabut, bukan lewat umur.
        $validated = $request->validate([
            'expires_in_hours' => 'nullable|integer|min:1',
            'single_use' => 'nullable|boolean',
        ]);

        $expiresInHours = isset($validated['expires_in_hours']) && $validated['expires_in_hours'] !== null
            ? (int) $validated['expires_in_hours']
            : null;
        $singleUse = $request->boolean('single_use');

        $code = CustomerLoginLink::generateCode();
        $expiresAt = $expiresInHours ? now()->addHours($expiresInHours) : null;

        // Satu link aktif per customer. Link yang masih bisa dipakai harus
        // dicabut admin dulu lewat Cabut Link Login, supaya link yang sudah
        // dikirim ke customer tidak mati diam-diam karena salah klik. Link yang
        // sudah kedaluwarsa/terpakai boleh langsung diganti dan dicabut di sini.
        $revokedCount = DB::transaction(function () use ($customerAccount, $code, $singleUse, $expiresAt) {
            $hasUsableLink = CustomerLoginLink::query()
                ->where('customer_account_id', $customerAccount->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get()
                ->contains(fn (CustomerLoginLink $link) => $link->isUsable());

            if ($hasUsableLink) {
                return null;
            }

            $revoked = CustomerLoginLink::query()
                ->where('customer_account_id', $customerAccount->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'revoked_by' => Auth::id(),
                ]);

            CustomerLoginLink::create([
                'customer_account_id' => $customerAccount->id,
                'code_hash' => CustomerLoginLink::hashCode($code),
                'code_encrypted' => $code,
                'single_use' => $singleUse,
                'expires_at' => $expiresAt,
                'created_by' => Auth::id(),
            ]);

            return $revoked;
        });

        if ($revokedCount === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini masih punya link login aktif. Cabut link login lama dulu sebelum membuat yang baru.',
            ], 422);
        }

        $loginUrl = CustomerLoginLink::urlForCode($code);

        $message = $expiresAt
            ? 'Link login berhasil dibuat dan berlaku selama '.$expiresInHours.' jam.'
            : 'Link login berhasil dibuat dan berlaku sampai dicabut.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'login_url' => $loginUrl,
                'expires_at' => $expiresAt?->toIso8601String(),
                'single_use' => $singleUse,
                'revoked_previous' => $revokedCount,
            ],
        ]);
    }

    public function revokeLoginLink($id)
    {
        $customerAccount = CustomerAccount::findOrFail($id);

        $revokedCount = CustomerLoginLink::query()
            ->where('customer_account_id', $customerAccount->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_by' => Auth::id(),
            ]);

        if ($revokedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak punya link login aktif.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link login berhasil dicabut. Link yang sudah terlanjur dikirim tidak bisa dipakai lagi.',
            'data' => [
                'revoked' => $revokedCount,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $customerAccount = CustomerAccount::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',

            'is_active' => 'nullable|boolean',
        ]);

        $phone = PhoneNumber::normalizeIndonesian($request->whatsapp_number);
        $currentPhone = PhoneNumber::normalizeIndonesian($customerAccount->whatsapp_number);
        $phoneIsUnchanged = $phone === $currentPhone;

        $conflictingAccount = CustomerAccount::findByEquivalentWhatsapp($phone, (int) $customerAccount->id);

        // Nomor yang tidak diubah tidak boleh memblokir perubahan data lain (misal ganti nama),
        // walaupun ada account lama yang menyimpan nomor setara dengan format berbeda.
        if ($conflictingAccount && ! $phoneIsUnchanged) {
            throw ValidationException::withMessages([
                'whatsapp_number' => ['Nomor WhatsApp sudah digunakan oleh Customer Account lain: ' . $conflictingAccount->name . '.'],
            ]);
        }

        // Kalau nomornya tidak berubah tapi ada account kembar, pertahankan format lamanya
        // supaya normalisasi tidak menabrak unique index milik account kembar tersebut.
        $phoneToSave = $conflictingAccount && $phoneIsUnchanged
            ? $customerAccount->whatsapp_number
            : $phone;

        DB::beginTransaction();

        try {
            $customerAccount->update([
                'name' => $request->name,
                'whatsapp_number' => $phoneToSave,
                'auth_provider' => $customerAccount->auth_provider ?? 'phone',
                'is_active' => $request->boolean('is_active'),
            ]);

            DB::commit();

            return redirect('/erp/customers?tab=accounts')
                ->with('success', 'Customer account berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui customer account: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $account = CustomerAccount::findOrFail($id);

        // Lepas relasi ke customer/outlet dulu
        $account->customers()->detach();

        // Ubah nomor jadi random supaya unique tidak bentrok saat create lagi
        $account->whatsapp_number = 'deleted_' . mt_rand(1000000000, 9999999999);
        $account->email = $account->email
            ? 'deleted_' . mt_rand(1000000000, 9999999999) . '_' . $account->email
            : null;

        $account->is_active = false;
        $account->save();

        // Soft delete account
        $account->delete();

        return redirect('/erp/customers?tab=accounts')
            ->with('success', 'Customer account berhasil dihapus.');
    }
}
