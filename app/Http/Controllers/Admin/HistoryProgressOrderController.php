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
        $batches = OrderProgressBatch::with(['user', 'histories.progressItem.product', 'histories.operators', 'histories.machines'])
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

        $batches = $query;

        try {
            return DataTables::of($batches)
                ->addIndexColumn()
                ->addColumn('invoice_number', function ($batch) {
                    return $batch->orderProgress?->invoice_number ?? '-';
                })
                ->addColumn('date', function ($batch) {
                    return Carbon::parse($batch->created_at)->format('j M y H:i');
                })
                ->addColumn('user_name', function ($batch) {
                    return $batch->user?->name ?? '-';
                })
                ->addColumn('notes', function ($batch) {
                    return $batch->note ?? '-';
                })
                ->addColumn('products', function ($batch) {
                    $items = $batch->histories;

                    return view('erp.pages.production.waiting-list.partials.product-progress-histories', compact('items'))->render();
                })
                ->addColumn('action', function ($batch) {
                    return '
            <div class="text-center">
                <button type="button" class="btn btn-sm btn-danger btn-delete-batch"
                    data-id="' . $batch->id . '"
                    data-date="' . Carbon::parse($batch->date)->format('d M Y') . '"
                    data-user="' . ($batch->user->name ?? '-') . '">
                    Delete Batch
                </button>
            </div>
        ';
                })
                ->rawColumns(['products', 'action'])
                ->make(true);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function create($batch_id)
    {
        $batch = OrderProgressAssignBatch::with([
            'assigns.progressItem.product',
            'assigns.machine',
            'orderProgress.order.customer'
        ])->findOrFail($batch_id);

        // 🔹 Operator dipilih saat input progress, bukan saat assign
        $operators = Operator::where('active', 1)->orderBy('name')->get();

        return view('erp.pages.production.assign-list.add-progress-order', compact('batch', 'operators'));
    }

    public function store(Request $request, $batch_id)
    {
        $request->validate([
            'progress_date' => 'required|date',
            'note'          => 'nullable|string',
            'items'         => 'required|array',
            'items.*.assign_id'         => 'required|exists:order_progress_assigns,id',
            'items.*.operator_id'        => 'required|exists:operators,id',
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

                // ⛔ BLOKIR jika assign sudah full progress
                if (
                    ($assign->completed_quantity + $assign->reject_quantity + $assign->defect_quantity)
                    >= $assign->assigned_quantity
                ) {
                    DB::rollBack();
                    return back()->with('error', "Produk {$assign->progressItem->product->name} sudah full progress, tidak bisa ditambahkan lagi.");
                }

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
                $history = OrderProgressHistory::create([
                    'order_progress_item_id'    => $progressItem->id,
                    'order_progress_assign_id'  => $assign->id,
                    'product_id'                => $product->id,
                    'assign_batch_id'           => $assignBatch->id,
                    'order_progress_batch_id'   => $mainBatch->id,
                    'completed_quantity'        => $completed,
                    'defect_quantity'           => $defect,
                    'reject_quantity'           => $reject,
                    'operator_id'               => (int) $data['operator_id'],
                    'machine_id'                => $assign->machine_id,
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

                    $ps->increment('finished_product_stock', $completed);

                    $deliveryOrderItem = DeliveryOrderItem::where('order_progress_id', $assignBatch->order_progress_id)
                        ->where('order_progress_item_id', $progressItem->id) // ✅ tambahkan filter item
                        ->where('product_id', $product->id)
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

                    // 🔹 Tambahkan kembali ke pending waiting list
                    $ps = ProductionStock::firstOrCreate(
                        ['product_id' => $product->id, 'production_warehouse_id' => 2],
                        [
                            'opening_stock' => 0,
                            'available_quantity' => 0,
                            'finished_product_stock' => 0,
                            'canceled_product_stock' => 0,
                            'pending_waiting_list' => 0,
                        ]
                    );
                    $ps->increment('pending_waiting_list', $reject);

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
                        'order_progress_history_2_id'  => $history->id,
                    ]);

                    // 🔹 Tambahkan kembali ke pending waiting list
                    $ps = ProductionStock::firstOrCreate(
                        ['product_id' => $product->id, 'production_warehouse_id' => 2],
                        [
                            'opening_stock' => 0,
                            'available_quantity' => 0,
                            'finished_product_stock' => 0,
                            'canceled_product_stock' => 0,
                            'pending_waiting_list' => 0,
                        ]
                    );
                    $ps->increment('pending_waiting_list', $defect);
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

    //         // 🔹 Ambil data history + relasi
    //         $history = OrderProgressHistory::with(['progressItem', 'assign'])->findOrFail($id);
    //         $progressItem = $history->progressItem;
    //         $assign = $history->assign; // relasi ke order_progress_assigns

    //         if (!$progressItem || !$assign) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Relasi progress item atau assign tidak ditemukan.'
    //             ], 404);
    //         }

    //         // 🔹 Hitung selisih nilai lama vs baru
    //         $oldCompleted = $history->completed_quantity ?? 0;
    //         $newCompleted = (int) $validated['completed_quantity'];
    //         $deltaCompleted = $newCompleted - $oldCompleted;

    //         $oldDefect = $history->defect_quantity ?? 0;
    //         $newDefect = (int) ($validated['defect_quantity'] ?? 0);
    //         $deltaDefect = $newDefect - $oldDefect;

    //         $oldReject = $history->reject_quantity ?? 0;
    //         $newReject = (int) ($validated['reject_quantity'] ?? 0);
    //         $deltaReject = $newReject - $oldReject;

    //         $deltaChange = $deltaCompleted + $deltaDefect + $deltaReject;

    //         // 🔹 Validasi batas maksimum quantity
    //         $totalCompletedNow = ($progressItem->completed_quantity ?? 0) + $deltaCompleted;
    //         if ($totalCompletedNow > $progressItem->quantity) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Jumlah selesai melebihi total quantity produk (' . number_format($progressItem->quantity) . ').'
    //             ], 422);
    //         }

    //         DB::beginTransaction();

    //         // ===============================
    //         // 🟩 1. Update HISTORY
    //         // ===============================
    //         $history->update([
    //             'completed_quantity' => $newCompleted,
    //             'defect_quantity' => $newDefect,
    //             'reject_quantity' => $newReject,
    //             'note' => $validated['note'] ?? null,
    //         ]);

    //         // ===============================
    //         // 🟩 2. Update ORDER_PROGRESS_ITEMS
    //         // ===============================
    //         $progressItem->completed_quantity += $deltaCompleted;
    //         // $progressItem->defect_quantity += $deltaDefect;
    //         // $progressItem->reject_quantity += $deltaReject;
    //         // $progressItem->change_quantity += $deltaChange;
    //         $progressItem->save();

    //         // ===============================
    //         // 🟩 3. Update ORDER_PROGRESS_ASSIGNS
    //         // ===============================
    //         $assign->completed_quantity += $deltaCompleted;
    //         $assign->assigned_quantity += $deltaChange;
    //         $assign->defect_quantity += $deltaDefect;
    //         $assign->reject_quantity += $deltaReject;
    //         $assign->change_quantity += $deltaChange;

    //         // 🔹 Update assigned_quantity (berkurang jika completed naik)
    //         // $assign->assigned_quantity -= $deltaCompleted;
    //         // if ($assign->assigned_quantity < 0) $assign->assigned_quantity = 0;

    //         $assign->save();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'History, progress item, dan assign berhasil diperbarui.'
    //         ]);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function updateHistory(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'completed_quantity' => 'required|numeric|min:0',
                'defect_quantity'    => 'nullable|numeric|min:0',
                'reject_quantity'    => 'nullable|numeric|min:0',
                'note'               => 'nullable|string|max:255',
            ]);

            $history = OrderProgressHistory::with(['progressItem.product', 'assign'])->findOrFail($id);
            $progressItem = $history->progressItem;
            $assign = $history->assign;
            $product = $progressItem?->product;

            if (!$progressItem || !$assign || !$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Relasi progress item, assign, atau product tidak ditemukan.'
                ], 404);
            }

            // Hitung delta perubahan
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

            // Validasi batas quantity
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
                'defect_quantity'    => $newDefect,
                'reject_quantity'    => $newReject,
                'note'               => $validated['note'] ?? null,
            ]);

            // ===============================
            // 🟩 2. Update PROGRESS ITEM & ASSIGN
            // ===============================
            $progressItem->increment('completed_quantity', $deltaCompleted);
            $assign->increment('completed_quantity', $deltaCompleted);
            $assign->increment('defect_quantity', $deltaDefect);
            $assign->increment('reject_quantity', $deltaReject);
            $assign->increment('change_quantity', $deltaChange);

            // 🟩 2.1 Sync assigned_quantity dengan total baru (completed + defect + reject)
            $newTotalChange = $newCompleted + $newDefect + $newReject;
            $oldTotalChange = $oldCompleted + $oldDefect + $oldReject;
            $deltaAssigned  = $newTotalChange - $oldTotalChange;

            if ($deltaAssigned !== 0) {
                $assign->increment('assigned_quantity', $deltaAssigned);
            }

            // ===============================
            // 🟩 3. Update PRODUCTION STOCK
            // ===============================
            $ps = ProductionStock::firstOrCreate(
                ['product_id' => $product->id, 'production_warehouse_id' => 2],
                [
                    'opening_stock' => 0,
                    'available_quantity' => 0,
                    'finished_product_stock' => 0,
                    'canceled_product_stock' => 0,
                    'pending_waiting_list' => 0,
                ]
            );

            if ($deltaCompleted !== 0) {
                $ps->increment('finished_product_stock', $deltaCompleted);

                if ($deltaCompleted > 0) {
                    $ps->decrement('pending_waiting_list', $deltaCompleted);
                    $ps->decrement('available_quantity', $deltaCompleted); // ← TAMBAH INI SAJA
                } else {
                    $ps->increment('pending_waiting_list', abs($deltaCompleted));
                    $ps->increment('available_quantity', abs($deltaCompleted)); // ← TAMBAH INI SAJA
                }
            }

            if ($deltaAssigned !== 0) {
                $deltaAssignedOnly = $deltaAssigned - $deltaCompleted; // exclude yang sudah dihitung di deltaCompleted

                if ($deltaAssignedOnly > 0) {
                    $ps->decrement('available_quantity', $deltaAssignedOnly);
                    $ps->decrement('pending_waiting_list', $deltaAssignedOnly);
                } elseif ($deltaAssignedOnly < 0) {
                    $ps->increment('available_quantity', abs($deltaAssignedOnly));
                    $ps->increment('pending_waiting_list', abs($deltaAssignedOnly));
                }
            }

            // ===============================
            // 🟩 3.1 Update DELIVERY ORDER ITEM (ready_qty)
            // ===============================
            if ($deltaCompleted !== 0 && $progressItem) {
                $deliveryOrderItem = DeliveryOrderItem::where('order_progress_item_id', $progressItem->id)->first();

                if ($deliveryOrderItem) {
                    // Jika deltaCompleted positif = increment, negatif = decrement
                    $deliveryOrderItem->increment('ready_qty', $deltaCompleted);
                }
            }

            $orderProgressHistoryId = $history->exists ? $history->id : null;

            // ===============================
            // 🟩 4. Update / Tambah REJECT PRODUCT
            // ===============================
            if ($deltaReject !== 0) {
                $avgCost = $product->avg_cost ?? 0;
                $fixedCost = $product->fixed_cost ?? 0;

                $rejectRecord = RejectProduct::updateOrCreate(
                    [
                        'order_progress_history_2_id' => $orderProgressHistoryId,
                        'order_progress_id'          => $assign->order_progress_id,
                        // 'assign_id'                  => $assign->id,
                        'product_id'                 => $product->id,
                    ],
                    [
                        'avg_cost'        => $avgCost,
                        'fixed_cost'      => $fixedCost,
                        'reject_date'     => now(),
                        'status'          => 'pending',
                        'note'            => 'Auto update from history',
                        'user_id'         => Auth::id(),
                    ]
                );

                $rejectRecord->quantity = ($rejectRecord->quantity ?? 0) + $deltaReject;
                // $rejectRecord->total_cost = $rejectRecord->quantity * $rejectRecord->avg_cost;
                // $rejectRecord->total_fixed_cost = $rejectRecord->quantity * $rejectRecord->fixed_cost;
                $rejectRecord->save();
            }

            // ===============================
            // 🟩 5. Update / Tambah DEFECT PRODUCT
            // ===============================
            if ($deltaDefect !== 0) {
                $avgCost = $product->avg_cost ?? 0;
                $fixedCost = $product->fixed_cost ?? 0;

                $defectRecord = DefectProduct::updateOrCreate(
                    [
                        'order_progress_history_2_id' => $orderProgressHistoryId,
                        // 'order_progress_id'          => $assign->order_progress_id,
                        // 'assign_id'                  => $assign->id,
                        'product_id'                 => $product->id,
                    ],
                    [
                        'avg_cost'        => $avgCost,
                        'fixed_cost'      => $fixedCost,
                        'defect_date'     => now(),
                        'defect_type'     => 'production',
                        'status'          => 'pending',
                        'note'            => 'Auto update from history',
                        'user_id'         => Auth::id(),
                    ]
                );

                $defectRecord->quantity = ($defectRecord->quantity ?? 0) + $deltaDefect;
                // $defectRecord->total_cost = $defectRecord->quantity * $defectRecord->avg_cost;
                // $defectRecord->total_fixed_cost = $defectRecord->quantity * $defectRecord->fixed_cost;
                $defectRecord->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'History, stok produksi, dan produk reject/defect berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteHistory($id)
    {
        DB::beginTransaction();
        try {
            $history = OrderProgressHistory::with(['progressItem.product', 'assign.batch'])->findOrFail($id);
            $progressItem = $history->progressItem;
            $assign = $history->assign;
            $product = $progressItem?->product;

            if (!$progressItem || !$assign || !$product) {
                return response()->json(['message' => 'Relasi progress item, assign, atau product tidak ditemukan.'], 404);
            }

            // Ambil nilai lama sebelum dihapus
            $completed = $history->completed_quantity ?? 0;
            $defect = $history->defect_quantity ?? 0;
            $reject = $history->reject_quantity ?? 0;
            $totalChange = $completed + $defect + $reject;

            // 1️⃣ Balikkan nilai ke progress item dan assign
            $progressItem->decrement('completed_quantity', $completed);
            $assign->decrement('completed_quantity', $completed);
            $assign->decrement('defect_quantity', $defect);
            $assign->decrement('reject_quantity', $reject);
            $assign->decrement('change_quantity', $totalChange);

            // 2️⃣ Update stok produksi - HANYA untuk finished_product dan pending_waiting_list
            $ps = ProductionStock::where('product_id', $product->id)
                ->where('production_warehouse_id', 2)
                ->first();

            if ($ps && $completed > 0) {
                $ps->decrement('finished_product_stock', $completed);
                $ps->increment('pending_waiting_list', $completed);
                // $ps->increment('available_quantity', $completed);
            }

            // 2.1️⃣ Decrement ready_qty pada delivery_order_items
            if ($completed > 0 && $progressItem) {
                $deliveryOrderItem = DeliveryOrderItem::where('order_progress_item_id', $progressItem->id)->first();

                if ($deliveryOrderItem) {
                    $deliveryOrderItem->decrement('ready_qty', $completed);
                }
            }

            // 3️⃣ Hapus record defect & reject terkait
            RejectProduct::where('order_progress_history_2_id', $history->id)->forceDelete();
            DefectProduct::where('order_progress_history_2_id', $history->id)->forceDelete();

            // 4️⃣ Simpan assign_id dan assign_batch_id sebelum hapus history
            $assignId = $history->order_progress_assign_id;
            $assignBatchId = $assign?->assign_batch_id;

            // 5️⃣ Hapus history
            $history->forceDelete();

            // 6️⃣ Hapus assign jika tidak ada history lain yang terkait
            if ($assignId) {
                $remainingHistories = OrderProgressHistory::where('order_progress_assign_id', $assignId)->count();
                if ($remainingHistories == 0) {
                    $assignToDelete = OrderProgressAssign::find($assignId);

                    if ($assignToDelete && $assignToDelete->assigned_quantity > 0) {
                        // Increment available_quantity di production_stock
                        $psAssign = ProductionStock::where('product_id', $product->id)
                            ->where('production_warehouse_id', 2)
                            ->first();

                        if ($psAssign) {
                            $psAssign->increment('available_quantity', $assignToDelete->assigned_quantity);
                            $psAssign->increment('pending_waiting_list', $assignToDelete->assigned_quantity);
                        }
                    }

                    // Hapus assign tanpa trigger event
                    OrderProgressAssign::withoutEvents(function () use ($assignId) {
                        OrderProgressAssign::where('id', $assignId)->forceDelete();
                    });
                }
            }

            // 7️⃣ Hapus assign batch jika tidak ada assign lain yang terkait
            if ($assignBatchId) {
                $remainingAssigns = OrderProgressAssign::where('assign_batch_id', $assignBatchId)->count();
                if ($remainingAssigns == 0) {
                    // Hapus assign batch tanpa trigger event
                    OrderProgressAssignBatch::withoutEvents(function () use ($assignBatchId) {
                        OrderProgressAssignBatch::where('id', $assignBatchId)->forceDelete();
                    });
                }
            }

            DB::commit();
            return response()->json(['message' => 'History berhasil dihapus dan stok telah diperbarui.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus history: ' . $e->getMessage()], 500);
        }
    }

    public function deleteBatch($batchId)
    {
        DB::beginTransaction();
        try {
            $batch = OrderProgressBatch::with([
                'histories.progressItem.product',
                'histories.assign.batch'
            ])->findOrFail($batchId);

            // Kumpulkan assign_id dan assign_batch_id yang akan dihapus
            $assignIds = [];
            $assignBatchIds = [];

            foreach ($batch->histories as $history) {
                $progressItem = $history->progressItem;
                $assign = $history->assign;
                $product = $progressItem?->product;

                $completed = $history->completed_quantity ?? 0;
                $defect = $history->defect_quantity ?? 0;
                $reject = $history->reject_quantity ?? 0;
                $totalChange = $completed + $defect + $reject;

                // 🔹 Balikkan nilai progress item & assign
                if ($progressItem) {
                    $progressItem->decrement('completed_quantity', $completed);
                }

                if ($assign) {
                    $assign->decrement('completed_quantity', $completed);
                    $assign->decrement('defect_quantity', $defect);
                    $assign->decrement('reject_quantity', $reject);
                    $assign->decrement('change_quantity', $totalChange);

                    // Ambil assign_batch_id dari assign
                    if ($assign->assign_batch_id) {
                        $assignBatchIds[] = $assign->assign_batch_id;
                    }
                }

                // 🔹 Update stok produksi - HANYA untuk finished_product dan pending_waiting_list
                if ($product && $completed > 0) {
                    $ps = ProductionStock::where('product_id', $product->id)
                        ->where('production_warehouse_id', 2)
                        ->first();

                    if ($ps) {
                        $ps->decrement('finished_product_stock', $completed);
                        $ps->increment('pending_waiting_list', $completed);
                    }

                    // 🔹 Decrement ready_qty pada delivery_order_items
                    if ($progressItem) {
                        $deliveryOrderItem = DeliveryOrderItem::where('order_progress_item_id', $progressItem->id)->first();

                        if ($deliveryOrderItem) {
                            $deliveryOrderItem->decrement('ready_qty', $completed);
                        }
                    }
                }

                // 🔹 Hapus defect & reject terkait
                RejectProduct::where('order_progress_history_2_id', $history->id)->forceDelete();
                DefectProduct::where('order_progress_history_2_id', $history->id)->forceDelete();

                // 🔹 Simpan assign_id
                if ($history->order_progress_assign_id) {
                    $assignIds[] = $history->order_progress_assign_id;
                }

                // 🔹 Hapus history tanpa trigger event
                OrderProgressHistory::withoutEvents(function () use ($history) {
                    $history->forceDelete();
                });
            }

            // 🔹 Hapus batch tanpa trigger event
            OrderProgressBatch::withoutEvents(function () use ($batch) {
                $batch->forceDelete();
            });

            // 🔹 Hapus order_progress_assign yang tidak memiliki history lagi
            $uniqueAssignIds = array_unique($assignIds);
            foreach ($uniqueAssignIds as $assignId) {
                $remainingHistories = OrderProgressHistory::where('order_progress_assign_id', $assignId)->count();
                if ($remainingHistories == 0) {
                    $assignToDelete = OrderProgressAssign::with('progressItem.product')->find($assignId);
                    if ($assignToDelete && $assignToDelete->assigned_quantity > 0) {
                        $productAssign = $assignToDelete->progressItem?->product;
                        if ($productAssign) {
                            // Increment available_quantity di production_stock
                            $psAssign = ProductionStock::where('product_id', $productAssign->id)
                                ->where('production_warehouse_id', 2)
                                ->first();

                            if ($psAssign) {
                                $psAssign->increment('available_quantity', $assignToDelete->assigned_quantity);
                            }
                        }
                    }

                    // Hapus tanpa trigger event
                    OrderProgressAssign::withoutEvents(function () use ($assignId) {
                        OrderProgressAssign::where('id', $assignId)->forceDelete();
                    });
                }
            }

            // 🔹 Hapus order_progress_assign_batch yang tidak memiliki assign lagi
            $uniqueAssignBatchIds = array_unique($assignBatchIds);
            foreach ($uniqueAssignBatchIds as $assignBatchId) {
                $remainingAssigns = OrderProgressAssign::where('assign_batch_id', $assignBatchId)->count();
                if ($remainingAssigns == 0) {
                    // Hapus tanpa trigger event
                    OrderProgressAssignBatch::withoutEvents(function () use ($assignBatchId) {
                        OrderProgressAssignBatch::where('id', $assignBatchId)->forceDelete();
                    });
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Batch dan seluruh history terkait berhasil dihapus serta stok diperbarui.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus batch: ' . $e->getMessage()
            ], 500);
        }
    }
}
