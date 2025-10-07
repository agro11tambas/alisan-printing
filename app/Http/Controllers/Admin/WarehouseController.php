<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseProduct;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    public function getWarehouse()
    {
        return view('erp.pages.warehouses.items');
    }

    public function dataWarehouse(Request $request)
    {
        $warehouse = Warehouse::with('product')->latest();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $warehouse->whereBetween('date_change', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('product_name')) {
            $warehouse->whereHas('product', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        return DataTables::of($warehouse)
            ->addIndexColumn()
            ->addColumn('product', function ($warehouse) {
                return $warehouse->product->name;
            })
            ->addColumn('stok_awal', function ($warehouse) {
                return $warehouse->stok_awal;
            })
            ->addColumn('barang_masuk', function ($warehouse) {
                return $warehouse->barang_masuk;
            })
            ->addColumn('barang_keluar', function ($warehouse) {
                return $warehouse->barang_keluar;
            })
            ->addColumn('stok_akhir', function ($warehouse) {
                return $warehouse->stok_akhir;
            })
            ->addColumn('date_change', function ($warehouse) {
                return $warehouse->date_change;
            })
            ->addColumn('note', function ($warehouse) {
                return $warehouse->note;
            })
            ->addColumn('action', function ($warehouse) {
                return view('erp.pages.warehouses.partials.action-button', compact('warehouse'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getReportItems()
    {
        return view('erp.pages.warehouses.report-items');
    }

    public function dataReportItems(Request $request)
    {
        $reportItems = PurchaseProduct::query();

        if ($request->filled('product_name')) {
            $reportItems->where('name', 'like', '%' . $request->product_name . '%');
        }

        return DataTables::of($reportItems)
            ->addIndexColumn()
            ->addColumn('name', function ($reportItem) {
                return $reportItem->name;
            })
            ->addColumn('stock', function ($reportItem) {
                return $reportItem->stock;
            })
            ->addColumn('avg_cost', function ($reportItem) {
                return $reportItem->avg_cost;
            })
            ->make(true);
    }

    public function create()
    {
        $products = PurchaseProduct::all();
        return view('erp.pages.warehouses.create-item', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product' => 'required',
            'product.*' => 'exists:purchase_products,id',
            'stock' => 'required|in:masuk,keluar',
            'barang_masuk' => 'nullable|numeric',
            'barang_keluar' => 'nullable|numeric',
            'date_change' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $productId = $request->product;
        // Ambil stok akhir terakhir dari database
        $lastWarehouse = Warehouse::where('purchase_product_id', $productId)
            ->latest('date_change')
            ->orderBy('id', 'desc')
            ->first();

        $stokAwal = $lastWarehouse ? $lastWarehouse->stok_akhir : 0;

        // Hitung barang masuk dan keluar
        $barangMasuk = $request->stock === 'masuk' ? ($request->barang_masuk ?? 0) : 0;
        $barangKeluar = $request->stock === 'keluar' ? ($request->barang_keluar ?? 0) : 0;

        // Hitung stok akhir
        $stokAkhir = $stokAwal + $barangMasuk - $barangKeluar;

        // Simpan ke database
        Warehouse::create([
            'purchase_product_id' => $productId,
            'stok_awal' => $stokAwal,
            'barang_masuk' => $barangMasuk,
            'barang_keluar' => $barangKeluar,
            'stok_akhir' => $stokAkhir,
            'date_change' => $request->date_change,
            'keterangan' => $request->note,
        ]);

        PurchaseProduct::where('id', $productId)->update([
            'stock' => $stokAkhir
        ]);

        return redirect('/erp/warehouses/items')->with('success', 'Data Warehouse Berhasil Ditambahkan');
    }

    public function delete($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return redirect('/erp/warehouses/items')->with('success', 'Data Warehouse Berhasil Dihapus');
    }

    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $products = PurchaseProduct::all();
        return view('erp.pages.warehouses.edit-item', compact('warehouse', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product' => 'required', // hanya 1 produk, tidak array
            'stock' => 'required|in:masuk,keluar',
            'barang_masuk' => 'nullable|numeric',
            'barang_keluar' => 'nullable|numeric',
            'date_change' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $warehouse = Warehouse::findOrFail($id);

        $productId = $request->product;

        // Ambil data stok terakhir sebelum data yang sedang di-update
        $lastWarehouseBefore = Warehouse::where('purchase_product_id', $productId)
            ->where('id', '<>', $warehouse->id)
            ->where('date_change', '<=', $request->date_change)
            ->latest('date_change')
            ->orderBy('id', 'desc')
            ->first();

        $stokAwal = $lastWarehouseBefore ? $lastWarehouseBefore->stok_akhir : 0;

        $barangMasuk = $request->stock === 'masuk' ? ($request->barang_masuk ?? 0) : 0;
        $barangKeluar = $request->stock === 'keluar' ? ($request->barang_keluar ?? 0) : 0;

        $stokAkhir = $stokAwal + $barangMasuk - $barangKeluar;

        // Update data warehouse
        $warehouse->update([
            'purchase_product_id' => $productId,
            'stok_awal' => $stokAwal,
            'barang_masuk' => $barangMasuk,
            'barang_keluar' => $barangKeluar,
            'stok_akhir' => $stokAkhir,
            'date_change' => $request->date_change,
            'keterangan' => $request->note,
        ]);

        PurchaseProduct::where('id', $productId)->update([
            'stock' => $stokAkhir
        ]);

        return redirect('/erp/warehouses/items')->with('success', 'Data Warehouse Berhasil Diperbarui');
    }
}
