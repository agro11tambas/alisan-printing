<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefectProduct;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Products;
use App\Models\InventoryStockOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    public function getStockIn()
    {
        return view('erp.pages.inventory.stock-in.stock-in');
    }

    public function dataStockIn(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $inventory = Inventory::with([
            'items',
            'purchase.supplier',
            'order.customer',
            'saleReturn.customer',
            'materialRequest',
        ])
            ->where('status', 'Stock In')
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
                        ->orWhere('order_number', 'like', '%' . $request->search_keyword . '%');
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
                // gunakan COLLATE biar bisa handle tanda kurung
                $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
            });
        }

        // ✅ Filter progress status
        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $inventory->whereDoesntHave('items', function ($q) {
                    $q->whereColumn('stock_in', '<', 'quantity');
                });
            } elseif ($request->progress_status === 'progress') {
                $inventory->whereHas('items', function ($q) {
                    $q->whereColumn('stock_in', '<', 'quantity');
                });
            }
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $inventory;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $inventory->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($inventory) {
                // 🧾 Transaction number + badge
                if ($inventory->purchase_id) {
                    $badge = '<span class="badge bg-soft-success text-success mb-1">Purchase</span>';
                    $number = e($inventory->purchase->purchase_number ?? '-');
                } elseif ($inventory->canceled_product_id) {
                    $badge = '<span class="badge bg-soft-warning text-warning mb-1">Canceled Product</span>';
                    $number = e($inventory->order_number ?? '-');
                } elseif ($inventory->sale_return_id) {
                    $badge = '<span class="badge bg-soft-danger text-danger mb-1">Sale Returns</span>';
                    $number = e($inventory->order_number ?? '-');
                } elseif ($inventory->material_request_id) {
                    $badge = '<span class="badge bg-soft-primary text-primary mb-1">Material Request</span>';
                    $number = e($inventory->material_request_number ?? '-');
                } else {
                    $badge = '';
                    $number = '-';
                }

                // 📅 Date
                $date = Carbon::parse($inventory->created_at)->format('d M y H:i');

                // 🔥 Gabungkan seperti sale list
                $transactionDisplay = '
                    <div>
                        <div>' . $badge . '</div>
                        <div class="fw-semibold">' . $number . '</div>
                        <small class="text-muted">' . $date . '</small>
                    </div>
                ';

                // 👤 Partner
                if ($inventory->purchase_id) {
                    $partner = e(optional($inventory->purchase->supplier)->name ?? '-');
                } elseif ($inventory->production_stock_id) {
                    $partner = 'Production';
                } elseif ($inventory->sale_return_id) {
                    $partner = e(optional($inventory->saleReturn->customer)->name ?? '-');
                } else {
                    $partner = '-';
                }

                // 📦 Stock in partial
                $stockInHtml = view('erp.pages.inventory.stock-in.partials.product-stock-in', compact('inventory'))->render();

                // ⚙️ Action partial
                $actionHtml = view('erp.pages.inventory.stock-in.partials.action-button-stock-in', compact('inventory'))->render();

                return [
                    'id' => $inventory->id,
                    'transaction_number' => $transactionDisplay,
                    'date' => $date,
                    'partner_name' => $partner,
                    'stock_in' => $stockInHtml,
                    'action' => $actionHtml,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function getStockOut()
    {
        return view('erp.pages.inventory.stock-out.stock-out');
    }

    public function dataStockOut(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $inventory = Inventory::with([
            'items',
            'purchaseReturn.supplier',
            'order.customer',
            'materialRequest.requestedBy',
        ])
            ->where('status', 'Stock Out')
            ->orderByDesc('id');

        // 🔎 Filter tanggal
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

        // 🔎 Filter pencarian
        if ($request->search_type && $request->filled('search_keyword')) {
            if ($request->search_type === 'invoice_number') {
                $inventory->where(function ($q) use ($request) {
                    $q->where('purchase_number', 'like', '%' . $request->search_keyword . '%')
                        ->orWhere('order_number', 'like', '%' . $request->search_keyword . '%');
                });
            } elseif ($request->search_type === 'partner') {
                $inventory->where(function ($q) use ($request) {
                    $q->whereHas('purchaseReturn.supplier', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });

                    $q->orWhereHas('order.customer', function ($query) use ($request) {
                        $query->where('name', 'like', '%' . $request->search_keyword . '%');
                    });
                });
            }
        } elseif ($request->search_type === 'type' && $request->filled('search_type_dropdown')) {
            if ($request->search_type_dropdown === 'purchase_return') {
                $inventory->whereNotNull('purchase_return_id');
            } elseif ($request->search_type_dropdown === 'sale') {
                $inventory->whereNotNull('order_id');
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $inventory->whereHas('items.product', function ($q) use ($productKeyword) {
                // gunakan COLLATE biar bisa handle tanda kurung
                $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
            });
        }

        // 🔎 Filter progress status
        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $inventory->whereDoesntHave('items', function ($q) {
                    $q->whereColumn('stock_out', '<', 'quantity');
                })
                    ->whereNotNull('material_request_id')
                    ->whereHas('materialRequest', function ($q) {
                        $q->whereNull('deleted_at');
                    });
            } elseif ($request->progress_status === 'progress') {
                $inventory->whereHas('items', function ($q) {
                    $q->whereColumn('stock_out', '<', 'quantity');
                });
            }
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $inventory;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $inventory->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($inventory) {
                // 🧾 Transaction number + badge
                if ($inventory->purchase_return_id) {
                    $badge = '<span class="badge bg-soft-danger text-danger mb-1">Purchase Return</span>';
                    $number = e($inventory->purchase_number ?? '-');
                } elseif ($inventory->material_request_id) {
                    $badge = '<span class="badge bg-soft-warning text-warning mb-1">Request Stock</span>';
                    $number = e($inventory->material_request_number ?? '-');
                } elseif ($inventory->order_id) {
                    $badge = '<span class="badge bg-soft-success text-success mb-1">Sale List</span>';
                    $number = e($inventory->order->order_number ?? '-');
                } else {
                    $badge = '';
                    $number = '-';
                }

                // 📅 Date
                $date = Carbon::parse($inventory->created_at)->format('d M y H:i');

                // 🔥 Gabungkan semuanya
                $transactionDisplay = '
                    <div>
                        <div>' . $badge . '</div>
                        <div class="fw-semibold">' . $number . '</div>
                        <small class="text-muted">' . $date . '</small>
                    </div>
                ';

                // 👤 Partner Name
                if ($inventory->purchase_return_id) {
                    $partner = e(optional($inventory->purchaseReturn->supplier)->name ?? '-');
                } elseif ($inventory->material_request_id) {
                    $partner = e(optional($inventory->materialRequest->requestedBy)->name ?? '-');
                } else if ($inventory->order_id) {
                    $partner = e(optional($inventory->order->customer)->name ?? '-');
                } else {
                    $partner = '-';
                }

                // 📦 Stock Out partial
                $stockOutHtml = view('erp.pages.inventory.stock-out.partials.product-stock-out', compact('inventory'))->render();

                // ⚙️ Action partial
                $actionHtml = view('erp.pages.inventory.stock-out.partials.action-button-stock-out', compact('inventory'))->render();

                return [
                    'id' => $inventory->id,
                    'transaction_number' => $transactionDisplay,
                    'date' => $date,
                    'partner_name' => $partner,
                    'stock_out' => $stockOutHtml,
                    'action' => $actionHtml,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }


    public function getReportItems()
    {
        return view('erp.pages.inventory.report-items');
    }

    // public function dataReportItems(Request $request)
    // {
    //     $reportItems = InventoryStock::whereHas('product', function ($q) {
    //         $q->whereNull('products.deleted_at');
    //     })->with('product');

    //     if ($request->filled('product_name')) {
    //         $reportItems->whereHas('product', function ($query) use ($request) {
    //             $query->where('name', 'like', '%' . $request->product_name . '%');
    //         });
    //     }

    //     $reportItems->orderBy(
    //         Products::select('name')
    //             ->whereColumn('products.id', 'inventory_stocks.product_id')
    //     );

    //     $reportItems = $reportItems->get();

    //     return DataTables::of($reportItems)
    //         ->addIndexColumn()
    //         ->addColumn('name', function ($reportItem) {
    //             return $reportItem->product->name;
    //         })
    //         ->addColumn('purchase_stocks', function ($reportItem) {
    //             return $reportItem->purchase_stocks ?? 0;
    //         })
    //         ->addColumn('inventory_stock', function ($reportItem) {
    //             return number_format($reportItem->inventory_stock);
    //         })
    //         // ->addColumn('stock_after_sales', function ($reportItem) {
    //         //     return '<span class="text-danger">' . $reportItem->stock_after_sales . '</span>';
    //         // })
    //         ->addColumn('stock_after_sales', function ($reportItem) {
    //             $stock = number_format($reportItem->stock_after_sales) ?? 0;

    //             // ✅ cek minimum stock
    //             if ($stock <= $reportItem->minimum_stock) {
    //                 return $stock . ' <span class="text-danger">(Low Stock)</span>';
    //             }

    //             return $stock;
    //         })
    //         ->addColumn('incoming_stock', function ($reportItem) {
    //             return number_format($reportItem->incoming_stock) ?? 0;
    //         })
    //         ->addColumn('avg_cost', function ($reportItem) {
    //             return '<span class="text-primary">' . $reportItem->avg_cost . '</span>';
    //         })
    //         ->addColumn('action', function ($row) {
    //             return '
    //                 <button type="button" class="btn btn-sm btn-outline-danger btnDefect" 
    //                     data-id="' . $row->product_id . '" 
    //                     data-name="' . e($row->product->name) . '">
    //                     <i class="feather-alert-triangle me-1"></i> Defect
    //                 </button>
    //             ';
    //         })

    //         ->rawColumns(['stock_after_sales', 'avg_cost', 'action'])
    //         ->make(true);
    // }

    public function dataReportItems(Request $request)
    {
        $reportItems = InventoryStock::whereHas('product', function ($q) {
            $q->whereNull('products.deleted_at');
        })
            ->with('product')
            ->orderBy('stock_after_sales', 'desc'); // urutkan berdasarkan kolom stock_after_sales

        if ($request->filled('product_name')) {
            $keyword = $request->product_name;

            $reportItems->whereHas('product', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('sku', 'like', '%' . $keyword . '%');
                });
            });
        }

        // urutkan nama produk setelah stock (opsional, kalau mau tambahan)
        $reportItems->orderBy(
            Products::select('name')
                ->whereColumn('products.id', 'inventory_stocks.product_id')
        );

        $reportItems = $reportItems->get();

        return DataTables::of($reportItems)
            ->addIndexColumn()
            ->addColumn('name', fn($item) => e($item->product->name))
            ->addColumn(
                'purchase_stocks',
                fn($item) =>
                number_format($item->purchase_stocks ?? 0, 0, ',', '.')
            )
            ->addColumn(
                'inventory_stock',
                fn($item) =>
                number_format($item->inventory_stock ?? 0, 0, ',', '.')
            )
            // ->addColumn('stock_after_sales', function ($item) {
            //     $stock = (int) $item->stock_after_sales;
            //     $formatted = number_format($stock, 0, ',', '.');

            //     if ($stock <= $item->minimum_stock) {
            //         return $formatted . ' <span class="text-danger">(Low Stock)</span>';
            //     }

            //     return $formatted;
            // })
            ->addColumn('stock_after_sales', function ($item) {

                // 1. Inventory stock
                $inventoryStock = (int) $item->inventory_stock;

                // 2. Production stock (available_quantity)
                $productionStock = \App\Models\ProductionStock::where('product_id', $item->product_id)
                    ->sum('available_quantity');

                // 3. Total usage from order_item_components (qty)
                $usedQty = \App\Models\OrderItemComponent::where('product_id', $item->product_id)
                    ->whereNull('deleted_at')
                    ->sum('qty');

                // 4. Final stock after sales
                $stockAfterSales = $inventoryStock + $productionStock - $usedQty;

                // Format tampilannya
                $formatted = number_format($stockAfterSales, 0, ',', '.');

                if ($stockAfterSales <= $item->minimum_stock) {
                    return $formatted . ' <span class="text-danger">(Low Stock)</span>';
                }

                return $formatted;
            })
            // ->addColumn(
            //     'incoming_stock',
            //     fn($item) =>
            //     number_format($item->incoming_stock ?? 0, 0, ',', '.')
            // )
            ->addColumn('incoming_stock', function ($item) {
                $incoming = DB::table('inventory_items_2')
                    ->where('product_id', $item->product_id)
                    ->whereNotNull('purchase_item_id')
                    ->selectRaw('SUM(remaining_stock_in - stock_in) AS incoming')
                    ->value('incoming');

                return number_format($incoming ?? 0, 0, ',', '.');
            })
            ->addColumn('outgoing_stock', function ($item) {

                $outgoing = DB::table('inventory_items_2')
                    ->where('product_id', $item->product_id)
                    ->whereNotNull('material_request_item_id')
                    ->selectRaw('SUM(remaining_stock_in - stock_out) AS outgoing')
                    ->value('outgoing');

                return number_format($outgoing ?? 0, 0, ',', '.');
            })
            ->addColumn(
                'avg_cost',
                fn($item) =>
                '<span class="text-primary">' . number_format($item->avg_cost, 0, ',', '.') . '</span>'
            )
            ->addColumn('action', fn($item) => '
            <button type="button" class="btn btn-sm btn-outline-danger btnDefect" 
                data-id="' . $item->product_id . '" 
                data-name="' . e($item->product->name) . '">
                <i class="feather-alert-triangle me-1"></i> Defect
            </button>
        ')
            ->rawColumns(['stock_after_sales', 'avg_cost', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:1',
            'note'       => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $productId = $request->product_id;
            $quantity  = (int) $request->quantity;

            // 🔹 Ambil stok inventory
            $inventory = \App\Models\InventoryStock::where('product_id', $productId)->first();

            if (!$inventory) {
                throw new \Exception('Inventory stock record not found for this product.');
            }

            // 🔹 Cek stok cukup atau tidak
            if ($inventory->inventory_stock < $quantity) {
                throw new \Exception('Insufficient inventory stock for defect input.');
            }

            // 🔹 Simpan defect product
            $defect = \App\Models\DefectProduct::create([
                'product_id'   => $productId,
                'quantity'     => $quantity,
                'defect_date'  => now(),
                'status'       => 'pending',
                'type'         => 'Inventory',
                'note'         => $request->note,
                'user_id'      => Auth::id(),
                // 'inventory_id' => $inventory->id,
            ]);

            // 🔹 Kurangi stok di inventory
            $inventory->decrement('inventory_stock', $quantity);
            $inventory->decrement('stock_after_sales', $quantity);

            DB::commit();
            return response()->json([
                'message' => 'Defect product successfully recorded and stock updated.',
                'data'    => $defect
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to store defect product: ' . $e->getMessage()
            ], 500);
        }
    }
}
