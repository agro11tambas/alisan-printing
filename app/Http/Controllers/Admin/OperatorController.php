<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OperatorController extends Controller
{
    public function getOperators()
    {
        return view('erp.pages.operators.operator');
    }

    // public function dataOperators(Request $request)
    // {
    //     $query = Operator::query()
    //         ->withSum('histories as total_completed', 'completed_quantity')
    //         ->withSum('histories as total_defect', 'defect_quantity')
    //         ->withSum('histories as total_reject', 'reject_quantity');

    //     if ($request->filled('name')) {
    //         $query->where('name', 'like', '%' . $request->name . '%');
    //     }

    //     return DataTables::of($query)
    //         ->addIndexColumn()
    //         ->addColumn('status', function ($row) {
    //             return $row->active
    //                 ? '<span class="badge bg-success">Active</span>'
    //                 : '<span class="badge bg-secondary">Inactive</span>';
    //         })
    //         ->addColumn('completed', function ($row) {
    //             return '<span class="fw-bold text-success">' . number_format($row->total_completed ?? 0) . '</span>';
    //         })
    //         ->addColumn('defect_progress', function ($row) {
    //             return '<span class="fw-bold text-warning">' . number_format($row->total_defect ?? 0) . '</span>';
    //         })
    //         ->addColumn('reject_progress', function ($row) {
    //             return '<span class="fw-bold text-danger">' . number_format($row->total_reject ?? 0) . '</span>';
    //         })
    //         ->addColumn('total_progress', function ($row) {
    //             $total = ($row->total_completed ?? 0) + ($row->total_defect ?? 0) + ($row->total_reject ?? 0);
    //             return '<span class="fw-bold">' . number_format($total) . '</span>';
    //         })
    //         ->addColumn('action', function ($row) {
    //             return view('erp.pages.operators.partials.action-button', compact('row'))->render();
    //         })
    //         ->rawColumns(['status', 'completed', 'defect_progress', 'reject_progress', 'total_progress', 'action'])
    //         ->make(true);
    // }

    public function dataOperators(Request $request)
    {
        $query = Operator::query();

        // --- filter tanggal histories ---
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $start = Carbon::today();
                    $end = Carbon::tomorrow();
                    break;
                case 'last_7_days':
                    $start = Carbon::now()->subDays(7);
                    $end = Carbon::now();
                    break;
                case 'this_month':
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    break;
                case 'last_30_days':
                    $start = Carbon::now()->subDays(30);
                    $end = Carbon::now();
                    break;
                case 'year_to_date':
                    $start = Carbon::now()->startOfYear();
                    $end = Carbon::now();
                    break;
                case 'yearly':
                    $start = Carbon::now()->startOfYear();
                    $end = Carbon::now()->endOfYear();
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $start = Carbon::parse($request->start_date);
                        $end = Carbon::parse($request->end_date)->endOfDay();
                    }
                    break;
            }
        }

        // --- ambil id produk kategori Sablon ---
        $productIds = DB::table('product_category_product')
            ->join('product_categories', 'product_categories.id', '=', 'product_category_product.category_id')
            ->where('product_categories.name', 'Sablon')
            ->pluck('product_category_product.product_id');

        // --- subquery total berdasarkan kategori dan tanggal ---
        $subCompleted = DB::table('order_progress_histories_2')
            ->join('order_progress_items', 'order_progress_items.id', '=', 'order_progress_histories_2.order_progress_item_id')
            ->whereIn('order_progress_items.product_id', $productIds)
            ->when(isset($start) && isset($end), fn ($q) => $q->whereBetween('order_progress_histories_2.created_at', [$start, $end]))
            ->selectRaw('SUM(order_progress_histories_2.completed_quantity)')
            ->whereColumn('order_progress_histories_2.operator_id', 'operators.id');

        $subDefect = DB::table('order_progress_histories_2')
            ->join('order_progress_items', 'order_progress_items.id', '=', 'order_progress_histories_2.order_progress_item_id')
            ->whereIn('order_progress_items.product_id', $productIds)
            ->when(isset($start) && isset($end), fn ($q) => $q->whereBetween('order_progress_histories_2.created_at', [$start, $end]))
            ->selectRaw('SUM(order_progress_histories_2.defect_quantity)')
            ->whereColumn('order_progress_histories_2.operator_id', 'operators.id');

        $subReject = DB::table('order_progress_histories_2')
            ->join('order_progress_items', 'order_progress_items.id', '=', 'order_progress_histories_2.order_progress_item_id')
            ->whereIn('order_progress_items.product_id', $productIds)
            ->when(isset($start) && isset($end), fn ($q) => $q->whereBetween('order_progress_histories_2.created_at', [$start, $end]))
            ->selectRaw('SUM(order_progress_histories_2.reject_quantity)')
            ->whereColumn('order_progress_histories_2.operator_id', 'operators.id');

        $query->addSelect([
            'operators.*',
            'total_completed' => $subCompleted,
            'total_defect' => $subDefect,
            'total_reject' => $subReject,
        ]);

        // --- filter nama operator ---
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // --- return ke DataTables ---
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn(
                'status',
                fn ($row) => $row->active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>'
            )
            ->addColumn(
                'completed',
                fn ($row) => '<span class="fw-bold text-success">'.number_format($row->total_completed ?? 0).'</span>'
            )
            ->addColumn(
                'defect_progress',
                fn ($row) => '<span class="fw-bold text-warning">'.number_format($row->total_defect ?? 0).'</span>'
            )
            ->addColumn(
                'reject_progress',
                fn ($row) => '<span class="fw-bold text-danger">'.number_format($row->total_reject ?? 0).'</span>'
            )
            ->addColumn('total_progress', function ($row) {
                $total = ($row->total_completed ?? 0) + ($row->total_defect ?? 0) + ($row->total_reject ?? 0);

                return '<span class="fw-bold">'.number_format($total).'</span>';
            })
            ->addColumn(
                'action',
                fn ($row) => view('erp.pages.operators.partials.action-button', compact('row'))->render()
            )
            ->rawColumns(['status', 'completed', 'defect_progress', 'reject_progress', 'total_progress', 'action'])
            ->make(true);
    }

    // public function show($id)
    // {
    //     $operator = Operator::with([
    //         'histories.progressItem.product' => function ($q) {
    //             $q->withTrashed(); // kalau produk sudah dihapus
    //         },
    //     ])->findOrFail($id);

    //     // Gabungkan produk yang pernah di-progress
    //     $products = $operator->histories
    //         ->filter(fn($h) => $h->progressItem && $h->progressItem->product)
    //         ->groupBy('progressItem.product_id')
    //         ->map(function ($group) {
    //             $product = $group->first()->progressItem->product;
    //             $completed = $group->sum('completed_quantity');
    //             $defect = $group->sum('defect_quantity');
    //             $reject = $group->sum('reject_quantity');

    //             return [
    //                 'product_name' => $product->name ?? '-',
    //                 'sku' => $product->sku ?? '-',
    //                 'completed' => $completed,
    //                 'defect' => $defect,
    //                 'reject' => $reject,
    //             ];
    //         });

    //     return view('erp.pages.operators.detail', compact('operator', 'products'));
    // }

    // public function show($id)
    // {
    //     $operator = Operator::with([
    //         'histories.progressItem.product' => function ($q) {
    //             $q->withTrashed(); // kalau produk sudah dihapus
    //         },
    //     ])->findOrFail($id);

    //     // Gabungkan produk yang pernah di-progress
    //     $products = $operator->histories
    //         ->filter(fn($h) => $h->progressItem && $h->progressItem->product)
    //         ->groupBy('progressItem.product_id')
    //         ->map(function ($group) {
    //             $product = $group->first()->progressItem->product;
    //             $completed = $group->sum('completed_quantity');
    //             $defect = $group->sum('defect_quantity');
    //             $reject = $group->sum('reject_quantity');

    //             return [
    //                 'product_name' => $product->name ?? '-',
    //                 'sku' => $product->sku ?? '-',
    //                 'completed' => $completed,
    //                 'defect' => $defect,
    //                 'reject' => $reject,
    //             ];
    //         });

    //     // 🔹 Cek apakah permintaan JSON (lazy load API)
    //     if (request()->wantsJson()) {
    //         $length = (int) request()->input('length', 15);
    //         $start = (int) request()->input('start', 0);

    //         $total = $products->count();
    //         $paginated = $products->slice($start, $length)->values();

    //         return response()->json([
    //             'operator' => [
    //                 'id' => $operator->id,
    //                 'name' => $operator->name,
    //                 'role' => $operator->role,
    //             ],
    //             'data' => $paginated,
    //             'has_more' => $total > ($start + $length),
    //         ]);
    //     }

    //     // 🔹 View mode (aslinya tetap)
    //     return view('erp.pages.operators.detail', compact('operator', 'products'));
    // }

    public function show($id)
    {
        $operator = Operator::with([
            'histories.progressItem.product' => function ($q) {
                $q->withTrashed(); // kalau produk sudah dihapus
            },
        ])->findOrFail($id);

        // 🔹 View mode (aslinya tetap)
        return view('erp.pages.operators.detail', compact('operator'));
    }

    public function dataShow(Request $request, $id)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $operator = Operator::findOrFail($id);

        // ✅ Filter tanggal histories
        $histories = $operator->histories()->with(['progressItem.product' => fn ($q) => $q->withTrashed()]);
        if ($request->filled('filter')) {
            $filter = $request->filter;
            switch ($filter) {
                case 'today':
                    $histories->whereDate('created_at', Carbon::today());
                    break;
                case 'last_7_days':
                    $histories->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $histories->whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $histories->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $histories->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $histories->whereBetween('created_at', [
                            $request->start_date,
                            Carbon::parse($request->end_date)->endOfDay(),
                        ]);
                    }
                    break;
            }
        }

        $products = $histories->get()
            ->filter(fn ($h) => $h->progressItem && $h->progressItem->product)
            ->groupBy('progressItem.product_id')
            ->map(function ($group) {
                $product = $group->first()->progressItem->product;

                return [
                    'product_name' => $product->name ?? '-',
                    'sku' => $product->sku ?? '-',
                    'completed' => $group->sum('completed_quantity'),
                    'defect' => $group->sum('defect_quantity'),
                    'reject' => $group->sum('reject_quantity'),
                    'total' => $group->sum('completed_quantity') + $group->sum('defect_quantity') + $group->sum('reject_quantity'),
                    'last_date' => optional($group->max('created_at'))->format('Y-m-d H:i'),
                ];
            })
            ->values();

        $totalData = $products->count();
        $data = $products->slice($start, $length)->values();

        return response()->json([
            'data' => $data,
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create()
    {
        return view('erp.pages.operators.create-operator');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        Operator::create($request->only(['name', 'active']));

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $operator = Operator::findOrFail($id);

        return view('erp.pages.operators.edit-operator', compact('operator'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        $operator = Operator::findOrFail($id);

        $operator->update($request->only(['name', 'active']));

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil diperbarui.');
    }

    public function delete($id)
    {
        $operator = Operator::findOrFail($id);

        $operator->delete();

        return redirect('/erp/shop-manager/operators')->with('success', 'Operator berhasil dihapus.');
    }
}
