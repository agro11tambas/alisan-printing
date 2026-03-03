<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressItem;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Services\AssignCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderProgressAssignController extends Controller
{
    // public function create($id)
    // {
    //     $progress = OrderProgress::with(['items.product', 'order.customer'])
    //         ->findOrFail($id);

    //     $operators = Operator::where('active', 1)->orderBy('name')->get();

    //     // 🔹 Generate assign code otomatis lewat service
    //     $assignCode = AssignCode::generateAssignCode();

    //     return view('erp.pages.production.assign-list.add-assign', compact(
    //         'progress',
    //         'operators',
    //         'assignCode'
    //     ));
    // }

    public function create($id)
    {
        $progress = OrderProgress::with(['items.product', 'order.customer'])
            ->findOrFail($id);

        $operators = Operator::where('active', 1)->orderBy('name')->get();

        // 🔹 Generate assign code otomatis lewat service
        $assignCode = AssignCode::generateAssignCode();

        $productIds = $progress->items->pluck('product_id')->toArray();

        $productionStocks = DB::table('production_stocks')
            ->whereIn('product_id', $productIds)
            ->pluck('available_quantity', 'product_id');

        foreach ($progress->items as $item) {
            $totals = DB::table('order_progress_assigns')
                ->where('order_progress_item_id', $item->id)
                ->selectRaw('
                    COALESCE(SUM(assigned_quantity),0)  AS total_assigned,
                    COALESCE(SUM(completed_quantity),0) AS total_completed,
                    COALESCE(SUM(defect_quantity),0)    AS total_defect,
                    COALESCE(SUM(reject_quantity),0)    AS total_reject
                ')
                ->first();

            $activeAssign = max(
                ($totals->total_assigned) - ($totals->total_completed + $totals->total_defect + $totals->total_reject),
                0
            );

            $remaining = max(
                ($item->quantity) - (($item->completed_quantity ?? 0) + $activeAssign),
                0
            );

            $item->active_assign      = $activeAssign;   // kolom "Assigning"
            $item->available_quantity = $remaining;      // kolom "Available"
            $item->remaining_quantity = $remaining;      // max input "Assign Now"
            $item->production_stock = $productionStocks[$item->product_id] ?? 0;
        }

        return view('erp.pages.production.assign-list.add-assign', compact(
            'progress',
            'operators',
            'assignCode'
        ));
    }

    public function store(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            // 'assign_code' => 'required|string',
            'assign_date' => 'required|date',
            'note'        => 'nullable|string',
            'items'       => 'required|array',
            'items.*.order_progress_item_id' => 'required|exists:order_progress_items,id',
            // 🟢 validasi operator & qty hanya wajib kalau tidak bypass
            'items.*.operator_id'            => 'required_if:items.*.bypass,0|nullable|exists:operators,id',
            'items.*.assigned_quantity'      => 'required_if:items.*.bypass,0|integer|min:0',
            'items.*.note'                   => 'nullable|string',
            'items.*.bypass'                 => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $progress = OrderProgress::findOrFail($id);

            // 🟩 1. Buat satu batch assign baru
            $batch = OrderProgressAssignBatch::create([
                'order_progress_id' => $progress->id,
                'assign_code'       => $progress->order->order_number,
                'assign_date'       => $request->assign_date,
                'note'              => $request->note,
                'created_by'        => Auth::id(),
            ]);

            // 🟩 2. Loop produk yang diassign
            foreach ($request->items as $idx => $data) {
                // 🟡 skip jika bypass dicentang
                if (!empty($data['bypass']) && (int)$data['bypass'] === 1) {
                    continue;
                }

                if (empty($data['assigned_quantity']) || (int)$data['assigned_quantity'] <= 0) {
                    continue;
                }

                // $item = OrderProgressItem::query()
                //     ->withSum('assigns as total_completed', 'completed_quantity') // alias total_completed
                //     ->findOrFail($data['order_progress_item_id']);

                $item = OrderProgressItem::query()
                    ->with('assigns') // <= WAJIB
                    ->withSum('assigns as total_completed', 'completed_quantity')
                    ->findOrFail($data['order_progress_item_id']);


                // ⛔ BLOKIR jika assign sudah full assign
                $alreadyAssigned = $item->assigns->sum('completed_quantity');

                if ($alreadyAssigned >= $item->quantity) {
                    DB::rollBack();
                    return back()->with('error', "Produk {$item->product->name} sudah full assign, tidak bisa ditambahkan lagi.");
                }

                $quantity          = (int) $item->quantity;
                $completed         = (int) ($item->total_completed ?? 0); // total completed dari semua assign
                $remainingAllowed  = max($quantity - $completed, 0);      // <= inilah batas yang boleh di-assign lagi
                $requested         = (int) $data['assigned_quantity'];

                if ($requested > $remainingAllowed) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.assigned_quantity" => "Assigned quantity ($requested) melebihi remaining ($remainingAllowed) untuk produk {$item->product->name}.",
                    ]);
                }

                // kalau remaining 0, skip
                if ($remainingAllowed <= 0) {
                    continue;
                }

                $productionStock = ProductionStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'production_warehouse_id' => 2,
                    ],
                    [
                        'opening_stock' => 0,
                        'available_quantity' => 0,
                        'finished_product_stock' => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                // 🟥 Cek jika stok available 0 atau kurang dari requested
                // if ($productionStock->available_quantity <= 0) {
                //     throw \Illuminate\Validation\ValidationException::withMessages([
                //         "items.$idx.assigned_quantity" => "Stok available 0 untuk produk {$item->product->name}.",
                //     ]);
                // }

                // if ($requested > $productionStock->available_quantity) {
                //     throw \Illuminate\Validation\ValidationException::withMessages([
                //         "items.$idx.assigned_quantity" => "Assigned quantity ($requested) melebihi stok available ({$productionStock->available_quantity}) untuk produk {$item->product->name}.",
                //     ]);
                // }

                OrderProgressAssign::create([
                    'assign_batch_id'        => $batch->id,
                    'order_progress_item_id' => $item->id,
                    'product_id'             => $item->product_id,
                    'operator_id'            => (int) $data['operator_id'],
                    'assigned_quantity'      => $requested, // aman karena <= remaining
                    'completed_quantity'     => 0,
                    'note'                   => $data['note'] ?? null,
                ]);

                $productionStock = ProductionStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'production_warehouse_id' => 2, // sesuaikan jika perlu
                    ],
                    [
                        'opening_stock' => 0,
                        'available_quantity' => 0,
                        'finished_product_stock' => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                $productionStock->decrement('available_quantity', $requested);
                $productionStock->decrement('pending_waiting_list', $requested);
            }

            DB::commit();

            return redirect('/erp/productions/waiting-list')
                ->with('success', "Assign batch {$batch->assign_code} berhasil ditambahkan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($batch_id)
    {
        $batch = OrderProgressAssignBatch::with([
            'orderProgress.order.customer',
            'orderProgress.items.product',
            'assigns.operator',
            'assigns.progressItem'
        ])->findOrFail($batch_id);

        $operators = Operator::where('active', 1)->orderBy('name')->get();

        // Ambil progress terkait agar loop item sama seperti di create()
        $progress = $batch->orderProgress;

        $productIds = $progress->items->pluck('product_id')->toArray();

        $productionStocks = DB::table('production_stocks')
            ->whereIn('product_id', $productIds)
            ->pluck('available_quantity', 'product_id'); // hasil: [product_id => qty]

        foreach ($batch->assigns as $assign) {
            $item = $assign->progressItem;

            // Hitung total assign, completion dsb berdasarkan item
            $totals = DB::table('order_progress_assigns')
                ->where('order_progress_item_id', $item->id)
                ->selectRaw('
            COALESCE(SUM(assigned_quantity),0)  AS total_assigned,
            COALESCE(SUM(completed_quantity),0) AS total_completed,
            COALESCE(SUM(defect_quantity),0)    AS total_defect,
            COALESCE(SUM(reject_quantity),0)    AS total_reject
        ')
                ->first();

            // $activeAssign = max(
            //     $totals->total_assigned - ($totals->total_completed + $totals->total_defect + $totals->total_reject),
            //     0
            // );

            // $currentBatchAssign = $assign->assigned_quantity;

            // $remaining = max(
            //     $item->quantity - (($item->completed_quantity ?? 0) + $activeAssign),
            //     0
            // );

            // $assign->active_assign      = $activeAssign;
            // $assign->available_quantity = $remaining;
            // $assign->remaining_quantity = $remaining + $currentBatchAssign;

            // $activeAssignAll = max(
            //     $totals->total_assigned - ($totals->total_completed + $totals->total_defect + $totals->total_reject),
            //     0
            // );

            // $currentBatchAssign = (int) ($assign->assigned_quantity ?? 0);

            // // active assign selain batch ini
            // $activeAssignOther = max($activeAssignAll - $currentBatchAssign, 0);

            // $completedQty = (int) ($item->completed_quantity ?? 0);

            // // sisa yang masih bisa di-assign (di luar jatah batch ini)
            // $remaining = max($item->quantity - ($completedQty + $activeAssignOther), 0);

            // $assign->active_assign      = $activeAssignOther;          // ini “assigning” selain batch ini
            // $assign->available_quantity = $remaining;                  // available real (tanpa batch ini)
            // $assign->remaining_quantity = $remaining + $currentBatchAssign; // max yang boleh kamu isi saat edit

            // $item->production_stock = $productionStocks[$item->product_id] ?? 0;
            $activeAssignAll = max(
                $totals->total_assigned - ($totals->total_completed + $totals->total_defect + $totals->total_reject),
                0
            );

            $currentBatchAssign = (int) ($assign->assigned_quantity ?? 0);

            // Active assign SEBELUM batch ini di-edit
            $activeAssignOther = max($activeAssignAll - $currentBatchAssign, 0);

            $completedQty = (int) ($item->completed_quantity ?? 0);

            // Sisa yang bisa di-assign (tanpa memperhitungkan batch ini)
            $remaining = max($item->quantity - ($completedQty + $activeAssignOther), 0);

            // ✅ MAX = REMAINING (jangan tambah currentBatchAssign!)
            $item->active_assign      = $activeAssignOther;
            $item->available_quantity = $remaining;
            $item->remaining_quantity = $remaining; // ✅ INI YANG BENAR!
            $item->production_stock   = $productionStocks[$item->product_id] ?? 0;
        }

        return view('erp.pages.production.assign-list.edit-assign', compact(
            'batch',
            'progress',
            'operators'
        ));
    }

    public function update(Request $request, $batch_id)
    {
        // dd($request->all());
        $request->validate([
            'assign_date' => 'required|date',
            'note'        => 'nullable|string',
            'items'       => 'required|array',
            'items.*.id'  => 'nullable|exists:order_progress_assigns,id',
            'items.*.order_progress_item_id' => 'required|exists:order_progress_items,id',
            'items.*.operator_id'            => 'required_if:items.*.bypass,0|nullable|exists:operators,id',
            'items.*.assigned_quantity'      => 'required_if:items.*.bypass,0|integer|min:0',
            'items.*.note'                   => 'nullable|string',
            'items.*.bypass'                 => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $batch = OrderProgressAssignBatch::findOrFail($batch_id);

            // 🔹 Update batch utama
            $batch->update([
                'assign_date' => $request->assign_date,
                'note'        => $request->note,
            ]);

            foreach ($request->items as $data) {
                // 🚫 Skip kalau bypass dicentang → hapus assign lama kalau ada
                if (!empty($data['bypass']) && (int)$data['bypass'] === 1) {
                    if (!empty($data['id'])) {
                        $assign = OrderProgressAssign::find($data['id']);
                        if ($assign) {
                            // 🟢 Kembalikan stok sebelum hapus (biar konsisten)
                            $restoreQty = (int) $assign->assigned_quantity;
                            $productionStock = ProductionStock::firstOrCreate(
                                [
                                    'product_id' => $assign->product_id,
                                    'production_warehouse_id' => 2,
                                ],
                                [
                                    'opening_stock' => 0,
                                    'available_quantity' => 0,
                                    'finished_product_stock' => 0,
                                    'canceled_product_stock' => 0,
                                ]
                            );
                            $productionStock->increment('available_quantity', $restoreQty);
                            $productionStock->increment('pending_waiting_list', $restoreQty);

                            // 🗑️ Hapus record assign lama
                            $assign->forceDelete();
                        }
                    }
                    continue;
                }

                // 🚫 Skip kalau qty kosong atau 0
                if (empty($data['assigned_quantity']) || $data['assigned_quantity'] <= 0) continue;

                $item = OrderProgressItem::with('product')->findOrFail($data['order_progress_item_id']);

                // Hitung total assign batch ini
                $currentAssigned = $item->assigns()
                    ->where('assign_batch_id', $batch->id)
                    ->sum('assigned_quantity');

                // Hitung total assign batch lain
                $alreadyAssigned = $item->assigns()
                    ->where('assign_batch_id', '!=', $batch->id)
                    ->sum('assigned_quantity');

                // Total sisa kuota = stok - batch lain + batch ini
                $remaining = max($item->quantity - $alreadyAssigned + $currentAssigned, 0);
                $assignedQty = min($data['assigned_quantity'], $remaining);

                if ($assignedQty <= 0) continue;

                // 🔹 Ambil stok produksi
                $productionStock = ProductionStock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'production_warehouse_id' => 2,
                    ],
                    [
                        'opening_stock' => 0,
                        'available_quantity' => 0,
                        'finished_product_stock' => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                // 🔹 Update jika ID ada, else buat baru
                if (!empty($data['id'])) {
                    $assign = OrderProgressAssign::find($data['id']);

                    if ($assign) {
                        $oldQty = (int) $assign->assigned_quantity;
                        $newQty = (int) $assignedQty;
                        $diff   = $newQty - $oldQty;

                        if ($diff > 0) {
                            $productionStock->decrement('available_quantity', $diff);
                            $productionStock->decrement('pending_waiting_list', $diff);
                        } elseif ($diff < 0) {
                            $restoreQty = abs($diff);
                            $productionStock->increment('available_quantity', $restoreQty);
                            $productionStock->increment('pending_waiting_list', $restoreQty);
                        }

                        $assign->update([
                            'operator_id'       => $data['operator_id'],
                            'assigned_quantity' => $newQty,
                            'note'              => $data['note'] ?? null,
                        ]);
                    }
                } else {
                    // 🆕 Buat baru
                    OrderProgressAssign::create([
                        'assign_batch_id'        => $batch->id,
                        'order_progress_item_id' => $item->id,
                        'product_id'             => $item->product_id,
                        'operator_id'            => $data['operator_id'],
                        'assigned_quantity'      => $assignedQty,
                        'note'                   => $data['note'] ?? null,
                    ]);

                    $productionStock->decrement('available_quantity', $assignedQty);
                    $productionStock->decrement('pending_waiting_list', $assignedQty);
                }
            }

            DB::commit();

            return redirect('/erp/productions/waiting-list/assign-list')
                ->with('success', "Assign batch {$batch->assign_code} berhasil diperbarui.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $batch = OrderProgressAssignBatch::with(['assigns.product'])->findOrFail($id);

            // 🔹 Kembalikan stok dari semua assign di batch ini
            foreach ($batch->assigns as $assign) {
                // Ambil stok produksi berdasarkan produk
                $productionStock = ProductionStock::firstOrCreate(
                    [
                        'product_id' => $assign->product_id,
                        'production_warehouse_id' => 2,
                    ],
                    [
                        'opening_stock' => 0,
                        'available_quantity' => 0,
                        'finished_product_stock' => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                // 🔸 Kembalikan stok sebanyak assigned_quantity
                $productionStock->increment('available_quantity', $assign->assigned_quantity);
                $productionStock->increment('pending_waiting_list', $assign->assigned_quantity);

                // 🔹 Hapus assign permanen
                $assign->forceDelete();
            }

            // 🔹 Hapus batch itu sendiri
            $batch->forceDelete();

            DB::commit();
            return back()->with('success', "Batch {$batch->assign_code} berhasil dihapus secara permanen dan stok dikembalikan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus batch: ' . $e->getMessage());
        }
    }

    public function getAssignList()
    {
        return view('erp.pages.production.assign-list.assign-list');
    }

    public function dataAssignList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $batches = OrderProgressAssignBatch::with([
            'assigns.operator',
            'orderProgress.order.customer'
        ]);

        if ($request->filled('progress_status')) {
            switch ($request->progress_status) {
                case 'progress':
                    $batches->where('status', '!=', 'completed')
                        ->orderBy('assign_date', 'asc'); // ⬅️ terlama dulu
                    break;
                case 'completed':
                    $batches->where('status', 'completed')
                        ->orderBy('assign_date', 'desc'); // ⬅️ terbaru dulu
                    break;
            }
        } else {
            $batches->orderBy('assign_date', 'desc'); // ⬅️ default = desc
        }

        // 🔹 Filter berdasarkan status progress
        if ($request->filled('progress_status')) {
            switch ($request->progress_status) {
                case 'progress':
                    $batches->where('status', '!=', 'completed');
                    break;
                case 'completed':
                    $batches->where('status', 'completed');
                    break;
            }
        }

        // 🔹 Filter berdasarkan assign code (search)
        // if ($request->filled('search_keyword')) {
        //     $keyword = trim($request->search_keyword);
        //     $batches->where('assign_code', 'like', "%{$keyword}%");
        // }

        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword . '%';

            if ($request->search_type === 'customer') {
                $batches->where(function ($q) use ($keyword) {

                    // 🔍 Cari berdasarkan nama customer
                    $q->whereHas('orderProgress.order.customer', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', $keyword);
                    });

                    // 🔍 Cari berdasarkan business_name
                    $q->orWhereHas('orderProgress.order.customerAddress', function ($sub) use ($keyword) {
                        $sub->where('business_name', 'like', $keyword);
                    });
                });
            } else {
                // 🔍 Cari order_number
                $batches->whereHas('orderProgress.order', function ($q) use ($keyword) {
                    $q->where('order_number', 'like', $keyword);
                });
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $batches->whereHas('orderProgress.items.product', function ($q) use ($productKeyword) {
                $q->where(function ($sub) use ($productKeyword) {
                    $sub->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"])
                        ->orWhereRaw("LOWER(sku) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
                });
            });
        }

        // 🔹 Filter tanggal (optional)
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $batches->whereDate('assign_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $batches->whereBetween('assign_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $batches->whereMonth('assign_date', Carbon::now()->month)
                        ->whereYear('assign_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $batches->whereBetween('assign_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $batches->whereBetween('assign_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $batches->whereYear('assign_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $batches->whereBetween('assign_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // ✅ Hindari query count dua kali
        $totalQuery = clone $batches;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $batches->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($batch) {
                $date = Carbon::parse($batch->created_at)->format('d M y H:i');

                // 🧾 Assign Code + Date
                $assignCodeHtml = "<div><div>{$batch->assign_code}</div><small class='text-muted'>{$date}</small></div>";

                // 🧍‍♂️ Order info + customer
                $order = $batch->orderProgress->order ?? null;
                $orderInfo = '-';
                if ($order) {
                    $customerName = $order->customer ? $order->customer->name : 'Unknown';
                    $orderInfo = "<div>
                    <div>" . ($order->order_number ?? '') . "</div>
                    <small class='text-muted'>{$customerName}</small>
                </div>";
                }

                // 👥 Total item, quantity, operator
                $totalItems = $batch->assigns->count();
                $totalQuantity = number_format($batch->assigns->sum('assigned_quantity'), 0, ',', '.');
                $operators = $batch->assigns->pluck('operator.name')->unique()->implode(', ') ?: '-';
                $note = e($batch->note ?? '-');

                // 📦 Assign products partial
                // $assigns = OrderProgressAssign::with(['operator', 'progressItem.product'])
                //     ->where('assign_batch_id', $batch->id)
                //     ->get();
                $assigns = OrderProgressAssign::with([
                    'operator',
                    'progressItem.product',
                    'progressItem.designItem' // ⬅️ tambah ini
                ])
                    ->where('assign_batch_id', $batch->id)
                    ->get();

                $assignProducts = view('erp.pages.production.assign-list.partials.assign-product', compact('assigns'))->render();

                // ⚙️ Action button partial
                $allCompleted = $batch->assigns->every(function ($assign) {
                    return $assign->change_quantity >= $assign->assigned_quantity;
                });

                $hasOnlyProgressStatus = !DB::table('order_progress_assigns')
                    ->where('assign_batch_id', $batch->id)
                    ->where('status', '!=', 'progress')
                    ->exists();

                $action = view('erp.pages.production.assign-list.partials.assign-action-button', compact(
                    'batch',
                    'allCompleted',
                    'hasOnlyProgressStatus'
                ))->render();

                $businessName = e($batch->orderProgress?->order?->customerAddress?->business_name ?? '-');
                $customerName = e($batch->orderProgress?->order?->customer?->name ?? '-');

                $customerHtml = '
                    <div style="white-space: normal; word-break: break-word; max-width:180px;">
                        <div class="fw-semibold">' . $businessName . '</div>
                        <small class="text-muted">' . $customerName . '</small>
                    </div>
                ';

                $orderNotesValue = $batch->orderProgress?->order?->notes;

                $orderNotes = $orderNotesValue
                    ? '<div style="white-space:normal; word-break:break-word; max-width:220px;">'
                    . e($orderNotesValue) .
                    '</div>'
                    : '<div>-</div>';

                return [
                    'id' => $batch->id,
                    'assign_code' => $assignCodeHtml,
                    'order_info' => $orderInfo,
                    'customer' => $customerHtml,
                    'total_items' => $totalItems,
                    'total_quantity' => $totalQuantity,
                    'operators' => e($operators),
                    'note' => $note,
                    'assign_products' => $assignProducts,
                    'action' => $action,
                    'order_notes' => $orderNotes,
                    'created_at' => $date,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function AssignSummary(Request $request)
    {
        // BASE QUERY
        $query = OrderProgressAssign::query()
            ->with(['progressItem.product', 'batch', 'product']);

        // ================== FILTER TANGGAL ==================
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereDate('assign_date', Carbon::today())
                    );
                    break;

                case 'last_7_days':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereBetween('assign_date', [
                            Carbon::now()->subDays(7),
                            Carbon::now()
                        ])
                    );
                    break;

                case 'this_month':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereMonth('assign_date', Carbon::now()->month)
                            ->whereYear('assign_date', Carbon::now()->year)
                    );
                    break;

                case 'last_30_days':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereBetween('assign_date', [
                            Carbon::now()->subDays(30),
                            Carbon::now()
                        ])
                    );
                    break;

                case 'year_to_date':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereBetween('assign_date', [
                            Carbon::now()->startOfYear(),
                            Carbon::now()
                        ])
                    );
                    break;

                case 'yearly':
                    $query->whereHas(
                        'batch',
                        fn($q) =>
                        $q->whereYear('assign_date', Carbon::now()->year)
                    );
                    break;

                case 'custom':
                    if ($request->start_date && $request->end_date) {
                        $query->whereHas(
                            'batch',
                            fn($q) =>
                            $q->whereBetween('assign_date', [
                                $request->start_date,
                                $request->end_date
                            ])
                        );
                    }
                    break;
            }
        }

        // ================== FILTER PRODUCT ==================
        if ($request->filled('product')) {
            $key = strtolower(trim($request->product));

            $query->where(function ($q) use ($key) {
                $q->whereHas('progressItem.product', function ($sub) use ($key) {
                    $sub->whereRaw("LOWER(name) LIKE ?", ["%{$key}%"])
                        ->orWhereRaw("LOWER(sku) LIKE ?", ["%{$key}%"]);
                });

                $q->orWhereHas('product', function ($sub) use ($key) {
                    $sub->whereRaw("LOWER(name) LIKE ?", ["%{$key}%"])
                        ->orWhereRaw("LOWER(sku) LIKE ?", ["%{$key}%"]);
                });
            });
        }

        // ======================================================
        // 1) Ambil daftar semua product yang pernah muncul di Assign
        // ======================================================
        $allProducts = OrderProgressAssign::query()
            ->leftJoin('order_progress_items', 'order_progress_items.id', '=', 'order_progress_assigns.order_progress_item_id')
            ->selectRaw("
            DISTINCT 
            CASE 
                WHEN order_progress_assigns.product_id IS NOT NULL 
                THEN order_progress_assigns.product_id 
                ELSE order_progress_items.product_id 
            END AS final_product_id
        ")
            ->pluck('final_product_id');

        // ======================================================
        // 2) Summary hasil filter tanggal (qty bisa hilang → nanti dibuat 0)
        // ======================================================

        // ORIGINAL SUMMARY QUERY (DIBIARKAN, DITUTUP KOMENTAR)
        /*
    $base = $query->getQuery(); 

    $summary = $base
        ->leftJoin('order_progress_items', 'order_progress_items.id', '=', 'order_progress_assigns.order_progress_item_id')
        ->selectRaw("
            CASE 
                WHEN order_progress_assigns.product_id IS NOT NULL 
                THEN order_progress_assigns.product_id 
                ELSE order_progress_items.product_id 
            END AS final_product_id,
            SUM(order_progress_assigns.assigned_quantity) AS total_assigned_qty
        ")
        ->groupBy('final_product_id')
        ->get();
    */

        // → SOLUSI SUMMARY BARU (TETAP MENGGUNAKAN FILTER TANGGAL)
        $summary = $query->clone()
            ->leftJoin('order_progress_items', 'order_progress_items.id', '=', 'order_progress_assigns.order_progress_item_id')
            ->selectRaw("
            CASE 
                WHEN order_progress_assigns.product_id IS NOT NULL 
                THEN order_progress_assigns.product_id 
                ELSE order_progress_items.product_id 
            END AS final_product_id,
            SUM(order_progress_assigns.assigned_quantity) AS total_assigned_qty
        ")
            ->groupBy('final_product_id')
            ->get()
            ->keyBy('final_product_id'); // supaya mudah dicocokkan

        // ======================================================
        // 3) FORMAT RESULT — gabungkan allProducts + summary
        // ======================================================
        $data = collect($allProducts)->map(function ($productId) use ($summary) {
            $product = Products::withTrashed()->find($productId);

            return [
                'product_name'        => $product->name ?? '-',
                'sku'                 => $product->sku ?? '-',
                'total_assigned_qty'  => $summary[$productId]->total_assigned_qty ?? 0, // JIKA TIDAK ADA → 0
            ];
        });

        return response()->json(['data' => $data]);
    }
}
