<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefectProduct;
use App\Models\DefectProductHistory;
use App\Models\DeliveryOrderItem;
use App\Models\Operator;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemHistory;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressBatch;
use App\Models\OrderProgressHistory;
use App\Models\OrderProgressItem;
use App\Models\ProductionStock;
use App\Models\RejectProduct;
use App\Models\RejectProductHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\Facades\DataTables;

class HistoryProgressOrderController extends Controller
{
    public function getHistory($id)
    {
        $progress = OrderProgress::with(['order.customer'])->findOrFail($id);
        return view('erp.pages.production.waiting-list.assign-list', compact('progress'));
    }

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

    // public function addProgress($id)
    // {
    //     $progress = OrderProgress::with(['items.product', 'order.customer'])
    //         ->findOrFail($id);

    //     $operators = Operator::where('active', 1)->orderBy('name')->get();

    //     return view('erp.pages.production.waiting-list.add-progress-order', compact('progress', 'operators'));
    // }

    public function create($batch_id)
    {
        $batch = OrderProgressAssignBatch::with([
            'assigns.progressItem.product',
            'assigns.operator',
            'orderProgress.order.customer'
        ])->findOrFail($batch_id);

        return view('erp.pages.production.assign-list.add-progress-order', compact('batch'));
    }

    // public function store(Request $request, $id)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'change_date' => 'required|date',
    //         'note' => 'nullable|string',
    //         'items' => 'required|array',
    //         'items.*.order_progress_item_id' => 'required|exists:order_progress_items,id',
    //         'items.*.change_quantity' => 'required|integer|min:0',
    //         'items.*.operator_id' => 'nullable|exists:operators,id',
    //         // 'production_warehouse_id' => 'required|exists:production_warehouses,id',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $orderProgress = OrderProgress::with('items.product')->findOrFail($id);

    //         // 1. Simpan batch
    //         $batch = OrderProgressBatch::create([
    //             'order_progress_id' => $orderProgress->id,
    //             'user_id' => $request->user()->id,
    //             'date' => $request->change_date,
    //             'note' => $request->note,
    //         ]);

    //         // 2. Loop tiap item progress
    //         foreach ($request->items as $itemData) {
    //             $progressItem = OrderProgressItem::findOrFail($itemData['order_progress_item_id']);

    //             $changeQty = min(
    //                 $itemData['change_quantity'],
    //                 $progressItem->quantity - $progressItem->completed_quantity
    //             );

    //             // Update completed quantity
    //             $progressItem->completed_quantity += $changeQty;
    //             $progressItem->save();

    //             // ✅ Pastikan operator_id benar-benar dikirim
    //             $operatorId = isset($itemData['operator_id']) && $itemData['operator_id'] !== ''
    //                 ? $itemData['operator_id']
    //                 : null;

    //             // 3. Simpan history ke order_progress_histories_2
    //             OrderProgressHistory::create([
    //                 'order_progress_item_id'   => $progressItem->id,
    //                 'order_progress_batch_id'  => $batch->id,
    //                 'change_quantity'          => $changeQty,
    //                 'operator_id'              => $operatorId, // ← ini sekarang pasti isi
    //                 'note'                     => $itemData['note'] ?? null,
    //             ]);

    //             // Update stok produk (decrement available, increment finished)
    //             if ($changeQty > 0 && $progressItem->product) {
    //                 $warehouseId =
    //                     $progressItem->production_warehouse_id
    //                     ?? $orderProgress->production_warehouse_id
    //                     ?? $request->production_warehouse_id
    //                     ?? 2;

    //                 // Pastikan baris production_stocks ada
    //                 $ps = ProductionStock::firstOrCreate(
    //                     ['product_id' => $progressItem->product_id, 'production_warehouse_id' => $warehouseId],
    //                     ['opening_stock' => 0, 'finished_product_stock' => 0, 'canceled_product_stock' => 0, 'available_quantity' => 0]
    //                 );

    //                 // Langsung decrement/increment (boleh minus)
    //                 $ps->decrement('available_quantity', $changeQty);
    //                 $ps->increment('finished_product_stock', $changeQty);

