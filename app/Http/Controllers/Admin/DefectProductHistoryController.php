<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefectProduct;
use App\Models\DefectProductHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefectProductHistoryController extends Controller
{
    public function returnToSupplier(Request $request, $id)
    {
        $request->validate([
            'date'     => 'required|date',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $defect = DefectProduct::with('product', 'supplier')->findOrFail($id);
            $qty = (int) $request->quantity;

            if ($defect->quantity < $qty) {
                return back()->with('error', 'Jumlah return melebihi stok defect yang tersedia!');
            }

            // Update defect product record
            $defect->decrement('quantity', $qty);
            $defect->increment('returned_quantity', $qty);

            if ($defect->quantity <= 0) {
                $defect->update(['status' => 'completed']);
            }

            // Simpan ke history
            DefectProductHistory::create([
                'defect_product_id' => $defect->id,
                'product_id'        => $defect->product_id,
                'supplier_id'       => $defect->supplier_id,
                'quantity'          => $qty,
                'action_type'       => 'return',
                'note'              => $request->note ?? 'Return defect product to supplier',
                'action_date'       => $request->date,
                'user_id'           => Auth::id(),
            ]);

            DB::commit();
            return back()->with('success', 'Defect product berhasil dikembalikan ke supplier.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return defect product gagal: ' . $e->getMessage());

            return back()->with('error', 'Gagal mengembalikan defect product: ' . $e->getMessage());
        }
    }

    public function eliminate(Request $request, $id)
    {
        $request->validate([
            'date'     => 'required|date',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $defect = DefectProduct::with('product', 'supplier')->findOrFail($id);
            $qty = (int) $request->quantity;

            if ($defect->quantity < $qty) {
                return back()->with('error', 'Jumlah eliminasi melebihi stok defect yang tersedia!');
            }

            // Update defect product record
            $defect->decrement('quantity', $qty);
            $defect->increment('eliminated_quantity', $qty);

            if ($defect->quantity <= 0) {
                $defect->update(['status' => 'completed']);
            }

            // Simpan ke history
            DefectProductHistory::create([
                'defect_product_id' => $defect->id,
                'product_id'        => $defect->product_id,
                'supplier_id'       => $defect->supplier_id,
                'quantity'          => $qty,
                'action_type'       => 'eliminate',
                'note'              => $request->note ?? 'Eliminate defect product',
                'action_date'       => $request->date,
                'user_id'           => Auth::id(),
            ]);

            DB::commit();
            return back()->with('success', 'Defect product berhasil dieliminasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Eliminate defect product gagal: ' . $e->getMessage());

            return back()->with('error', 'Gagal mengeliminasi defect product: ' . $e->getMessage());
        }
    }

    public function historyPage($id)
    {
        $defect = DefectProduct::with('product', 'supplier')->findOrFail($id);
        return view('erp.pages.adjustment-products.defect-products.history-detail-defect-product', compact('defect'));
    }

    public function dataHistory($id)
    {
        $query = DefectProductHistory::with('user')
            ->where('defect_product_id', $id)
            ->orderByDesc('action_date');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('action_date', fn($r) => $r->action_date ? $r->action_date->format('Y-m-d') : '-')
            ->addColumn('action_type', function ($r) {
                return $r->action_type === 'return'
                    ? '<span class="badge bg-soft-success text-success">Return</span>'
                    : '<span class="badge bg-soft-danger text-danger">Eliminate</span>';
            })
            ->addColumn('quantity', fn($r) => '<strong class="text-danger">' . number_format($r->quantity) . '</strong>')
            ->addColumn('note', fn($r) => $r->note ?? '-')
            ->addColumn('user', fn($r) => $r->user?->name ?? '-')
            ->rawColumns(['action_type', 'quantity'])
            ->make(true);
    }
}
