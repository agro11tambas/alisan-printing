<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\Customers;
use App\Models\CustomerAddresses;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index()
    {
        return view('erp.pages.customers.index');
    }

    public function data(Request $request)
    {
        $customers = Customers::with('addresses')->latest();

        if ($request->filled('name')) {
            $customers->whereHas('addresses', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->name . '%');
            });
        }

        return DataTables::of($customers)
            ->addIndexColumn()
            ->addColumn('name', function ($customer) {
                return $customer->name;
            })
            ->addColumn('phone', function ($customer) {
                return '<strong>' . ($customer->phone ?? '-') . '</strong>';
            })
            ->addColumn('customer_deposit', function ($customer) {
                return '<strong> Rp. ' . number_format($customer->customer_deposit, 0, ',', '.') . '</strong>';
            })
            ->addColumn('action', function ($customer) {
                return view('erp.pages.customers.partials.action-button', compact('customer'))->render();
            })
            ->rawColumns(['action', 'addresses', 'phone', 'customer_deposit'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.customers.create-customer');
    }

    public function detail($id)
    {
        $customer = Customers::with('addresses')->findOrFail($id); // Eager load addresses

        return view('erp.pages.customers.detail-customer', compact('customer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'addresses' => 'array|required',
            'addresses.*.business_name' => 'nullable|string',
            'addresses.*.address' => 'required|string',
            'addresses.*.google_maps' => 'required|string',
        ]);

        try {
            $hasDuplicate = Customers::where('phone', $request->phone)->exists();

            if ($hasDuplicate) {
                DB::rollBack();
                $msg = 'Nomor telepon sudah terdaftar. Gunakan nomor lain.';
                return back()->with('error', $msg);
            }

            // Buat customer
            $customer = Customers::create([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            // Simpan setiap alamat
            foreach ($request->addresses as $addr) {
                $customer->addresses()->create([
                    'business_name' => $addr['business_name'] ?? null,
                    'address' => $addr['address'],
                    'google_maps' => $addr['google_maps'],
                ]);
            }

            return redirect('/erp/customers')->with('success', 'Customer berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan customer: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $customer = Customers::with('addresses')->findOrFail($id);

        return view('erp.pages.customers.edit-customer', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customers::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'addresses' => 'array|required',
            'addresses.*.business_name' => 'nullable|string',
            'addresses.*.address' => 'required|string',
            'addresses.*.google_maps' => 'required|string',
        ]);

        // Update data customer
        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        // Hapus semua alamat lama
        $customer->addresses()->delete();

        // Simpan ulang alamat baru
        foreach ($request->addresses as $addr) {
            $customer->addresses()->create([
                'business_name' => $addr['business_name'] ?? null,
                'address' => $addr['address'],
                'google_maps' => $addr['google_maps'],
            ]);
        }

        return redirect('/erp/customers')->with('success', 'Customer berhasil diperbarui.');
    }

    public function delete($id)
    {
        $customer = Customers::findOrFail($id);

        // Hapus semua alamat terkait
        $customer->addresses()->delete();

        // Hapus data customer
        $customer->delete();

        return redirect('/erp/customers')->with('success', 'Customer berhasil dihapus.');
    }
}
