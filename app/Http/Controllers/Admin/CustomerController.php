<?php

namespace App\Http\Controllers\Admin;

use App\Exports\InactiveCustomerExport;
use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\CustomerAddresses;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    // Customer dianggap tidak aktif jika order terakhirnya lebih lama dari batas ini.
    private const INACTIVE_ORDER_MONTHS = 2;

    public function index()
    {
        return view('erp.pages.customers.index', [
            'inactiveOrderMonths' => self::INACTIVE_ORDER_MONTHS,
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $inactiveCutoff = now()->subMonths(self::INACTIVE_ORDER_MONTHS);
        $customers = Customers::query()
            ->with('user')
            ->withMax('orders', 'order_date')
            ->orderBy('name');

        if ($request->boolean('inactive')) {
            $this->applyInactiveFilter($customers, $inactiveCutoff);
        }

        $this->applyNameFilter($customers, $request);

        return DataTables::of($customers)
            ->addIndexColumn()
            ->addColumn('name', function ($customer) use ($inactiveCutoff) {
                $name = e($customer->name);
                $lastOrderDate = $customer->orders_max_order_date;

                if ($lastOrderDate && Carbon::parse($lastOrderDate)->lt($inactiveCutoff)) {
                    $name .= ' <span class="badge bg-warning text-dark ms-1" title="Order terakhir: '
                        . Carbon::parse($lastOrderDate)->format('d M Y') . '">Tidak order '
                        . self::INACTIVE_ORDER_MONTHS . ' bulan</span>';
                } elseif (!$lastOrderDate) {
                    $name .= ' <span class="badge bg-secondary ms-1">Belum pernah order</span>';
                }

                return $name;
            })
            ->addColumn('last_order_date', function ($customer) {
                return $customer->orders_max_order_date
                    ? Carbon::parse($customer->orders_max_order_date)->format('d M Y')
                    : '-';
            })
            ->addColumn('customer_deposit', function ($customer) {
                return '<strong> Rp. ' . number_format($customer->customer_deposit, 0, ',', '.') . '</strong>';
            })
            ->addColumn('action', function ($customer) {
                return view('erp.pages.customers.partials.action-button', compact('customer'))->render();
            })
            ->addColumn('user', function ($customer) {
                return $customer->user?->name ?? '-';
            })
            ->rawColumns(['action', 'addresses', 'customer_deposit', 'name'])
            ->make(true);
    }

    /**
     * Export Excel tab "Tidak Order" beserta kontak customer.
     * Filter nama dari halaman ikut, sama seperti tabelnya.
     */
    public function exportInactive(Request $request)
    {
        $inactiveCutoff = now()->subMonths(self::INACTIVE_ORDER_MONTHS);
        $customers = Customers::query()
            ->withMax('orders', 'order_date')
            ->orderBy('name')
            ->orderBy('id');

        $this->applyInactiveFilter($customers, $inactiveCutoff);
        $this->applyNameFilter($customers, $request);

        $filename = 'customer-tidak-order-'.Carbon::now()->format('Ymd-His').'.xlsx';

        return (new InactiveCustomerExport($customers, $inactiveCutoff, self::INACTIVE_ORDER_MONTHS))
            ->download($filename);
    }

    // Tab "Tidak Order": tidak ada order sejak batas waktu, termasuk yang belum pernah order sama sekali.
    private function applyInactiveFilter(Builder $customers, Carbon $inactiveCutoff): void
    {
        $customers->whereDoesntHave('orders', function ($query) use ($inactiveCutoff) {
            $query->where('order_date', '>=', $inactiveCutoff);
        });
    }

    private function applyNameFilter(Builder $customers, Request $request): void
    {
        if (! $request->filled('name')) {
            return;
        }

        $keyword = $request->name;
        $customers->where(function ($q) use ($keyword) {
            $q->where('name', 'like', '%' . $keyword . '%')
              ->orWhereHas('addresses', function ($query) use ($keyword) {
                  $query->where('business_name', 'like', '%' . $keyword . '%');
              });
        });
    }

    // public function create()
    // {
    //     return view('erp.pages.customers.create-customer');
    // }

    public function create()
    {
        $customerAccounts = CustomerAccount::query()
            ->orderBy('id')
            ->get()
            ->unique(fn (CustomerAccount $account) => PhoneNumber::normalizeIndonesian($account->whatsapp_number) ?? 'account-'.$account->id)
            ->sortBy(fn (CustomerAccount $account) => mb_strtolower($account->name ?? ''))
            ->values();

        return view('erp.pages.customers.create-customer', compact('customerAccounts'));
    }

    public function detail($id)
    {
        $customer = Customers::with(['addresses', 'accounts.latestLoginLink'])->findOrFail($id);

        return view('erp.pages.customers.detail-customer', compact('customer'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string',
    //         'phone' => 'required|string',
    //         'addresses' => 'array|required',
    //         'addresses.*.business_name' => 'nullable|string',
    //         'addresses.*.address' => 'required|string',
    //         'addresses.*.google_maps' => 'required|string',
    //     ]);

    //     try {
    //         $hasDuplicate = Customers::where('phone', $request->phone)->exists();

    //         if ($hasDuplicate) {
    //             DB::rollBack();
    //             $msg = 'Nomor telepon sudah terdaftar. Gunakan nomor lain.';
    //             return back()->with('error', $msg);
    //         }

    //         // Buat customer
    //         $customer = Customers::create([
    //             'name' => $request->name,
    //             'phone' => $request->phone,
    //             'user_id' => Auth::id(),
    //         ]);

    //         // Simpan setiap alamat
    //         foreach ($request->addresses as $addr) {
    //             $customer->addresses()->create([
    //                 'business_name' => $addr['business_name'] ?? null,
    //                 'address' => $addr['address'],
    //                 'google_maps' => $addr['google_maps'],
    //             ]);
    //         }

    //         return redirect('/erp/customers')->with('success', 'Customer berhasil ditambahkan');
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Gagal menambahkan customer: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',

            'existing_account_ids' => 'nullable|array',
            'existing_account_ids.*' => 'exists:customer_accounts,id',

            'accounts' => 'nullable|array',
            'accounts.*.name' => 'nullable|string',
            'accounts.*.whatsapp_number' => 'nullable|string|distinct',

            'addresses' => 'required|array|min:1',
            'addresses.*.business_name' => 'nullable|string',
            'addresses.*.address' => 'required|string',
            'addresses.*.google_maps' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $customer = Customers::create([
                'name' => $request->name,
                'user_id' => Auth::id(),
            ]);

            // Attach account existing
            if ($request->filled('existing_account_ids')) {
                $customer->accounts()->syncWithoutDetaching($request->existing_account_ids);
            }

            // Create account baru
            foreach ($request->accounts ?? [] as $accountInput) {
                $phone = PhoneNumber::normalizeIndonesian($accountInput['whatsapp_number'] ?? null);

                if (! $phone) {
                    continue;
                }

                $existingAccount = CustomerAccount::findByEquivalentWhatsapp($phone);

                if ($existingAccount) {
                    $customer->accounts()->syncWithoutDetaching([$existingAccount->id]);
                    continue;
                }

                $account = CustomerAccount::create([
                    'name' => $accountInput['name'] ?? $request->name,
                    'whatsapp_number' => $phone,
                    'password' => bcrypt($phone),
                    'auth_provider' => 'phone',
                    'is_active' => true,
                ]);

                $customer->accounts()->attach($account->id);
            }

            // Alamat utama diatur oleh customer sendiri lewat website, jadi ERP hanya
            // menandai alamat pertama sebagai default awal supaya selalu ada satu.
            $isFirstAddress = true;

            foreach ($request->addresses as $addr) {
                $customer->addresses()->create([
                    'business_name' => $addr['business_name'] ?? null,
                    'address' => $addr['address'],
                    'google_maps' => $addr['google_maps'],
                    'is_default' => $isFirstAddress,
                ]);

                $isFirstAddress = false;
            }

            DB::commit();

            return redirect('/erp/customers')->with('success', 'Outlet berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan outlet: ' . $e->getMessage());
        }
    }

    // public function edit($id)
    // {
    //     $customer = Customers::with('addresses')->findOrFail($id);

    //     return view('erp.pages.customers.edit-customer', compact('customer'));
    // }

    public function edit($id)
    {
        $customer = Customers::with(['addresses', 'accounts'])->findOrFail($id);

        $customerAccounts = CustomerAccount::query()
            ->orderBy('id')
            ->get()
            ->unique(fn (CustomerAccount $account) => PhoneNumber::normalizeIndonesian($account->whatsapp_number) ?? 'account-'.$account->id)
            ->sortBy(fn (CustomerAccount $account) => mb_strtolower($account->name ?? ''))
            ->values();

        return view('erp.pages.customers.edit-customer', compact('customer', 'customerAccounts'));
    }

    // public function update(Request $request, $id)
    // {
    //     $customer = Customers::with('accounts')->findOrFail($id);

    //     $request->validate([
    //         'name' => 'required|string',

    //         'existing_account_ids' => 'nullable|array',
    //         'existing_account_ids.*' => 'exists:customer_accounts,id',

    //         'accounts' => 'nullable|array',
    //         'accounts.*.name' => 'nullable|string',
    //         'accounts.*.whatsapp_number' => 'nullable|string|distinct',

    //         'addresses' => 'required|array|min:1',
    //         'addresses.*.business_name' => 'nullable|string',
    //         'addresses.*.address' => 'required|string',
    //         'addresses.*.google_maps' => 'required|string',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $customer->update([
    //             'name' => $request->name,
    //         ]);

    //         $accountIds = $request->existing_account_ids ?? [];

    //         foreach ($request->accounts ?? [] as $accountInput) {
    //             $phone = PhoneNumber::normalizeIndonesian($accountInput['whatsapp_number'] ?? null);

    //             if (! $phone) {
    //                 continue;
    //             }

    //             $existingAccount = CustomerAccount::findByEquivalentWhatsapp($phone);

    //             if ($existingAccount) {
    //                 DB::rollBack();

    //                 return back()
    //                     ->withInput()
    //                     ->with('error', 'Nomor ' . $phone . ' sudah terdaftar. Silakan pilih dari Account Existing.');
    //             }

    //             $account = CustomerAccount::create([
    //                 'name' => $accountInput['name'] ?? $request->name,
    //                 'whatsapp_number' => $phone,
    //                 'password' => bcrypt($phone),
    //                 'auth_provider' => 'phone',
    //                 'is_active' => true,
    //             ]);

    //             $accountIds[] = $account->id;
    //         }

    //         // Replace akses account outlet ini
    //         $customer->accounts()->sync($accountIds);

    //         // Replace alamat
    //         $customer->addresses()->delete();

    //         foreach ($request->addresses as $addr) {
    //             $customer->addresses()->create([
    //                 'business_name' => $addr['business_name'] ?? null,
    //                 'address' => $addr['address'],
    //                 'google_maps' => $addr['google_maps'],
    //             ]);
    //         }

    //         DB::commit();

    //         return redirect('/erp/customers')->with('success', 'Outlet berhasil diperbarui.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return back()
    //             ->withInput()
    //             ->with('error', 'Gagal memperbarui outlet: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $customer = Customers::with('accounts')->findOrFail($id);

        $request->validate([
            'name' => 'required|string',

            'existing_account_ids' => 'nullable|array',
            'existing_account_ids.*' => 'exists:customer_accounts,id',

            'existing_accounts' => 'nullable|array',
            'existing_accounts.*.name' => 'nullable|string',
            'existing_accounts.*.whatsapp_number' => 'nullable|string',

            'accounts' => 'nullable|array',
            'accounts.*.name' => 'nullable|string',
            'accounts.*.whatsapp_number' => 'nullable|string|distinct',

            'addresses' => 'required|array|min:1',
            'addresses.*.id' => 'nullable|integer',
            'addresses.*.business_name' => 'nullable|string',
            'addresses.*.address' => 'required|string',
            'addresses.*.google_maps' => 'required|string',
        ]);

        // Alamat utama ditentukan customer lewat website, jadi pilihannya harus tetap
        // bertahan walaupun alamat dirapikan ulang dari ERP.
        $currentDefaultAddressId = $customer->addresses()
            ->where('is_default', true)
            ->value('id');

        DB::beginTransaction();

        try {
            $customer->update([
                'name' => $request->name,
            ]);

            $accountIds = array_map('intval', $request->existing_account_ids ?? []);

            // Update account existing yang diedit di form
            foreach ($request->existing_accounts ?? [] as $accountId => $accountInput) {
                $accountId = (int) $accountId;

                if (! in_array($accountId, $accountIds, true)) {
                    continue;
                }

                $phone = PhoneNumber::normalizeIndonesian($accountInput['whatsapp_number'] ?? null);

                if ($phone) {
                    $phoneUsed = CustomerAccount::query()
                        ->whereIn('whatsapp_number', PhoneNumber::equivalentIndonesianFormats($phone))
                        ->orderByRaw('CASE WHEN whatsapp_number = ? THEN 0 ELSE 1 END', [$phone])
                        ->orderBy('id')
                        ->first();

                    if ($phoneUsed && $phoneUsed->id !== $accountId) {
                        $accountIds = array_map(
                            fn (int $selectedAccountId) => $selectedAccountId === $accountId
                                ? $phoneUsed->id
                                : $selectedAccountId,
                            $accountIds
                        );

                        continue;
                    }
                }

                CustomerAccount::where('id', $accountId)->update([
                    'name' => $accountInput['name'] ?? $request->name,
                    'whatsapp_number' => $phone,
                ]);
            }

            // Create account baru
            foreach ($request->accounts ?? [] as $accountInput) {
                $phone = PhoneNumber::normalizeIndonesian($accountInput['whatsapp_number'] ?? null);

                if (! $phone) {
                    continue;
                }

                $existingAccount = CustomerAccount::findByEquivalentWhatsapp($phone);

                if ($existingAccount) {
                    $accountIds[] = $existingAccount->id;
                    continue;
                }

                $account = CustomerAccount::create([
                    'name' => $accountInput['name'] ?? $request->name,
                    'whatsapp_number' => $phone,
                    'password' => bcrypt($phone),
                    'auth_provider' => 'phone',
                    'is_active' => true,
                ]);

                $accountIds[] = $account->id;
            }

            $customer->accounts()->sync(array_values(array_unique($accountIds)));

            $customer->addresses()->delete();

            $defaultAddress = null;
            $firstAddress = null;

            foreach ($request->addresses as $addr) {
                $address = $customer->addresses()->create([
                    'business_name' => $addr['business_name'] ?? null,
                    'address' => $addr['address'],
                    'google_maps' => $addr['google_maps'],
                    'is_default' => false,
                ]);

                $firstAddress ??= $address;

                if ($currentDefaultAddressId && (int) ($addr['id'] ?? 0) === (int) $currentDefaultAddressId) {
                    $defaultAddress = $address;
                }
            }

            // Kalau alamat utama pilihan customer sudah dihapus dari ERP, jatuh ke alamat pertama
            // supaya customer tetap punya satu alamat utama.
            $defaultAddress ??= $firstAddress;
            $defaultAddress?->update(['is_default' => true]);

            DB::commit();

            return redirect('/erp/customers')->with('success', 'Outlet berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui outlet: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $customer = Customers::findOrFail($id);

        // Hapus semua alamat terkait
        $customer->addresses()->delete();

        // Ubah phone menjadi nomor random sebelum soft delete
        $customer->phone = 'deleted_' . mt_rand(1000000000, 9999999999);
        $customer->save();

        // Soft delete customer
        $customer->delete();

        return redirect('/erp/customers')->with('success', 'Customer berhasil dihapus.');
    }

    public function updateDeposit(Request $request, Customers $customer)
    {
        $request->validate([
            'deposit_type'   => 'required|in:add,subtract',
            'deposit_amount' => 'required|numeric|min:1',
        ]);

        $amount = $request->deposit_amount;

        if ($request->deposit_type === 'add') {
            $customer->increment('customer_deposit', $amount);
        } else {
            if ($amount > $customer->customer_deposit) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['deposit_amount' => ['Amount melebihi saldo deposit.']],
                ], 422);
            }
            $customer->decrement('customer_deposit', $amount);
        }

        return response()->json(['success' => true, 'message' => 'Deposit berhasil diupdate.']);
    }
}
