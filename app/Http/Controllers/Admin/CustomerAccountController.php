<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\CustomerPasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;


class CustomerAccountController extends Controller
{
    public function index()
    {
        return view('erp.pages.customer-accounts.index');
    }

    public function data(Request $request)
    {
        $customerAccounts = CustomerAccount::with(['passwordResetToken', 'customers', 'customer'])->latest();

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
            ->addColumn('is_active', function ($account) {
                return $account->is_active
                    ? '<span class="badge bg-soft-success text-success">Active</span>'
                    : '<span class="badge bg-soft-danger text-danger">Inactive</span>';
            })
            ->addColumn('password_reset_status', function ($account) {
                return match ($account->password_reset_status) {
                    'pending' => '<span class="badge bg-soft-warning text-warning">Menunggu Reset</span>',
                    'completed' => '<span class="badge bg-soft-success text-success">Sudah Reset</span>',
                    'expired' => '<span class="badge bg-soft-danger text-danger">Kedaluwarsa</span>',
                    default => '<span class="badge bg-soft-secondary text-secondary">Belum Dibuat</span>',
                };
            })
            ->addColumn('action', function ($account) {
                return view('erp.pages.customer-accounts.partials.action-button', compact('account'))->render();
            })
            ->rawColumns(['action', 'whatsapp_number', 'is_active', 'password_reset_status'])
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
            'whatsapp_number' => 'required|string|unique:customer_accounts,whatsapp_number',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            CustomerAccount::create([
                'name' => $request->name,
                'whatsapp_number' => preg_replace('/\D/', '', $request->whatsapp_number),
                'password' => bcrypt(preg_replace('/\D/', '', $request->whatsapp_number)),
                'auth_provider' => 'phone',
                'is_active' => $request->boolean('is_active'),
            ]);

            DB::commit();

            return redirect('/erp/customer-accounts')
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

    public function generatePasswordResetLink($id)
    {
        $customerAccount = CustomerAccount::findOrFail($id);

        if (! $customerAccount->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Link reset password hanya dapat dibuat untuk akun yang aktif.',
            ], 422);
        }

        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(30);

        CustomerPasswordResetToken::updateOrCreate(
            ['customer_account_id' => $customerAccount->id],
            [
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
                'used_at' => null,
            ]
        );

        $websiteUrl = rtrim(config('app.frontend_website_url'), '/');
        $resetUrl = $websiteUrl.'/reset-password?'.http_build_query(['token' => $plainToken]);

        return response()->json([
            'success' => true,
            'message' => 'Link reset password berhasil dibuat dan berlaku selama 30 menit.',
            'data' => [
                'reset_url' => $resetUrl,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $customerAccount = CustomerAccount::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'whatsapp_number' => [
                'required',
                'string',
                Rule::unique('customer_accounts', 'whatsapp_number')->ignore($customerAccount->id),
            ],
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $phone = preg_replace('/\D/', '', $request->whatsapp_number);

            $customerAccount->update([
                'name' => $request->name,
                'whatsapp_number' => $phone,
                'auth_provider' => $customerAccount->auth_provider ?? 'phone',
                'is_active' => $request->boolean('is_active'),
            ]);

            DB::commit();

            return redirect('/erp/customer-accounts')
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

        return redirect('/erp/customer-accounts')
            ->with('success', 'Customer account berhasil dihapus.');
    }
}
