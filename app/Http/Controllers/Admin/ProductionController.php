<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function getStockIn()
    {
        return view('erp.pages.production.stock-in.stock-in');
    }

    // public function dataStockIn(Request $request)
    // {
    //     $length = (int) $request->input('length', 15);
    //     $start = (int) $request->input('start', 0);

    //     $inventory = Inventory::with([
    //         'items',
    //         'purchase.supplier',
    //         'order.customer',
    //         'saleReturn.customer',
    //         'materialRequest',
    //     ])
    //         ->where('status', 'Stock In Production')
    //         ->orderByDesc('id');

    //     // ✅ Filter tanggal
    //     if ($request->filter) {
    //         switch ($request->filter) {
    //             case 'today':
    //                 $inventory->whereDate('date', Carbon::today());
    //                 break;
    //             case 'last_7_days':
    //                 $inventory->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
    //                 break;
    //             case 'this_month':
    //                 $inventory->whereMonth('date', Carbon::now()->month)
    //                     ->whereYear('date', Carbon::now()->year);
    //                 break;
    //             case 'last_30_days':
    //                 $inventory->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
    //                 break;
    //             case 'year_to_date':
    //                 $inventory->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
    //                 break;
    //             case 'yearly':
    //                 $inventory->whereYear('date', Carbon::now()->year);
    //                 break;
    //             case 'custom':
    //                 if ($request->filled('start_date') && $request->filled('end_date')) {
    //                     $inventory->whereBetween('date', [$request->start_date, $request->end_date]);
    //                 }
    //                 break;
    //         }
    //     }

    //     // ✅ Filter pencarian
    //     if ($request->search_type && $request->filled('search_keyword')) {
    //         if ($request->search_type === 'invoice_number') {
    //             $inventory->where(function ($q) use ($request) {
    //                 $q->where('purchase_number', 'like', '%' . $request->search_keyword . '%')
    //                     ->orWhere('order_number', 'like', '%' . $request->search_keyword . '%');
    //             });
    //         } elseif ($request->search_type === 'partner') {
    //             $inventory->where(function ($q) use ($request) {
    //                 $q->whereHas('purchase.supplier', function ($query) use ($request) {
    //                     $query->where('name', 'like', '%' . $request->search_keyword . '%');
    //                 });

    //                 $q->orWhereHas('saleReturn.customer', function ($query) use ($request) {
    //                     $query->where('name', 'like', '%' . $request->search_keyword . '%');
    //                 });
    //             });
    //         }
    //     } elseif ($request->search_type === 'type' && $request->filled('search_type_dropdown')) {
    //         if ($request->search_type_dropdown === 'purchase') {
    //             $inventory->where('note', 'Purchase Account');
    //         } elseif ($request->search_type_dropdown === 'sale_return') {
    //             $inventory->where('note', 'Sale Returns');
    //         }
    //     }

    //     if ($request->filled('search_product')) {
    //         $productKeyword = trim(strtolower($request->search_product));

    //         $inventory->whereHas('items.product', function ($q) use ($productKeyword) {
    //             // gunakan COLLATE biar bisa handle tanda kurung
    //             $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
    //         });
    //     }

    //     // ✅ Filter progress status
    //     if ($request->filled('progress_status')) {
    //         if ($request->progress_status === 'completed') {
    //             $inventory->whereDoesntHave('items', function ($q) {
    //                 $q->whereColumn('stock_in', '<', 'quantity');
    //             });
    //         } elseif ($request->progress_status === 'progress') {
    //             $inventory->whereHas('items', function ($q) {
    //                 $q->whereColumn('stock_in', '<', 'quantity');
    //             });
    //         }
    //     }

    //     // ✅ Hitung total data sebelum pagination
    //     $totalQuery = clone $inventory;
    //     $totalData = $totalQuery->count();

    //     // ✅ Ambil data sesuai offset dan limit
    //     $data = $inventory->skip($start)->take($length)->get();

    //     // ✅ Format JSON ringan (lazy-load)
    //     return response()->json([
    //         'data' => $data->map(function ($inventory) {
    //             // 🧾 Transaction number + badge
    //             if ($inventory->purchase_id) {
    //                 $badge = '<span class="badge bg-soft-success text-success mb-1">Purchase</span>';
    //                 $number = e($inventory->purchase->purchase_number ?? '-');
    //             } elseif ($inventory->canceled_product_id) {
    //                 $badge = '<span class="badge bg-soft-warning text-warning mb-1">Canceled Product</span>';
    //                 $number = e($inventory->order_number ?? '-');
    //             } elseif ($inventory->sale_return_id) {
    //                 $badge = '<span class="badge bg-soft-danger text-danger mb-1">Sale Returns</span>';
    //                 $number = e($inventory->order_number ?? '-');
    //             } elseif ($inventory->material_request_id) {
    //                 $badge = '<span class="badge bg-soft-primary text-primary mb-1">Material Request</span>';
    //                 $number = e($inventory->material_request_number ?? '-');
    //             } else {
    //                 $badge = '';
    //                 $number = '-';
    //             }

    //             // 📅 Date
    //             $date = Carbon::parse($inventory->created_at)->format('d M y H:i');

    //             // 🔥 Gabungkan seperti sale list
    //             $transactionDisplay = '
    //                 <div>
    //                     <div>' . $badge . '</div>
    //                     <div class="fw-semibold">' . $number . '</div>
    //                     <small class="text-muted">' . $date . '</small>
    //                 </div>
    //             ';

    //             // 👤 Partner
    //             if ($inventory->purchase_id) {
    //                 $partner = e(optional($inventory->purchase->supplier)->name ?? '-');
    //             } elseif ($inventory->production_stock_id) {
    //                 $partner = 'Production';
    //             } elseif ($inventory->sale_return_id) {
    //                 $partner = e(optional($inventory->saleReturn->customer)->name ?? '-');
    //             } else {
    //                 $partner = '-';
    //             }

    //             // 📦 Stock in partial
    //             $stockInHtml = view('erp.pages.production.stock-in.partials.product-stock-in', compact('inventory'))->render();

    //             // ⚙️ Action partial
    //             $actionHtml = view('erp.pages.production.stock-in.partials.action-button-stock-in', compact('inventory'))->render();

    //             return [
    //                 'id' => $inventory->id,
    //                 'transaction_number' => $transactionDisplay,
    //                 'date' => $date,
    //                 'partner_name' => $partner,
    //                 'stock_in' => $stockInHtml,
    //                 'action' => $actionHtml,
    //             ];
    //         }),
    //         'has_more' => $totalData > ($start + $length),
    //     ]);
    // }

    public function dataStockIn(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start  = (int) $request->input('start', 0);

        $inventory = Inventory::with([
            'items',
            'purchase.supplier',
            'purchase.parentPurchase',
            'order.customer',
            'saleReturn.customer',
            'materialRequest',
        ])
            ->where('status', 'Stock In Production')
            ->orderByDesc('id');

        // ✅ Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $inventory->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $inventory->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $inventory->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $inventory->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $inventory->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $inventory->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $inventory->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // ✅ Filter pencarian
        if ($request->search_type && $request->filled('search_keyword')) {
            if ($request->search_type === 'invoice_number') {
                $inventory->where(function ($q) use ($request) {
                    $q->where('purchase_number', 'like', '%' . $request->search_keyword . '%')
                        ->orWhere('order_number', 'like', '%' . $request->search_keyword . '%')
                        ->orWhereHas('purchase.parentPurchase', function ($query) use ($request) {
                            $query->where('purchase_number', 'like', '%' . $request->search_keyword . '%');
                        });
                });
            } elseif ($request->search_type === 'partner') {
                $inventory->where(function ($q) use ($request) {
                    $q->whereHas('purchase.supplier', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });
                    $q->orWhereHas('saleReturn.customer', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });
                });
            }
        } elseif ($request->search_type === 'type' && $request->filled('search_type_dropdown')) {
            if ($request->search_type_dropdown === 'purchase') {
                $inventory->where('note', 'Purchase Account');
            } elseif ($request->search_type_dropdown === 'sale_return') {
                $inventory->where('note', 'Sale Returns');
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));
            $inventory->whereHas('items.product', function ($q) use ($productKeyword) {
                $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
            });
        }

        // ✅ Ambil data
        // $data = $inventory->skip($start)->take($length)->get();
        $allData = $inventory->get();

        // ✅ Group by supplier + purchase_date year-month
        // $grouped = $data->groupBy(function ($item) {
        //     $supplierId = optional($item->purchase->supplier ?? null)->id
        //         ?? ($item->sale_return_id ? 'return_' . (optional($item->saleReturn->customer)->id ?? 'unknown') : 'other');
        //     $month = Carbon::parse($item->purchase?->purchase_date ?? $item->created_at)->format('Y-m');
        //     return $supplierId . '_' . $month;
        // });

        /*
         * Grouping per Purchase List (PI) yang sebelumnya dipakai.
         * Dipertahankan agar flow PI bisa diaktifkan kembali bila dibutuhkan.
         *
         * $grouped = $allData->groupBy(function ($item) {
         *     return $item->purchase_id
         *         ? 'purchase_list_'.$item->purchase_id
         *         : 'inventory_'.$item->id;
         * });
         */
        $grouped = $allData->groupBy(function ($item) {
            if ($item->purchase_id) {
                return $item->purchase?->parent_purchase_id
                    ? 'purchase_order_'.$item->purchase->parent_purchase_id
                    : 'purchase_list_'.$item->purchase_id;
            }

            return 'inventory_'.$item->id;
        });


        // ✅ Filter progress_status setelah grouping
        if ($request->filled('progress_status')) {
            $grouped = $grouped->filter(function ($items) use ($request) {
                $allCompleted = $items->every(function ($inv) {
                    return $inv->items->every(fn($item) => $item->stock_in >= ($item->qty_base ?? $item->quantity));
                });

                if ($request->progress_status === 'completed') return $allCompleted;
                if ($request->progress_status === 'progress') return !$allCompleted;
                return true;
            });
        }

        $totalData = $grouped->count();
        $paginated = $grouped->slice($start, $length);

        return response()->json([
            'data' => $paginated->map(function ($items) {
                $first = $items->first();
                $purchaseOrder = $first->purchase?->parentPurchase;
                $groupDate = $purchaseOrder?->purchase_date
                    ?? $first->purchase?->purchase_date
                    ?? $first->created_at;

                // Partner + badge
                if ($first->purchase_id) {
                    $partner = e(optional($first->purchase->supplier)->name ?? '-');
                    $badge   = '<span class="badge bg-soft-success text-success mb-1">Purchase</span>';
                } elseif ($first->sale_return_id) {
                    $partner = e(optional($first->saleReturn->customer)->name ?? '-');
                    $badge   = '<span class="badge bg-soft-danger text-danger mb-1">Sale Returns</span>';
                } else {
                    $partner = '-';
                    $badge   = '';
                }

                // $month = Carbon::parse($first->purchase?->purchase_date ?? $first->created_at)->format('F Y');
                $dates = $items->map(fn($inv) => Carbon::parse($inv->purchase?->purchase_date ?? $inv->created_at));
                $month = $purchaseOrder
                    ? Carbon::parse($groupDate)->format('M Y')
                    : $dates->min()->format('M Y') . ' – ' . $dates->max()->format('M Y');

                // Merge items by product_id
                $mergedItems = $items->flatMap(fn($inv) => $inv->items)
                    ->groupBy('product_id')
                    ->map(function ($productItems) {
                        $first = $productItems->first();
                        return (object)[
                            'product'               => $first->product,
                            'unit_name'             => $first->unit_name ?? 'Pcs',
                            'unit_conversion_value' => $first->unit_conversion_value ?? 1,
                            'quantity'              => $productItems->sum('quantity'),
                            'qty_base'              => $productItems->sum('qty_base'),
                            'stock_in'              => $productItems->sum('stock_in'),
                        ];
                    })->values();

                $fakeInventory = (object)['items' => $mergedItems];

                $stockInHtml = view(
                    'erp.pages.production.stock-in.partials.product-stock-in',
                    ['inventory' => $fakeInventory]
                )->render();

                $isGroupCompleted = $mergedItems->every(fn($i) => $i->stock_in >= $i->qty_base); // ← qty_base
                $completeIcon     = $isGroupCompleted ? '<i class="fa fa-check-circle text-success ms-1"></i>' : '';

                $supplierId = $first->purchase?->supplier?->id;
                $purchaseOrderId = $purchaseOrder?->id;

                $actionHtml = view(
                    'erp.pages.production.stock-in.partials.action-button-stock-in',
                    [
                        'supplierId'  => $supplierId,
                        // 'year'        => $year,
                        // 'month'       => $monthNum,
                        'isCompleted' => $isGroupCompleted,
                        'inventory'   => $first,
                        'inventoryId' => $first->purchase_id && ! $purchaseOrderId ? $first->id : null,
                        'purchaseOrderId' => $purchaseOrderId,
                    ]
                )->render();

                $numbersList = $purchaseOrder
                    ? collect([$purchaseOrder->purchase_number])
                    : $items->map(fn($inv) => $inv->purchase->purchase_number ?? $inv->order_number ?? '-')
                        ->filter()->unique()->values();

                $numberHtml = $numbersList->implode('<br>');

                $transactionDisplay = '
                <div>
                    <div>' . $badge . '</div>
                    <div class="fw-semibold">' . $numberHtml . '</div>
                    <small class="text-muted">' . $month . ' (' . $items->count() . ' transactions)</small>
                </div>
            ';

                return [
                    'id'                 => $first->id,
                    'transaction_number' => $transactionDisplay,
                    'date'               => $month,
                    'date_raw'           => $groupDate,
                    'partner_name'       => $partner,
                    'stock_in'           => $stockInHtml,
                    'action'             => $actionHtml,
                ];
            })->values(),
            'has_more' => $totalData > ($start + $length),
        ]);
    }
}
