<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrderItem;
use App\Models\Operator;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemHistory;
use App\Models\OrderProgress;
use App\Models\OrderProgressBatch;
use App\Models\OrderProgressHistory;
use App\Models\OrderProgressItem;
use App\Models\ProductionStock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\Facades\DataTables;

class HistoryProgressOrderController extends Controller
{
    public function getOrderHistory($id)
    {
        $progress = OrderProgress::with([
            'items.product',
            'items.histories',
            'items.orderItem'
        ])->findOrFail($id);

        return view('erp.pages.production.waiting-list.history-order', compact('progress'));
    }

    public function dataOrderHistory(Request $request, $id)
    {
        $batches = OrderProgressBatch::with(['user', 'histories.progressItem', 'histories.operators',])
            ->where('order_progress_id', $id)
            ->latest();

        $query = $batches;

        // Filter berdasarkan change_date di batch
        if ($request->filter) {
            $query->when(true, function ($q) use ($request) {
                switch ($request->filter) {
                    case 'today':
                        $q->whereDate('change_date', Carbon::today());
                        break;
                    case 'last_7_days':
                        $q->whereBetween('change_date', [Carbon::now()->subDays(7), Carbon::now()]);
                        break;
                    case 'this_month':
                        $q->whereMonth('change_date', Carbon::now()->month)
                            ->whereYear('change_date', Carbon::now()->year);
                        break;
                    case 'last_30_days':
                        $q->whereBetween('change_date', [Carbon::now()->subDays(30), Carbon::now()]);
                        break;
                    case 'year_to_date':
                        $q->whereBetween('change_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                        break;
                    case 'yearly':
                        $q->whereYear('change_date', Carbon::now()->year);
                        break;
                    case 'custom':
                        if ($request->filled('start_date') && $request->filled('end_date')) {
                            $q->whereBetween('change_date', [$request->start_date, $request->end_date]);
                        }
                        break;
                }
            });
        }

        $batches = $query->get();

        try {
            return DataTables::of($batches)
                ->addIndexColumn()
                ->addColumn('invoice_number', function ($batch) {
                    return $batch->orderProgress?->invoice_number ?? '-';
                })
                ->addColumn('date', function ($batch) {
                    return Carbon::parse($batch->date)->format('j M y');
                })
                ->addColumn('user_name', function ($batch) {
                    return $batch->user?->name ?? '-';
                })
                ->addColumn('notes', function ($batch) {
                    return $batch->note ?? '-';
                })
                ->addColumn('products', function ($batch) {
                    $items = OrderProgressHistory::with(['operators', 'progressItem.product'])
                        ->where('order_progress_batch_id', $batch->id)
                        ->get();

                    return view('erp.pages.production.waiting-list.partials.product-progress-histories', compact('items'))->render();
                })
                ->rawColumns(['products'])
                ->make(true);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addProgress($id)
    {
        $progress = OrderProgress::with(['items.product', 'order.customer'])
            ->findOrFail($id);

        $operators = Operator::where('active', 1)->orderBy('name')->get();

        return view('erp.pages.production.waiting-list.add-progress-order', compact('progress', 'operators'));
    }

    public function store(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'change_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.order_progress_item_id' => 'required|exists:order_progress_items,id',
            'items.*.change_quantity' => 'required|integer|min:0',
            'items.*.operator_id' => 'nullable|exists:operators,id',
            // 'production_warehouse_id' => 'required|exists:production_warehouses,id',
        ]);

        DB::beginTransaction();
        try {
            $orderProgress = OrderProgress::with('items.product')->findOrFail($id);

            // 1. Simpan batch
            $batch = OrderProgressBatch::create([
                'order_progress_id' => $orderProgress->id,
                'user_id' => $request->user()->id,
                'date' => $request->change_date,
                'note' => $request->note,
            ]);

            // 2. Loop tiap item progress
            foreach ($request->items as $itemData) {
                $progressItem = OrderProgressItem::findOrFail($itemData['order_progress_item_id']);

                $changeQty = min(
                    $itemData['change_quantity'],
                    $progressItem->quantity - $progressItem->completed_quantity
                );

                // Update completed quantity
                $progressItem->completed_quantity += $changeQty;
                $progressItem->save();

                // ✅ Pastikan operator_id benar-benar dikirim
                $operatorId = isset($itemData['operator_id']) && $itemData['operator_id'] !== ''
                    ? $itemData['operator_id']
                    : null;

                // 3. Simpan history ke order_progress_histories_2
                OrderProgressHistory::create([
                    'order_progress_item_id'   => $progressItem->id,
                    'order_progress_batch_id'  => $batch->id,
                    'change_quantity'          => $changeQty,
                    'operator_id'              => $operatorId, // ← ini sekarang pasti isi
                    'note'                     => $itemData['note'] ?? null,
                ]);

                // Update stok produk (decrement available, increment finished)
                if ($changeQty > 0 && $progressItem->product) {
                    $warehouseId =
                        $progressItem->production_warehouse_id
                        ?? $orderProgress->production_warehouse_id
                        ?? $request->production_warehouse_id
                        ?? 2;

                    // Pastikan baris production_stocks ada
                    $ps = ProductionStock::firstOrCreate(
                        ['product_id' => $progressItem->product_id, 'production_warehouse_id' => $warehouseId],
                        ['opening_stock' => 0, 'finished_product_stock' => 0, 'canceled_product_stock' => 0, 'available_quantity' => 0]
                    );

                    // Langsung decrement/increment (boleh minus)
                    $ps->decrement('available_quantity', $changeQty);
                    $ps->increment('finished_product_stock', $changeQty);

                    // ✅ Update ready_qty di DeliveryOrderItem
                    $deliveryItem = DeliveryOrderItem::where('order_progress_id', $progressItem->order_progress_id)
                        ->where('product_id', $progressItem->product_id)
                        ->first();

                    if ($deliveryItem) {
                        $deliveryItem->increment('ready_qty', $changeQty);
                    }
                }
            }

            DB::commit();

            return redirect('/erp/productions/waiting-list')->with('success', 'Progress berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateHistory(Request $request, $id)
    {
        try {
            // 🔹 Validasi input
            $validated = $request->validate([
                'change_quantity' => 'required|numeric|min:0',
                'note' => 'nullable|string|max:255',
            ]);

            // 🔹 Cari data yang akan diupdate
            $history = OrderProgressHistory::findOrFail($id);

            // 🔹 Update kolom
            $history->change_quantity = $validated['change_quantity'];
            $history->note = $validated['note'];
            $history->save();

            return redirect()->back()->with('success', 'History updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong while updating history: ' . $e->getMessage());
        }
    }
}
