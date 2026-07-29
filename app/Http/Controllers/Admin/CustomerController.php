<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customers;
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
    public function index()
    {
        return view('erp.pages.customers.index');
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $customers = Customers::query()->with('user')->orderBy('name');

        if ($user->role === 'Sales') {
            $customers->where('user_id', $user->id);
        }

        if ($request->filled('name')) {
            $keyword = $request->name;
            $customers->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhereHas('addresses', function ($query) use ($keyword) {
                      $query->where('business_name', 'like', '%' . $keyword . '%');
                  });
            });
        }

        return DataTables::of($customers)
            ->addIndexColumn()
            ->addColumn('name', function ($customer) {
                return $customer->name;
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
            ->rawColumns(['action', 'addresses', 'customer_deposit'])
            ->make(true);
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
        $customer = Customers::with(['addresses', 'accounts'])->findOrFail($id);

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

            foreach ($request->addresses as $addr) {
                $customer->addresses()->create([
                    'business_name' => $addr['business_name'] ?? null,
                    'address' => $addr['address'],
                    'google_maps' => $addr['google_maps'],
                ]);
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
            'addresses.*.business_name' => 'nullable|string',
            'addresses.*.address' => 'required|string',
            'addresses.*.google_maps' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $customer->update([
                'name' => $request->name,
            ]);

            $accountIds = $request->existing_account_ids ?? [];

            // Update account existing yang diedit di form
            foreach ($request->existing_accounts ?? [] as $accountId => $accountInput) {
                if (! in_array($accountId, $accountIds)) {
                    continue;
                }

                $phone = PhoneNumber::normalizeIndonesian($accountInput['whatsapp_number'] ?? null);

                if ($phone) {
                    $phoneUsed = CustomerAccount::findByEquivalentWhatsapp($phone, (int) $accountId);


                    if ($phoneUsed) {
                        DB::rollBack();

                        return back()
                            ->withInput()
                            ->with('error', 'Nomor ' . $phone . ' sudah terdaftar di account lain.');
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

            $customer->accounts()->sync($accountIds);

            $customer->addresses()->delete();

            foreach ($request->addresses as $addr) {
                $customer->addresses()->create([
                    'business_name' => $addr['business_name'] ?? null,
                    'address' => $addr['address'],
                    'google_maps' => $addr['google_maps'],
                ]);
            }

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
