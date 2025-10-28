<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressItem;
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

        $operators = Operator::where('active', 1)->orderBy('name')->get();

        // 🔹 Generate assign code otomatis lewat service
        $assignCode = AssignCode::generateAssignCode();

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
            'items.*.operator_id'            => 'required|exists:operators,id',
            'items.*.assigned_quantity'      => 'required|integer|min:1',
            'items.*.note'                   => 'nullable|string',
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
                if (empty($data['assigned_quantity']) || (int)$data['assigned_quantity'] <= 0) {
                    continue;
                }

                /** @var \App\Models\OrderProgressItem $item */
                $item = OrderProgressItem::query()
                    ->withSum('assigns as total_completed', 'completed_quantity') // alias total_completed
                    ->findOrFail($data['order_progress_item_id']);

                $quantity          = (int) $item->quantity;
                $completed         = (int) ($item->total_completed ?? 0); // total completed dari semua assign
                $remainingAllowed  = max($quantity - $completed, 0);      // <= inilah batas yang boleh di-assign lagi
                $requested         = (int) $data['assigned_quantity'];

                // ❗ HARD RULE: tidak boleh lebih dari remaining
                if ($requested > $remainingAllowed) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.assigned_quantity" => "Assigned quantity ($requested) melebihi remaining ($remainingAllowed) untuk produk {$item->product->name}.",
                    ]);
                }

                // kalau remaining 0, skip
                if ($remainingAllowed <= 0) {
                    continue;
                }

                OrderProgressAssign::create([
                    'assign_batch_id'        => $batch->id,
                    'order_progress_item_id' => $item->id,
                    'operator_id'            => (int) $data['operator_id'],
                    'assigned_quantity'      => $requested, // aman karena <= remaining
                    'completed_quantity'     => 0,
                    // ❌ defect_quantity jangan disimpan di tabel assign (itu milik progress)
                    'note'                   => $data['note'] ?? null,
                ]);
            }

            DB::commit();

            return redirect('/erp/productions/waiting-list/assign-list')
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

        return view('erp.pages.production.assign-list.edit-assign', compact(
            'batch',
            'operators'
        ));
    }

    public function update(Request $request, $batch_id)
    {
        $request->validate([
            'assign_date' => 'required|date',
            'note'        => 'nullable|string',
            'items'       => 'required|array',
            'items.*.id'  => 'nullable|exists:order_progress_assigns,id',
            'items.*.order_progress_item_id' => 'required|exists:order_progress_items,id',
            'items.*.operator_id'            => 'required|exists:operators,id',
            'items.*.assigned_quantity'      => 'required|integer|min:1',
            'items.*.note'                   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $batch = OrderProgressAssignBatch::findOrFail($batch_id);

            // 🔹 Update batch info
            $batch->update([
                'assign_date' => $request->assign_date,
                'note'        => $request->note,
            ]);

            // 🔹 Update atau tambah assign baru
            foreach ($request->items as $data) {
                if (empty($data['assigned_quantity']) || $data['assigned_quantity'] <= 0) continue;

                $item = OrderProgressItem::findOrFail($data['order_progress_item_id']);
                $alreadyAssigned = $item->assigns()
                    ->where('assign_batch_id', '!=', $batch->id)
                    ->sum('assigned_quantity');
                $remaining = $item->quantity - $alreadyAssigned;
                $assignedQty = min($data['assigned_quantity'], $remaining);

                if ($assignedQty <= 0) continue;

                // Update existing or create new
                OrderProgressAssign::updateOrCreate(
                    [
                        'id' => $data['id'] ?? null,
                        'assign_batch_id' => $batch->id,
                        'order_progress_item_id' => $item->id,
                    ],
                    [
                        'operator_id' => $data['operator_id'],
                        'assigned_quantity' => $assignedQty,
                        'note' => $data['note'] ?? null,
                    ]
                );
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

    public function getAssignList()
    {
        return view('erp.pages.production.assign-list.assign-list');
    }

    public function dataAssignList(Request $request)
    {
        $batches = OrderProgressAssignBatch::with(['assigns.operator', 'orderProgress.order.customer']);

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
        if ($request->filled('search_keyword')) {
            $keyword = trim($request->search_keyword);
            $batches->where('assign_code', 'like', "%{$keyword}%");
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

        $batches = $batches->latest()->get();

        return DataTables::of($batches)
            ->addIndexColumn()
            ->addColumn('assign_code', function ($batch) {
                $date = Carbon::parse($batch->assign_date)->format('d M y');
                return "<div><div>{$batch->assign_code}</div><small class='text-muted'>{$date}</small></div>";
            })
            ->addColumn('order_info', function ($batch) {
                $order = $batch->orderProgress->order ?? null;
                if (!$order) return '-';
                $customerName = $order->customer ? $order->customer->name : 'Unknown';
                return "<div>
                        <div>" . ($order->order_number ?? '') . "</div>
                        <small class='text-muted'>{$customerName}</small>
                    </div>";
            })
            ->addColumn('total_items', fn($batch) => $batch->assigns->count())
            ->addColumn('total_quantity', fn($batch) => number_format($batch->assigns->sum('assigned_quantity'), 0, ',', '.'))
            ->addColumn('operators', fn($batch) => $batch->assigns->pluck('operator.name')->unique()->implode(', ') ?: '-')
            ->addColumn('note', fn($batch) => e($batch->note ?? '-'))
            ->addColumn('assign_products', function ($batch) {
                $assigns = OrderProgressAssign::with(['operator', 'progressItem.product'])
                    ->where('assign_batch_id', $batch->id)
                    ->get();
                return view('erp.pages.production.assign-list.partials.assign-product', compact('assigns'))->render();
            })
            ->addColumn('action', function ($batch) {
                $allCompleted = $batch->assigns->every(function ($assign) {
                    return $assign->change_quantity >= $assign->assigned_quantity;
                });

                return view('erp.pages.production.assign-list.partials.assign-action-button', compact('batch', 'allCompleted'))->render();
            })
            ->rawColumns(['assign_code', 'assign_products', 'order_info', 'action'])
            ->make(true);
    }
}
