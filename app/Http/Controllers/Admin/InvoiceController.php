<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('erp.pages.invoices.index');
    }

    public function dataInvoice(Request $request)
    {
        $invoice = Invoice::with('termAndConditions')->latest();

        if ($request->filled('name')) {
            $invoice->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('bank_name')) {
            $invoice->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        if ($request->filled('account_number')) {
            $invoice->where('account_number', 'like', '%' . $request->account_number . '%');
        }

        return DataTables::of($invoice)
            ->addIndexColumn()
            ->addColumn('logo', function ($invoice) {
                if ($invoice->logo) {
                    $url = asset(str_replace('public/', '', $invoice->logo));
                    return '
                        <a href="' . $url . '" 
                            data-lightbox="invoice-logo-' . $invoice->id . '" 
                            data-title="' . e($invoice->name ?? 'Invoice Logo') . '" 
                            class="d-inline-block">
                            <img src="' . $url . '" width="80" height="60"
                                style="border-radius:8px;object-fit:cover;object-position:center;border:1px solid #ddd;">
                        </a>
                    ';
                } else {
                    return '<span class="text-muted">No Logo</span>';
                }
            })

            ->addColumn('bank_name', function ($invoice) {
                return $invoice->bank_name;
            })
            ->addColumn('account_number', function ($invoice) {
                return $invoice->account_number;
            })
            ->addColumn('name', function ($invoice) {
                return $invoice->name;
            })
            ->addColumn('address', function ($invoice) {
                return $invoice->address;
            })
            ->addColumn('terms_and_conditions', function ($invoice) {
                if ($invoice->termAndConditions->isEmpty()) {
                    return '-';
                }
                $list = '<ul>';
                foreach ($invoice->termAndConditions as $term) {
                    $list .= '<li>' . e($term->content) . '</li>';
                }
                $list .= '</ul>';
                return $list;
            })
            ->addColumn('action', function ($invoice) {
                return view('erp.pages.invoices.partials.action-button', compact('invoice'));
            })
            ->rawColumns(['action', 'terms_and_conditions', 'logo'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.invoices.create-invoice');
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
            'address' => 'required',
            'contents.*' => 'nullable|string',
        ]);

        $invoice = Invoice::create([
            'name' => $request->name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'address' => $request->address,
        ]);

        if ($request->has('contents')) {
            foreach ($request->contents as $content) {
                if (!empty($content)) {
                    $invoice->termAndConditions()->create(['content' => $content]);
                }
            }
        }

        if ($request->hasFile('logo')) {
            if ($invoice->logo && Storage::exists('public/' . $invoice->logo)) {
                Storage::delete('public/' . $invoice->logo);
            }

            $path = $request->file('logo')->store('invoice_logos', 'public');
            $invoice->logo = $path;
        }


        return redirect('/erp/invoices')->with('success', 'Invoice created successfully.');
    }

    public function edit($id)
    {
        $invoice = Invoice::with('termAndConditions')->findOrFail($id);
        return view('erp.pages.invoices.edit-invoice', compact('invoice'));
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::with('termAndConditions')->findOrFail($id);

        // Validasi data
        $request->validate([
            'logo'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name'           => 'required|string|max:255',
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'address'        => 'required|string|max:255',
            'contents'       => 'nullable|array',
            'contents.*'     => 'nullable|string|max:1000',
        ]);

        // Update data utama Invoice
        $invoice->update([
            'name'           => $request->name,
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'address'        => $request->address,
        ]);

        // Update Terms & Conditions
        if ($request->has('contents')) {
            // Hapus semua term lama
            $invoice->termAndConditions()->delete();

            // Simpan term baru
            foreach ($request->contents as $content) {
                if (!empty($content)) {
                    $invoice->termAndConditions()->create([
                        'content' => $content,
                    ]);
                }
            }
        } else {
            // Jika tidak ada data, hapus semua term
            $invoice->termAndConditions()->delete();
        }

        if ($request->hasFile('logo')) {
            // ✅ simpan ke folder yang benar (satu level di atas /public)
            $uploadPath = public_path('../invoice_logos');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // hapus logo lama
            if ($invoice->logo && file_exists($uploadPath . '/' . basename($invoice->logo))) {
                @unlink($uploadPath . '/' . basename($invoice->logo));
            }

            // upload file baru
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);

            // simpan path relatif agar bisa diakses dari web
            $invoice->logo = 'invoice_logos/' . $filename;
            $invoice->save();
        }

        return redirect('/erp/invoices')->with('success', 'Invoice berhasil diperbarui.');
    }

    public function delete($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Hapus semua terms & conditions terkait
        $invoice->termAndConditions()->delete();

        // Hapus invoice
        $invoice->delete();

        return redirect('/erp/invoices')->with('success', 'Invoice berhasil dihapus.');
    }
}
