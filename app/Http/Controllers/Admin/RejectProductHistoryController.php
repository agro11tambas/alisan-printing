<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectProduct;
use App\Models\RejectProductHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RejectProductHistoryController extends Controller
{
    public function rejectHistoryPage($id)
    {
        $rejectProduct = RejectProduct::with('product')->findOrFail($id);

        return view('erp.pages.adjustment-products.reject-products.history-reject-product', compact('rejectProduct'));
    }

    public function dataRejectHistory($id)
    {
        $histories = RejectProductHistory::with(['warehouse', 'user'])
            ->where('reject_product_id', $id)
            ->orderBy('date', 'desc');

        return DataTables::of($histories)
            ->addIndexColumn()
            ->addColumn('date', fn($h) => $h->date?->format('Y-m-d') ?? '-')
            ->addColumn('warehouse', fn($h) => $h->warehouse?->name ?? '-')
            ->addColumn('quantity', fn($h) => '<span class="fw-bold text-danger">' . number_format($h->quantity) . '</span>')
            ->addColumn('status', function ($h) {
                return match ($h->status) {
                    'returned'  => '<span class="badge bg-soft-success text-success">Returned</span>',
                    'eliminated' => '<span class="badge bg-soft-danger text-danger">Eliminated</span>',
                    default     => '<span class="badge bg-soft-secondary text-muted">Pending</span>',
                };
            })
            ->addColumn('user', fn($h) => $h->user?->name ?? '-')
            ->addColumn('note', fn($h) => $h->note ?? '-')
            ->rawColumns(['quantity', 'status'])
            ->make(true);
    }

    public function returnRejectToWarehouse(Request $request, $id)
    {
        $request->validate([
            'inventory_warehouse_id' => 'required|exists:inventory_warehouses,id',
            'quantity'               => 'required|integer|min:1',
            'note'                   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $reject = RejectProduct::findOrFail($id);

            RejectProductHistory::create([
                'reject_product_id'      => $reject->id,
                'product_id'             => $reject->product_id,
                'inventory_warehouse_id' => $request->inventory_warehouse_id,
                'quantity'               => $request->quantity,
                'date'                   => now(),
                'status'                 => 'completed',
                'note'                   => $request->note,
                'user_id'                => Auth::id(),
            ]);

            $reject->increment('returned_quantity', $request->quantity);
            if ($reject->returned_quantity >= $reject->quantity) {
                $reject->update(['status' => 'completed']);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Reject product successfully returned to warehouse.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }
}