    //                 // ✅ Update ready_qty di DeliveryOrderItem
    //                 // $deliveryItem = DeliveryOrderItem::where('order_progress_id', $progressItem->order_progress_id)
    //                 //     ->where('product_id', $progressItem->product_id)
    //                 //     ->first();

    //                 // if ($deliveryItem) {
    //                 //     $deliveryItem->increment('ready_qty', $changeQty);
    //                 // }

    //                 $deliveryItem = DeliveryOrderItem::where('order_progress_item_id', $progressItem->id)->first();

    //                 if ($deliveryItem) {
    //                     $deliveryItem->increment('ready_qty', $changeQty);
    //                 }
    //             }
    //         }

    //         DB::commit();

    //         return redirect('/erp/productions/waiting-list')->with('success', 'Progress berhasil ditambahkan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request, $batch_id)
    {
        $request->validate([
            'progress_date' => 'required|date',
            'note'          => 'nullable|string',
            'items'         => 'required|array',
            'items.*.assign_id'         => 'required|exists:order_progress_assigns,id',
            'items.*.completed_quantity' => 'required|integer|min:0',
            'items.*.reject_quantity'     => 'nullable|integer|min:0',
            'items.*.defect_quantity'    => 'nullable|integer|min:0',
            'items.*.note'               => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $assignBatch = OrderProgressAssignBatch::with(['assigns.progressItem.product', 'orderProgress'])
                ->findOrFail($batch_id);

            // 🔹 Buat OrderProgressBatch (utama)
            $mainBatch = OrderProgressBatch::create([
                'order_progress_id' => $assignBatch->order_progress_id,
                'user_id'           => Auth::id(),
                'date'              => $request->progress_date,
                'note'              => $request->note,
            ]);

            // 🔹 Validasi jumlah progress harus sama dengan assigned qty
            foreach ($request->items as $data) {
                $assign = OrderProgressAssign::findOrFail($data['assign_id']);

                $completed = (int) ($data['completed_quantity'] ?? 0);
                $reject    = (int) ($data['reject_quantity'] ?? 0);
                $defect    = (int) ($data['defect_quantity'] ?? 0);

                $totalInput = $completed + $reject + $defect;

                if ($totalInput !== (int) $assign->assigned_quantity) {
                    DB::rollBack(); 
                    return back()
                        ->with('error', "Total progress untuk produk {$assign->progressItem->product->name} harus sama dengan Assigned Qty (" . number_format($assign->assigned_quantity, 0, ',', '.') . ").");
                }
            }

            foreach ($request->items as $data) {
                $assign = OrderProgressAssign::with('progressItem.product')->findOrFail($data['assign_id']);
                $progressItem = $assign->progressItem;
                $product = $progressItem->product;

                $completed = (int) ($data['completed_quantity'] ?? 0);
                $reject    = (int) ($data['reject_quantity'] ?? 0);
                $defect    = (int) ($data['defect_quantity'] ?? 0);

                $change = $completed + $reject + $defect;

                // update assign quantities
                $assign->increment('change_quantity', $change);
                $progressItem->increment('completed_quantity', $completed);

                // ================== HISTORY ==================
                OrderProgressHistory::create([
                    'order_progress_item_id'    => $progressItem->id,
                    'order_progress_assign_id'  => $assign->id,
                    'assign_batch_id'           => $assignBatch->id,
                    'order_progress_batch_id'   => $mainBatch->id,
                    'completed_quantity'        => $completed,
                    'defect_quantity'           => $defect,
                    'reject_quantity'           => $reject,
                    'operator_id'               => $assign->operator_id,
                    'note'                      => $data['note'] ?? null,
                    'created_at'                => $request->progress_date,
                ]);

                // ================== COMPLETED ==================
                if ($completed > 0) {
                    $assign->increment('completed_quantity', $completed);

                    // update stok produksi
                    $ps = ProductionStock::firstOrCreate(
                        ['product_id' => $product->id, 'production_warehouse_id' => 2],
                        [
                            'opening_stock' => 0,
                            'available_quantity' => 0,
                            'finished_product_stock' => 0,
                            'canceled_product_stock' => 0
                        ]
                    );
                    $ps->decrement('available_quantity', $completed);
                    $ps->increment('finished_product_stock', $completed);

                    $deliveryOrderItem = DeliveryOrderItem::where('order_progress_id', $assignBatch->order_progress_id)
                        ->where('product_id', $product->id)
                        ->latest()
                        ->first();

                    if ($deliveryOrderItem) {
                        $deliveryOrderItem->increment('ready_qty', $completed);
                    }
                }

                // ================== REJECT PRODUCT ==================
                if ($reject > 0) {
                    $assign->increment('reject_quantity', $reject);

                    // ambil cost dasar produk
                    $avgCost = $product->avg_cost ?? 0;
                    $fixedCost = $product->fixed_cost ?? 0;
                    $totalCost = $avgCost * $reject;
                    $totalFixedCost = $fixedCost * $reject;

                    $rejectProduct = RejectProduct::create([
                        'product_id'               => $product->id,
                        'order_progress_id'        => $assignBatch->order_progress_id,
                        'order_progress_batch_id'  => $mainBatch->id,
                        'assign_id'                => $assign->id,
                        'quantity'                 => $reject,
                        'avg_cost'                 => $avgCost,
                        'fixed_cost'               => $fixedCost,
                        'total_cost'               => $totalCost,
                        'total_fixed_cost'         => $totalFixedCost,
                        'reject_date'              => $request->progress_date,
                        'status'                   => 'pending',
                        'note'                     => $data['note'] ?? 'Reject product from production progress',
                        'user_id'                  => Auth::id(),
                    ]);

                    // bisa tambahkan RejectProductHistory di sini jika kamu ingin
                    // RejectProductHistory::create([...]);
                }

                // ================== DEFECT PRODUCT ==================
                if ($defect > 0) {
                    $assign->increment('defect_quantity', $defect);

                    $avgCost = $product->avg_cost ?? 0;
                    $fixedCost = $product->fixed_cost ?? 0;
                    $totalCost = $avgCost * $defect;
                    $totalFixedCost = $fixedCost * $defect;

                    DefectProduct::create([
                        'product_id'             => $product->id,
                        'quantity'               => $defect,
                        'defect_date'            => $request->progress_date,
                        'defect_type'            => 'production',
                        'avg_cost'               => $avgCost,
                        'fixed_cost'             => $fixedCost,
                        'total_cost'             => $totalCost,
                        'total_fixed_cost'       => $totalFixedCost,
                        'status'                 => 'pending',
                        'note'                   => $data['note'] ?? 'Defect product from production progress',
                        'user_id'                => Auth::id(),
                        'order_progress_id'      => $assignBatch->order_progress_id,
                        'order_progress_batch_id' => $mainBatch->id,
                        'assign_id'              => $assign->id,
                    ]);
                }

                // ================== STATUS ASSIGN ==================
                if ($assign->change_quantity >= $assign->assigned_quantity) {
                    $assign->update(['status' => 'completed']);
                } elseif ($assign->change_quantity > 0) {
                    $assign->update(['status' => 'progress']);
                }
            }

            // 🔹 Update Assign Batch (cek semua assign status)
            $batchAllCompleted = $assignBatch->assigns()->where('status', '!=', 'completed')->doesntExist();

            $assignBatch->update([
                'last_progress_date' => $request->progress_date,
                'note'               => $assignBatch->note
                    ? ($assignBatch->note . "\n" . ($request->note ?? ''))
                    : ($request->note ?? null),
                'status'             => $batchAllCompleted ? 'completed' : 'progress',
            ]);

            DB::commit();
            return redirect('/erp/productions/waiting-list/assign-list')->with('success', 'Progress batch berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // public function updateHistory(Request $request, $id)
    // {
    //     try {
    //         // 🔹 Validasi input
    //         $validated = $request->validate([
    //             'completed_quantity' => 'required|numeric|min:0',
    //             'defect_quantity' => 'nullable|numeric|min:0',
    //             'reject_quantity' => 'nullable|numeric|min:0',
    //             'note' => 'nullable|string|max:255',
    //         ]);

    //         // 🔹 Cari data yang akan diupdate
    //         $history = OrderProgressHistory::findOrFail($id);

    //         // 🔹 Update kolom
    //         $history->completed_quantity = $validated['completed_quantity'];
    //         $history->defect_quantity = $validated['defect_quantity'] ?? 0;
    //         $history->reject_quantity = $validated['reject_quantity'] ?? 0;
    //         $history->note = $validated['note'];
    //         $history->save();

    //         return redirect()->back()->with('success', 'History updated successfully.');
    //     } catch (\Throwable $e) {
    //         return redirect()->back()->with('error', 'Something went wrong while updating history: ' . $e->getMessage());
    //     }
    // }

    public function updateHistory(Request $request, $id)
    {
        try {
            // 🔹 Validasi input
            $validated = $request->validate([
                'completed_quantity' => 'required|numeric|min:0',
                'defect_quantity' => 'nullable|numeric|min:0',
                'reject_quantity' => 'nullable|numeric|min:0',
                'note' => 'nullable|string|max:255',
            ]);

            // 🔹 Ambil data history + relasi
            $history = OrderProgressHistory::with(['progressItem', 'assign'])->findOrFail($id);
            $progressItem = $history->progressItem;
            $assign = $history->assign; // relasi ke order_progress_assigns

            if (!$progressItem || !$assign) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Relasi progress item atau assign tidak ditemukan.'
                ], 404);
            }

            // 🔹 Hitung selisih nilai lama vs baru
            $oldCompleted = $history->completed_quantity ?? 0;
            $newCompleted = (int) $validated['completed_quantity'];
            $deltaCompleted = $newCompleted - $oldCompleted;

            $oldDefect = $history->defect_quantity ?? 0;
            $newDefect = (int) ($validated['defect_quantity'] ?? 0);
            $deltaDefect = $newDefect - $oldDefect;

            $oldReject = $history->reject_quantity ?? 0;
            $newReject = (int) ($validated['reject_quantity'] ?? 0);
            $deltaReject = $newReject - $oldReject;

            $deltaChange = $deltaCompleted + $deltaDefect + $deltaReject;

            // 🔹 Validasi batas maksimum quantity
            $totalCompletedNow = ($progressItem->completed_quantity ?? 0) + $deltaCompleted;
            if ($totalCompletedNow > $progressItem->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jumlah selesai melebihi total quantity produk (' . number_format($progressItem->quantity) . ').'
                ], 422);
            }

            DB::beginTransaction();

            // ===============================
            // 🟩 1. Update HISTORY
            // ===============================
            $history->update([
                'completed_quantity' => $newCompleted,
                'defect_quantity' => $newDefect,
                'reject_quantity' => $newReject,
                'note' => $validated['note'] ?? null,
            ]);

            // ===============================
            // 🟩 2. Update ORDER_PROGRESS_ITEMS
            // ===============================
            $progressItem->completed_quantity += $deltaCompleted;
            // $progressItem->defect_quantity += $deltaDefect;
            // $progressItem->reject_quantity += $deltaReject;
            // $progressItem->change_quantity += $deltaChange;
            $progressItem->save();

            // ===============================
            // 🟩 3. Update ORDER_PROGRESS_ASSIGNS
            // ===============================
            $assign->completed_quantity += $deltaCompleted;
            $assign->assigned_quantity += $deltaChange;
            $assign->defect_quantity += $deltaDefect;
            $assign->reject_quantity += $deltaReject;
            $assign->change_quantity += $deltaChange;

            // 🔹 Update assigned_quantity (berkurang jika completed naik)
            // $assign->assigned_quantity -= $deltaCompleted;
            // if ($assign->assigned_quantity < 0) $assign->assigned_quantity = 0;

            $assign->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'History, progress item, dan assign berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
