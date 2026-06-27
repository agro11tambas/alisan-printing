<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $customerAccounts = CustomerAccount::latest();

        if ($request->filled('name')) {
            $customerAccounts->where('name', 'like', '%' . $request->name . '%');
        }

        return DataTables::of($customerAccounts)
            ->addIndexColumn()
            ->addColumn('name', function ($account) {
                return $account->name ?? '-';
            })
            ->addColumn('whatsapp_number', function ($account) {
                return '<strong>' . ($account->whatsapp_number ?? '-') . '</strong>';
            })
            ->addColumn('is_active', function ($account) {
                return $account->is_active
                    ? '<span class="badge bg-soft-success text-success">Active</span>'
                    : '<span class="badge bg-soft-danger text-danger">Inactive</span>';
            })
            ->addColumn('action', function ($account) {
                return view('erp.pages.customer-accounts.partials.action-button', compact('account'))->render();
            })
            ->rawColumns(['action', 'whatsapp_number', 'is_active'])
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
