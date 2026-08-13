<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressItem;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\Setting;
use App\Services\AssignCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderProgressAssignController extends Controller
{
    public function create($id)
    {
        $progress = OrderProgress::with(['items.product', 'order.customer'])
            ->findOrFail($id);

        $machines = Machine::where('active', 1)->orderBy('name')->get();

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

            $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

            if ($unitConversionValue <= 0) {
                $unitConversionValue = 1;
            }

            $baseQuantity = (float) ($item->quantity ?? 0) * $unitConversionValue;

            $activeAssign = max(
                ((float) $totals->total_assigned) - (
                    (float) $totals->total_completed +
                    (float) $totals->total_defect +
                    (float) $totals->total_reject
                ),
                0
            );

            $remaining = max(
                $baseQuantity - ((float) ($item->completed_quantity ?? 0) + $activeAssign),
                0
            );

            $item->active_assign = $activeAssign;
            $item->available_quantity = $remaining;
            $item->remaining_quantity = $remaining;
            $item->production_stock = $productionStocks[$item->product_id] ?? 0;

            $item->base_quantity = $baseQuantity;
            $item->base_available_quantity = $remaining;
        }

        return view('erp.pages.production.assign-list.add-assign', compact(
            'progress',
            'machines',
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
            // 🟢 mesin dipilih per produk, bukan sekali per batch (per invoice)
            'items.*.machine_id'             => 'nullable|exists:machines,id',
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
                'machine_id'        => null, // 🔹 diisi dari mesin produk pertama yang diassign
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

                // 🔧 mesin wajib untuk setiap produk yang benar-benar diassign
                if (empty($data['machine_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.machine_id" => "Mesin wajib dipilih untuk setiap produk yang diassign.",
                    ]);
                }

                $machineId = (int) $data['machine_id'];

                // $item = OrderProgressItem::query()
                //     ->withSum('assigns as total_completed', 'completed_quantity') // alias total_completed
                //     ->findOrFail($data['order_progress_item_id']);

                $item = OrderProgressItem::query()
                    ->with(['assigns', 'product'])
                    ->findOrFail($data['order_progress_item_id']);

                $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $quantityBase = (int) ((float) ($item->quantity ?? 0) * $unitConversionValue);

                $completed = (int) ($item->completed_quantity ?? 0);

                $activeAssigned = (int) $item->assigns->sum(function ($assign) {
                    return max(
                        ((int) $assign->assigned_quantity)
                            - ((int) $assign->completed_quantity)
                            - ((int) $assign->defect_quantity)
                            - ((int) $assign->reject_quantity),
                        0
                    );
                });

                $remainingAllowed = max($quantityBase - ($completed + $activeAssigned), 0);

                $requestedAssignQty = (int) $data['assigned_quantity'];

                if ($remainingAllowed <= 0) {
                    DB::rollBack();
                    return back()->with('error', "Produk {$item->product->name} sudah full assign, tidak bisa ditambahkan lagi.");
                }

                if ($requestedAssignQty > $remainingAllowed) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.assigned_quantity" => "Assigned quantity ($requestedAssignQty) melebihi remaining ($remainingAllowed) untuk produk {$item->product->name}.",
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

                if (!Setting::isEnabled('allow_negative_stock')) {
                    if ($productionStock->available_quantity <= 0) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$idx.assigned_quantity" => "Stok available 0 untuk produk {$item->product->name}.",
                        ]);
                    }

                    if ($requestedAssignQty > $productionStock->available_quantity) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            // "items.$idx.assigned_quantity" => "Assigned quantity ($requested) melebihi stok available ({$productionStock->available_quantity}) untuk produk {$item->product->name}.",
                            "items.$idx.assigned_quantity" => "Assigned quantity ($requestedAssignQty) melebihi stok available ({$productionStock->available_quantity}) untuk produk {$item->product->name}.",
                        ]);
                    }
                }

                OrderProgressAssign::create([
                    'assign_batch_id'        => $batch->id,
                    'order_progress_item_id' => $item->id,
                    'product_id'             => $item->product_id,
                    // 🔹 mesin per produk
                    'machine_id'             => $machineId,
                    'assigned_quantity'      => $requestedAssignQty,
                    'completed_quantity'     => 0,
                    'note'                   => $data['note'] ?? null,
                ]);

                // 🔹 Mesin batch dipakai untuk grouping di Assign List:
                //    ambil dari produk pertama yang diassign.
                if (is_null($batch->machine_id)) {
                    $batch->update(['machine_id' => $machineId]);
                }

                // $productionStock = ProductionStock::firstOrCreate(
                //     [
                //         'product_id' => $item->product_id,
                //         'production_warehouse_id' => 2, // sesuaikan jika perlu
                //     ],
                //     [
                //         'opening_stock' => 0,
                //         'available_quantity' => 0,
                //         'finished_product_stock' => 0,
                //         'canceled_product_stock' => 0,
                //     ]
                // );

                $productionStock->decrement('available_quantity', $requestedAssignQty);
                $productionStock->decrement('pending_waiting_list', $requestedAssignQty);
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
            'machine',
            'assigns.progressItem'
        ])->findOrFail($batch_id);

        $machines = Machine::where('active', 1)->orderBy('name')->get();

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
            'machines'
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
            // 🟢 mesin dipilih per produk, bukan sekali per batch (per invoice)
            'items.*.machine_id'             => 'nullable|exists:machines,id',
            'items.*.assigned_quantity'      => 'required_if:items.*.bypass,0|integer|min:0',
            'items.*.note'                   => 'nullable|string',
            'items.*.bypass'                 => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $batch = OrderProgressAssignBatch::findOrFail($batch_id);

            // 🔹 Update batch utama (mesin batch diisi ulang dari produk pertama di bawah)
            $batch->update([
                'assign_date' => $request->assign_date,
                'note'        => $request->note,
            ]);

            $firstMachineId = null;

            foreach ($request->items as $idx => $data) {
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

                // 🔧 mesin wajib untuk setiap produk yang diassign
                if (empty($data['machine_id'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.machine_id" => "Mesin wajib dipilih untuk setiap produk yang diassign.",
                    ]);
                }

                $machineId = (int) $data['machine_id'];

                if (is_null($firstMachineId)) {
                    $firstMachineId = $machineId;
                }

                $item = OrderProgressItem::with('product')->findOrFail($data['order_progress_item_id']);

                $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $requested = (int) $data['assigned_quantity'];
                $requestedAssignQty = $requested * $unitConversionValue;

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
                $assignedQty = min($requestedAssignQty, $remaining);

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
                            'machine_id'        => $machineId,
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
                        'machine_id'             => $machineId,
                        'assigned_quantity'      => $assignedQty,
                        'note'                   => $data['note'] ?? null,
                    ]);

                    $productionStock->decrement('available_quantity', $assignedQty);
                    $productionStock->decrement('pending_waiting_list', $assignedQty);
                }
            }

            // 🔹 Mesin batch dipakai untuk grouping di Assign List:
            //    ambil dari produk pertama yang diassign.
            $batch->update(['machine_id' => $firstMachineId]);

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

    /**
     * 🔧 Query batch yang sudah kena semua filter listing (status, pencarian, tanggal).
     *
     * Mesin sekarang dipilih per produk, jadi query ini cuma dipakai sebagai
     * daftar batch yang lolos filter — pemecahan per mesin dilakukan di
     * masing-masing endpoint.
     */
    private function filteredBatchQuery(Request $request)
    {
        $batches = OrderProgressAssignBatch::query()
            ->select('order_progress_assign_batches.id');

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
                    $sub->where('name', 'like', "%{$productKeyword}%")
                        ->orWhere('sku', 'like', "%{$productKeyword}%");
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

        return $batches;
    }

    public function dataAssignList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $batches = $this->filteredBatchQuery($request);

        // 🔹 Urutan tanggal mengikuti filter status
        $dateDirection = 'desc'; // ⬅️ default = terbaru dulu

        if ($request->filled('progress_status') && $request->progress_status === 'progress') {
            $dateDirection = 'asc'; // ⬅️ terlama dulu
        }

        // ✅ Baris listing = kombinasi batch × mesin, diurutkan per mesin lalu tanggal.
        //    Batch tanpa mesin (data lama) ditaruh paling bawah.
        $rows = DB::table('order_progress_assigns as opa')
            ->join('order_progress_assign_batches as b', 'b.id', '=', 'opa.assign_batch_id')
            ->leftJoin('machines as m', 'm.id', '=', 'opa.machine_id')
            ->whereNull('opa.deleted_at')
            ->whereIn('opa.assign_batch_id', $batches->toBase())
            ->groupBy('opa.assign_batch_id', 'opa.machine_id', 'm.name', 'b.assign_date')
            ->select([
                'opa.assign_batch_id',
                'opa.machine_id',
                'm.name as machine_name',
                'b.assign_date',
            ])
            ->orderByRaw('m.name IS NULL ASC')
            ->orderBy('m.name', 'asc')
            ->orderBy('b.assign_date', $dateDirection)
            // 🔹 tiebreaker biar urutannya stabil saat lazy-load per halaman
            ->orderBy('opa.assign_batch_id', $dateDirection);

        // 🔹 Halaman Detail: hanya mesin tertentu ('none' = assign tanpa mesin)
        if ($request->filled('machine_id')) {
            if ($request->machine_id === 'none') {
                $rows->whereNull('opa.machine_id');
            } else {
                $rows->where('opa.machine_id', (int) $request->machine_id);
            }
        }

        // ✅ Ambil satu baris lebih banyak untuk menentukan has_more (tanpa query count)
        $pageRows = $rows->skip($start)->take($length + 1)->get();

        $hasMore = $pageRows->count() > $length;
        $pageRows = $pageRows->take($length);

        // ✅ Muat batch-nya sekali saja untuk semua baris di halaman ini
        $batchModels = OrderProgressAssignBatch::query()
            ->whereIn('id', $pageRows->pluck('assign_batch_id')->unique()->values())
            ->with([
                'machine',
                'assigns.machine',
                'assigns.progressItem.product',
                'assigns.progressItem.designItem',
                'assigns.progressItem.progress.order',
                'orderProgress.order.customer',
                'orderProgress.order.customerAddress',
            ])
            ->get()
            ->keyBy('id');

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $pageRows->map(function ($row) use ($batchModels) {
                $batch = $batchModels->get($row->assign_batch_id);

                if (!$batch) {
                    return null;
                }

                $date = Carbon::parse($batch->created_at)->format('d M y H:i');

                // 🧾 Assign Code + Date
                // $assignCodeHtml = "<div><div>{$batch->assign_code}</div><small class='text-muted'>{$date}</small></div>";
                $mode = $batch->orderProgress?->order?->mode;
                $modeBadge = '';
                if ($mode === 'printing') {
                    $modeBadge = '<div><span class="badge bg-soft-info text-info mb-1">Printing</span></div>';
                } elseif ($mode === 'polosan') {
                    $modeBadge = '<div><span class="badge bg-soft-warning text-warning mb-1">Polosan</span></div>';
                }

                $assignCodeHtml = $modeBadge . "<div><div>{$batch->assign_code}</div><small class='text-muted'>{$date}</small></div>";

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

                // 📦 Hanya produk yang diassign ke mesin baris ini
                $machineId = $row->machine_id;

                $assigns = $batch->assigns->filter(function ($assign) use ($machineId) {
                    return is_null($machineId)
                        ? is_null($assign->machine_id)
                        : (int) $assign->machine_id === (int) $machineId;
                })->values();

                // ⚙️ Total item, quantity, mesin (mesin sekarang per produk)
                $totalItems = $assigns->count();
                $totalQuantity = number_format($assigns->sum('assigned_quantity'), 0, ',', '.');
                $machineName = $row->machine_name ?? 'Tanpa Mesin';
                $note = e($batch->note ?? '-');

                $assignProducts = view('erp.pages.production.assign-list.partials.assign-product', compact('assigns'))->render();

                // ⚙️ Action button partial (aksi tetap per batch, bukan per mesin)
                $allCompleted = $batch->assigns->every(function ($assign) {
                    return $assign->change_quantity >= $assign->assigned_quantity;
                });

                $hasOnlyProgressStatus = $batch->assigns
                    ->every(fn ($assign) => $assign->status === 'progress');

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
                    // dipakai untuk group header di tabel Assign List
                    'machine_id' => $machineId,
                    'machine' => e($machineName),
                    'note' => $note,
                    'assign_products' => $assignProducts,
                    'action' => $action,
                    'order_notes' => $orderNotes,
                    'created_at' => $date,
                ];
            })->filter()->values(),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * 🔧 Listing utama Assign List: satu baris per MESIN.
     *
     * Isi barisnya = tabel customer/invoice beserta produk yang diassign ke
     * mesin tersebut. Detail per invoice ada di halaman Detail.
     */
    public function dataAssignListMachines(Request $request)
    {
        $length = (int) $request->input('length', 50);
        $start  = (int) $request->input('start', 0);

        // 🔹 Daftar mesin yang punya assign. Mesin kosong (data lama) paling bawah.
        $machineRows = DB::table('order_progress_assigns as opa')
            ->leftJoin('machines as m', 'm.id', '=', 'opa.machine_id')
            ->whereNull('opa.deleted_at')
            ->whereIn('opa.assign_batch_id', $this->filteredBatchQuery($request)->toBase())
            ->groupBy('opa.machine_id', 'm.name')
            ->select([
                'opa.machine_id',
                'm.name as machine_name',
            ])
            ->orderByRaw('m.name IS NULL ASC')
            ->orderBy('m.name', 'asc')
            ->skip($start)
            ->take($length + 1)
            ->get();

        $hasMore = $machineRows->count() > $length;
        $machineRows = $machineRows->take($length);

        $assignsByMachine = $this->assignsGroupedByMachine($request, $machineRows->pluck('machine_id'));
        $query = $this->listingQueryString($request);

        return response()->json([
            'data' => $machineRows->map(function ($row) use ($assignsByMachine, $query) {
                $machineKey  = $row->machine_id ?? 'none';
                $machineName = $row->machine_name ?? 'Tanpa Mesin';

                $assigns = $assignsByMachine->get($machineKey, collect());
                $groups  = $this->groupAssignsByBatch($assigns);

                $assignProducts = view('erp.pages.production.assign-list.partials.machine-assign-table', compact('groups'))->render();

                $action = view('erp.pages.production.assign-list.partials.machine-action-button', compact(
                    'machineKey',
                    'machineName',
                    'query'
                ))->render();

                return [
                    'machine_id'      => $row->machine_id,
                    'machine'         => e($machineName),
                    'total_items'     => $assigns->count(),
                    'total_quantity'  => number_format($assigns->sum('assigned_quantity'), 0, ',', '.'),
                    'total_invoice'   => $groups->count(),
                    'assign_products' => $assignProducts,
                    'action'          => $action,
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * 🔧 Halaman Detail: assign list per invoice, khusus mesin yang dipilih.
     */
    public function machineDetail(Request $request, $machine)
    {
        $machineKey  = $machine === 'none' ? 'none' : (int) $machine;
        $machineName = $this->machineName($machineKey);

        return view('erp.pages.production.assign-list.assign-list-detail', compact(
            'machineKey',
            'machineName'
        ));
    }

    /**
     * 🖨️ Cetak daftar kerja satu mesin ke printer thermal (struk).
     */
    public function printMachine(Request $request, $machine)
    {
        $machineKey  = $machine === 'none' ? 'none' : (int) $machine;
        $machineName = $this->machineName($machineKey);

        // 🔹 Default hanya yang masih berjalan, biar struk tidak ikut yang sudah selesai
        $request->merge([
            'progress_status' => $request->input('progress_status', 'progress'),
        ]);

        $machineId = $machineKey === 'none' ? null : $machineKey;

        $assigns = $this->assignsGroupedByMachine($request, collect([$machineId]))
            ->get($machineKey, collect());

        $groups = $this->groupAssignsByBatch($assigns);

        $paperWidth = (int) $request->input('paper', 80); // mm, 80 atau 58

        return view('erp.pages.production.assign-list.print-machine-assign', compact(
            'machineName',
            'groups',
            'paperWidth'
        ));
    }

    private function machineName($machineKey): string
    {
        if ($machineKey === 'none') {
            return 'Tanpa Mesin';
        }

        return Machine::withTrashed()->findOrFail($machineKey)->name;
    }

    /**
     * 🔹 Ambil assign (yang lolos filter listing) untuk sekumpulan mesin,
     *    dikelompokkan per mesin. Key 'none' = assign tanpa mesin.
     */
    private function assignsGroupedByMachine(Request $request, $machineIds)
    {
        $machineIds  = collect($machineIds);
        $ids         = $machineIds->filter(fn($id) => !is_null($id))->values();
        $includeNull = $machineIds->contains(null);

        if ($ids->isEmpty() && !$includeNull) {
            return collect();
        }

        return OrderProgressAssign::query()
            ->whereIn('assign_batch_id', $this->filteredBatchQuery($request)->toBase())
            ->where(function ($q) use ($ids, $includeNull) {
                if ($ids->isNotEmpty()) {
                    $q->orWhereIn('machine_id', $ids);
                }

                if ($includeNull) {
                    $q->orWhereNull('machine_id');
                }
            })
            ->with([
                'progressItem.product',
                'progressItem.designItem',
                'batch.orderProgress.order.customer',
                'batch.orderProgress.order.customerAddress',
            ])
            ->get()
            ->groupBy(fn($assign) => $assign->machine_id ?? 'none');
    }

    /**
     * 🔹 Kelompokkan assign per batch (satu invoice = satu blok di tabel customer).
     */
    private function groupAssignsByBatch($assigns)
    {
        return collect($assigns)
            ->groupBy('assign_batch_id')
            ->map(fn($items) => [
                'batch'   => $items->first()->batch,
                'assigns' => $items->values(),
            ])
            ->sortBy(fn($group) => optional($group['batch'])->assign_date . '|' . optional($group['batch'])->id)
            ->values();
    }

    /**
     * 🔹 Query string filter listing, dipakai untuk link Print & Detail.
     */
    private function listingQueryString(Request $request): string
    {
        $params = array_filter(
            $request->only([
                'filter',
                'start_date',
                'end_date',
                'progress_status',
                'search_type',
                'search_keyword',
                'search_product',
            ]),
            fn($value) => $value !== null && $value !== ''
        );

        return $params ? '?' . http_build_query($params) : '';
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
