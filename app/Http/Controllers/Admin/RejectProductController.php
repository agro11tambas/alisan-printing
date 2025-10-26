<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Products;
use App\Models\RejectProduct;
use App\Models\RejectProductHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class RejectProductController extends Controller
{
    public function getRejectProducts()
    {
        return view('erp.pages.adjustment-products.reject-products.reject-products');
    }

    public function dataRejectProducts(Request $request)
    {
        $query = RejectProduct::with('product')
            ->selectRaw('product_id, SUM(quantity) as total_reject')
            ->groupBy('product_id');

        if ($request->filled('product_name')) {
            $query->whereHas('product', fn($q) =>
            $q->where('name', 'like', "%{$request->product_name}%"));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('product_name', fn($row) => optional($row->product)->name ?? '-')
            ->addColumn('action', function ($row) {
                return view(
                    'erp.pages.adjustment-products.reject-products.partials.action-button',
                    compact('row')
                )->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function detailRejectProducts($id)
    {
        $product = Products::findOrFail($id);
        return view('erp.pages.adjustment-products.reject-products.detail-reject-products', compact('product'));
    }

    public function dataDetailRejectProducts(Request $request, $id)
    {
        $rejectRecords = RejectProduct::with(['orderProgress', 'user'])
            ->where('product_id', $id)
            ->orderBy('reject_date', 'desc');

        // 🔥 Tambahkan filter berdasarkan status (Pending / Completed / Eliminated)
        if ($request->filled('status')) {
            $rejectRecords->where('status', $request->status);
        }

        return DataTables::of($rejectRecords)
            ->addIndexColumn()
            ->addColumn('reject_date', fn($record) => $record->reject_date?->format('Y-m-d') ?? '-')
            ->addColumn('order_progress', fn($record) => $record->orderProgress?->code ?? '-')
            ->addColumn(
                'quantity',
                fn($record) =>
                '<span class="text-danger fw-bold">' . number_format($record->quantity) . '</span>'
            )
            ->addColumn('note', fn($record) => $record->note ?? '-')
            ->addColumn('status', function ($record) {
                return match ($record->status) {
                    'pending'    => '<span class="badge bg-soft-warning text-warning">Pending</span>',
                    'completed'  => '<span class="badge bg-soft-success text-success">Completed</span>',                
                    default      => '<span class="badge bg-soft-secondary text-muted">' . ucfirst($record->status) . '</span>',
                };
            })
            ->addColumn('user', fn($record) => $record->user?->name ?? '-')
            ->addColumn('action', function ($record) {
                return view(
                    'erp.pages.adjustment-products.reject-products.partials.action-button-detail',
                    compact('record')
                )->render();
            })
            ->rawColumns(['quantity', 'status', 'action'])
            ->make(true);
    }

    public function returnToWarehouse(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'inventory_warehouse_id' => 'required|exists:inventory_warehouses,id',
        ]);

        DB::beginTransaction();
        try {
            $reject = RejectProduct::with('product')->findOrFail($id);

            $qty = (int) $request->quantity;

            // Validasi stok cukup
            if ($reject->quantity < $qty) {
                return back()->with('error', 'Jumlah return melebihi stok reject yang tersedia!');
            }

            // Buat record di tabel Inventory
            $inventory = Inventory::create([
                'date'          => $request->date,
                'reject_product_id' => $reject->id, // tambahkan kolom ini di migration Inventory kalau belum ada
                'status'        => 'Stock In',
                'note'          => 'Return Reject Product to Warehouse',
            ]);

            // Buat record di tabel InventoryItem
            InventoryItem::create([
                'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                'inventory_id'   => $inventory->id,
                'product_id'     => $reject->product_id,
                'quantity'       => $qty,
                'stock_in'       => 0,
                'remaining_stock_in' => $qty,
                'stock_out'      => 0,
            ]);

            // Kurangi stok reject dan tambahkan returned_quantity
            $reject->decrement('quantity', $qty);
            $reject->increment('returned_quantity', $qty);

            if ($reject->quantity <= 0) {
                $reject->update(['status' => 'completed']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Reject product berhasil dikembalikan ke warehouse.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return reject product gagal: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengembalikan produk: ' . $e->getMessage());
        }
    }
}
