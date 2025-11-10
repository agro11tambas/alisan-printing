<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Products;
use App\Models\Customers;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAddresses;
use Carbon\Carbon;
use App\Models\Discount;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Bank;
use App\Models\CanceledProduct;
use App\Models\DefectProduct;
use App\Models\FinancialReport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\ProductionStock;
use App\Models\SaleReturnEditHistory;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Services\ProductCostService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class SaleReturnController extends Controller
{
    public function getSaleReturns()
    {
        $order_number = SaleReturn::first();
        $transactionTypes = Account::where('name', 'Sale Return')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $defaultAccount = Account::where('is_default', true)->first();

        return view('erp.pages.sales.sale-return.sale-return', compact('order_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'defaultAccount'));
    }

    public function dataSaleReturns(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $returns = SaleReturn::with('customer')
            ->where('status', 'sale returns')
            ->orderBy('created_at', 'desc');

        // 🔹 Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $returns->whereDate('return_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $returns->whereBetween('return_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $returns->whereMonth('return_date', Carbon::now()->month)
                        ->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $returns->whereBetween('return_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $returns->whereBetween('return_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $returns->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $returns->whereBetween('return_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // 🔹 Filter payment status & keyword
        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $returns->whereIn('payment_status', ['Paid', 'Over Refunded']);
            } else {
                $returns->where('payment_status', $request->payment_status);
            }
        } elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $returns->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $returns->where('order_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $returns;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $returns->skip($start)->take($length)->get();

        // 🔹 Return format JSON untuk lazy-load
        return response()->json([
            'data' => $data->map(function ($return) {
                $date = Carbon::parse($return->return_date)->format('j M y');

                // 🔸 Order number & edited badge
                $html = '';
                if ((int)($return->status_edited ?? 0) === 1) {
                    $html .= '<div class="mb-1"><span class="badge bg-soft-primary text-primary">Edited</span></div>';
                }
                $html .= '
                <div>
                    <div>' . e($return->order_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>
            ';

                // 🔸 Payment status badge
                $payment_status = strtolower($return->payment_status);
                $badge = match ($payment_status) {
                    'refunded', 'paid' => '<div class="badge bg-soft-success text-success">' . e($return->payment_status) . '</div>',
                    'unpaid' => '<div class="badge bg-soft-danger text-danger">' . e($return->payment_status) . '</div>',
                    default => '<div class="badge bg-soft-warning text-warning">' . e($return->payment_status) . '</div>',
                };

                // 🔸 Status badge
                $statusBadge = '<div class="badge bg-soft-dark text-dark">' . ucfirst($return->status) . '</div>';

                // 🔸 Produk (termasuk soft deleted)
                $items = $return->items()
                    ->with([
                        'product' => fn($q) => $q->withTrashed(),
                    ])
                    ->get()
                    ->map(function ($item) {
                        $product = $item->product;
                        return [
                            'name' => e($product->name ?? '-'),
                            'sku' => e($product->sku ?? '-'),
                            'qty' => number_format($item->quantity, 0, ',', '.'),
                            'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        ];
                    });

                return [
                    'id' => $return->id,
                    'order_number' => $html,
                    'return_date' => $date,
                    'customer' => e($return->customer->name ?? '-'),
                    'total_amount' => 'Rp ' . number_format($return->total_amount, 0, ',', '.'),
                    'refund_amount' => '<span class="text-success">Rp ' . number_format($return->refund_amount, 0, ',', '.') . '</span>',
                    'remaining_amount' => '<span class="text-danger">Rp ' . number_format($return->remaining_amount, 0, ',', '.') . '</span>',
                    'payment_status' => $badge,
                    'status' => $statusBadge,
                    'products' => $items,
                    'account' => e($return->account ?? '-'),
                    'action' => view('erp.pages.sales.sale-return.partials.action-button', compact('return'))->render(),
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function dataDeletedSaleReturns(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $returns = SaleReturn::onlyTrashed()
            ->with(['customer', 'items.product', 'items.productBundle'])
            ->where('status', 'sale returns')
            ->orderBy('deleted_at', 'desc');

        // 🔎 Search customer
        if ($request->search_type === 'customer' && $request->filled('search_keyword')) {
            $returns->whereHas('customer', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search_keyword . '%');
            });
        }

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $returns;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $returns->skip($start)->take($length)->get();

        // 🔹 Return format JSON untuk lazy-load
        return response()->json([
            'data' => $data->map(function ($return) {
                $date = \Carbon\Carbon::parse($return->return_date)->format('j M y');

                $orderNumber = '<div>
                <div>' . e($return->order_number) . '</div>
                <small class="text-muted">' . $date . '</small>
            </div>';

                // 🔹 Produk (termasuk soft deleted)
                $items = $return->items()
                    ->with([
                        'product' => fn($q) => $q->withTrashed(),
                    ])
                    ->get()
                    ->map(function ($item) {
                        $product = $item->product;

                        return [
                            'name'  => e($product->name ?? '-'),
                            'sku'   => e($product->sku ?? '-'),
                            'qty'   => number_format($item->quantity, 0, ',', '.'),
                            'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        ];
                    });

                // 🔹 Tombol Action (hanya Owner)
                $action = '';
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    $action = '
                <div class="d-flex gap-2">
                    <button type="button" 
                        class="btn btn-success btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRestoreOrder"
                        data-id="' . $return->id . '" 
                        data-name="' . e($return->order_number) . '"
                        data-url="' . route('sale-returns.restore', $return->id) . '">
                            Restore
                    </button>
                    <button type="button" 
                        class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalForceDeleteOrder"
                        data-id="' . $return->id . '" 
                        data-name="' . e($return->order_number) . '"
                        data-url="' . route('sale-returns.forceDelete', $return->id) . '">
                            Hapus Permanen
                    </button>
                </div>';
                }

                return [
                    'id' => $return->id,
                    'order_number' => $orderNumber,
                    'customer' => e($return->customer->name ?? '-'),
                    'total_amount' => 'Rp ' . number_format($return->total_amount, 0, ',', '.'),
                    'deleted_at' => $return->deleted_at ? $return->deleted_at->format('j M y H:i') : '-',
                    'products' => $items,
                    'delete_notes' => e($return->delete_notes ?? '-'),
                    'deleted_by' => e(optional($return->deletedByUser)->name ?? '-'),
                    'action' => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create($id)
    {
        $order = Order::with(['orderItems.product', 'orderItems.productBundle.items.product', 'customer.addresses', 'customerAddress'])
            ->findOrFail($id);

        $expandedItems = collect();

        foreach ($order->orderItems as $item) {
            // 🔹 Hitung total sudah dikirim (shipped) untuk item ini
            $totalShipped = \App\Models\DeliveryOrderItem::where('order_item_id', $item->id)
                ->sum('shipped_qty');

            if ($item->product_id) {
                // 🔹 Hitung total sudah diretur (gabungan canceled + defect)
                $returnedQty = \App\Models\SaleReturnItem::where('order_item_id', $item->id)
                    ->selectRaw('COALESCE(SUM(canceled_quantity + defect_quantity), 0) as total_return')
                    ->value('total_return');

                // 🔹 Remaining qty = shipped - returned
                $item->remaining_qty = max(0, $totalShipped - $returnedQty);

                $expandedItems->push($item);
            } elseif ($item->product_bundle_id) {
                foreach ($item->productBundle->items as $bundleItem) {
                    // 🔹 Hitung total sudah dikirim (shipped)
                    $totalShipped = \App\Models\DeliveryOrderItem::where('order_item_id', $item->id)
                        ->where('product_id', $bundleItem->product_id)
                        ->sum('shipped_qty');

                    // 🔹 Hitung total sudah diretur (gabungan canceled + defect)
                    $returnedQty = \App\Models\SaleReturnItem::where('order_item_id', $item->id)
                        ->where('product_id', $bundleItem->product_id)
                        ->selectRaw('COALESCE(SUM(canceled_quantity + defect_quantity), 0) as total_return')
                        ->value('total_return');

                    $expandedItems->push((object) [
                        'id'            => $item->id,
                        'order_id'      => $item->order_id,
                        'product_id'    => $bundleItem->product_id,
                        'product'       => $bundleItem->product,
                        'quantity'      => $item->quantity,
                        'remaining_qty' => max(0, $totalShipped - $returnedQty),
                        'price'         => $bundleItem->product->price ?? 0,
                    ]);
                }
            }
        }

        $products     = Products::with(['categories', 'discounts', 'categories.discounts'])->orderBy('name', 'asc')->get();
        $customers    = Customers::with('addresses')->orderBy('name', 'asc')->get();
        $cashAccounts = Account::where('name', 'Cash')->orderBy('name', 'asc')->get();
        $bankAccounts = Account::where('name', 'Bank')->orderBy('name', 'asc')->get();
        $discount     = Discount::first();

        return view('erp.pages.sales.sale-return.create-order', [
            'order'          => $order,
            'products'       => $products,
            'remainingItems' => $expandedItems,
            'customers'      => $customers,
            'cashAccounts'   => $cashAccounts,
            'bankAccounts'   => $bankAccounts,
            'discount'       => $discount,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_number'      => 'required|string',
            'sale_order_id'     => 'required|exists:orders,id',
            'customer_id'           => 'required|exists:customers,id',
            'customer_address_id'   => 'required|exists:customer_addresses,id',
            'return_date'       => 'required|date',
            'order_item_ids'    => 'required|array',
            'product_id'        => 'required|array',
            'canceled_quantity' => 'required|array',
            'defect_quantity' => 'required|array',
            'price'             => 'required|array',
            'total'             => 'required|array',
            'sub_total'         => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'return_type' => 'nullable|string|in:canceled,defect',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with(['orderItems.product', 'orderItems.productBundle.items.product'])
                ->findOrFail($request->sale_order_id);

            $address = CustomerAddresses::find($request->customer_address_id);

            $grandTotal = array_sum($request->total);
            $paidAmount = $request->refund_amount ?? 0;
            $remainingAmount = $grandTotal - $paidAmount;
            $status = 'Sale Returns';
            $account = 'Sale Return';
            $paymentStatus = ($paidAmount <= 0) ? 'Unpaid' : (($paidAmount < $grandTotal) ? 'Partially Paid' : 'Refunded');

            // Buat SaleReturn
            $saleReturn = SaleReturn::create([
                'sale_order_id'     => $order->id,
                'customer_id'          => $request->customer_id,
                'customer_address_id'  => $request->customer_address_id,
                'order_number'      => $request->order_number,
                'return_date'       => $request->return_date,
                'payment_status'    => $paymentStatus,
                'status'            => $status,
                'account'           => $account,
                'total_amount'      => $grandTotal,
                'refund_amount'     => $paidAmount,
                'remaining_amount'  => $remainingAmount,
                'business_name'     => $address?->business_name,
                'return_address'    => $address?->address,
                'google_map'        => $address?->google_maps,
                'note'              => $request->note,
            ]);

            $returnType = $request->return_type; // satu kali di atas foreach

            foreach ($request->order_item_ids as $index => $orderItemId) {
                if (empty($orderItemId)) continue;

                $orderItem = $order->orderItems->firstWhere('id', (int) $orderItemId);
                if (!$orderItem) continue;

                $productId = $request->product_id[$index] ?? null;
                $canceledQty = (int)($request->canceled_quantity[$index] ?? 0);
                $defectQty = (int)($request->defect_quantity[$index] ?? 0);
                $totalQty = $canceledQty + $defectQty;

                if ($totalQty <= 0) continue;
                $price     = (float)($request->price[$index] ?? 0);
                $subtotal  = (float)($request->total[$index] ?? ($totalQty * $price));

                if ($totalQty <= 0 || !$productId) continue;

                // Validasi retur
                $returnedQty = SaleReturnItem::where('order_item_id', $orderItem->id)
                    ->where('product_id', $productId)
                    ->sum(DB::raw('COALESCE(canceled_quantity, 0) + COALESCE(defect_quantity, 0)'));

                $maxQty = max(0, $orderItem->quantity - $returnedQty);
                if ($totalQty > $maxQty) {
                    $pname = optional(\App\Models\Products::find($productId))->name ?? 'Produk';
                    throw new \Exception("Qty retur melebihi sisa qty untuk: {$pname}");
                }

                // Ambil avg_cost
                $component = \App\Models\OrderItemComponent::where('order_item_id', $orderItem->id)
                    ->where('product_id', $productId)
                    ->first();

                $avgCostAtSale = $component?->avg_cost_at_sale ?? 0;
                $fixedCostAtSale = $component?->fixed_cost_at_sale ?? 0;

                $totalCostAtSale = $avgCostAtSale * $totalQty;
                $totalFixedCostAtSale = $fixedCostAtSale * $totalQty;

                // Simpan SaleReturnItem
                $saleReturnItem = SaleReturnItem::create([
                    'sale_return_id'       => $saleReturn->id,
                    'product_id'           => $productId,
                    'order_item_id'        => $orderItem->id,
                    'canceled_quantity'    => $canceledQty,
                    'defect_quantity'      => $defectQty,
                    'price'                => $price,
                    'total'                => $subtotal,
                    'avg_cost_at_return'   => $avgCostAtSale,
                    'fixed_cost_at_return' => $fixedCostAtSale,
                    'total_cost'           => $totalCostAtSale,
                    'total_fixed_cost'     => $totalFixedCostAtSale,
                ]);

                if ($defectQty > 0) {
                    DefectProduct::create([
                        'product_id'   => $productId,
                        'quantity'     => $defectQty,
                        'defect_date'  => $request->return_date,
                        'defect_type'  => 'from_sale_return',
                        'status'       => 'pending',
                        'note'         => 'Defect product from Sale Return',
                        'user_id'      => Auth::id(),
                        'sale_return_id'      => $saleReturn->id,
                        'sale_return_item_id' => $saleReturnItem->id,
                    ]);
                }

                if ($canceledQty > 0) {
                    $productionStock = ProductionStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'production_warehouse_id' => $orderItem->order->warehouse_id ?? 2,
                        ],
                        [
                            'opening_stock' => 0,
                            'finished_product_stock' => 0,
                            'canceled_product_stock' => 0,
                            'available_quantity' => 0,
                        ]
                    );
                    $productionStock->increment('canceled_product_stock', $canceledQty);

                    CanceledProduct::create([
                        'production_stock_id' => $productionStock->id,
                        'product_id'          => $productId,
                        'warehouse_id'        => $productionStock->production_warehouse_id,
                        'sale_return_id'      => $saleReturn->id,
                        'sale_return_item_id' => $saleReturnItem->id,
                        'order_id'            => $order->id,
                        'order_item_id'       => $orderItem->id,
                        'quantity'            => $canceledQty,
                        'avg_cost_at_cancel'  => $avgCostAtSale,
                        'fixed_cost_at_cancel' => $fixedCostAtSale,
                        'total_cost'          => $totalCostAtSale,
                        'total_fixed_cost'    => $totalFixedCostAtSale,
                        'date'                => $request->return_date,
                        'type'                => 'from_sale_return',
                        'status'              => 'pending',
                        'note'                => 'Canceled product from Sale Return',
                        'created_by'          => Auth::id(),
                    ]);
                }
            }

            $groupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type);

            // **Transaksi CREDIT (sale return refund ke customer)**
            AccountTransaction::create([
                'sale_return_id'      => $saleReturn->id,
                'order_number'        => $saleReturn->order_number,
                'transaction_date'    => $request->return_date,
                'account_id'          => $saleAccount->id,
                'credit'              => 0,
                'debit'               => $grandTotal,
                'note'                => $request->note ?? '',
                'particular'          => '',
                'transaction_group_id' => $groupId,
            ]);

            $saleAccount->closing_balance += $grandTotal;
            $saleAccount->save();

            // ================== CATAT FINANCIAL REPORT ==================
            try {
                $returnDate = Carbon::parse($request->return_date);
                $returnRevenue = (float) $grandTotal;

                // 🔹 Ambil total COGS dari avg_cost_at_return yang disimpan di sale_return_items
                $returnCogs = SaleReturnItem::where('sale_return_id', $saleReturn->id)
                    ->sum(DB::raw('avg_cost_at_return * (COALESCE(canceled_quantity, 0) + COALESCE(defect_quantity, 0))'));

                // 🔹 Karena ini retur, nilainya negatif (revenue & cogs berkurang)
                $grossLoss = -1 * ($returnRevenue - $returnCogs);
                $netLoss   = $grossLoss;

                FinancialReport::create([
                    'date'             => $returnDate,
                    'transaction_type' => 'sale_return',
                    'reference_id'     => $saleReturn->id,
                    'reference_table'  => 'sale_returns',
                    'revenue'          => -$returnRevenue,  // pendapatan berkurang
                    'cogs'             => -$returnCogs,     // cost ikut dikembalikan
                    'gross_profit'     => $grossLoss,
                    'expense'          => 0,
                    'net_profit'       => $netLoss,
                    'notes'            => 'Auto-generated from Sale Return (based on avg_cost_at_return)',
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal mencatat laporan keuangan Sale Return ID ' . $saleReturn->id . ': ' . $e->getMessage());
            }

            DB::commit();
            return redirect('/erp/sales/sale-returns')->with('success', 'Sale return berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale Return Store Failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Gagal menyimpan sale return: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $saleReturn = SaleReturn::with([
            'items.product',
            'items.orderItem.product',
            'items.orderItem.productBundle.items.product',
            'customer.addresses',
            'customerAddress',
        ])->findOrFail($id);

        $order = $saleReturn->saleOrder()
            ->with(['orderItems.product', 'orderItems.productBundle.items.product'])
            ->first();

        $expandedItems = collect();

        foreach ($order->orderItems as $item) {
            $returnedQty = SaleReturnItem::where('order_item_id', $item->id)
                ->where('sale_return_id', '!=', $saleReturn->id)
                ->sum(DB::raw('COALESCE(canceled_quantity,0) + COALESCE(defect_quantity,0)'));

            if ($item->product_id) {
                $existingItem = $saleReturn->items
                    ->where('order_item_id', $item->id)
                    ->where('product_id', $item->product_id)
                    ->first();

                $expandedItems->push((object) [
                    'order_item_id'     => $item->id,                   // 🟢 WAJIB
                    'product_id'        => $item->product_id,
                    'product'           => $item->product,
                    'quantity'          => $item->quantity,
                    'remaining_qty'     => max(0, $item->quantity - $returnedQty),
                    'canceled_quantity' => $existingItem->canceled_quantity ?? 0,
                    'defect_quantity'   => $existingItem->defect_quantity ?? 0,
                    'price'             => $existingItem->price ?? ($item->product->price ?? 0),
                    'total'             => $existingItem->total ?? 0,
                ]);
            } elseif ($item->product_bundle_id) {
                foreach ($item->productBundle->items as $bundleItem) {
                    $bundleReturnedQty = SaleReturnItem::where('order_item_id', $item->id)
                        ->where('product_id', $bundleItem->product_id)
                        ->where('sale_return_id', '!=', $saleReturn->id)
                        ->sum(DB::raw('COALESCE(canceled_quantity,0) + COALESCE(defect_quantity,0)'));

                    $existingItem = $saleReturn->items
                        ->where('order_item_id', $item->id)
                        ->where('product_id', $bundleItem->product_id)
                        ->first();

                    $expandedItems->push((object) [
                        'order_item_id'     => $item->id,                 // 🟢 WAJIB
                        'product_id'        => $bundleItem->product_id,
                        'product'           => $bundleItem->product,
                        'quantity'          => $item->quantity,
                        'remaining_qty'     => max(0, $item->quantity - $bundleReturnedQty),
                        'canceled_quantity' => $existingItem->canceled_quantity ?? 0,
                        'defect_quantity'   => $existingItem->defect_quantity ?? 0,
                        'price'             => $existingItem->price ?? ($bundleItem->product->price ?? 0),
                        'total'             => $existingItem->total ?? 0,
                    ]);
                }
            }
        }

        $products     = Products::with(['categories', 'discounts', 'categories.discounts'])
            ->orderBy('name', 'asc')->get();
        $customers    = Customers::with('addresses')->orderBy('name', 'asc')->get();
        $cashAccounts = Account::where('name', 'Cash')->orderBy('name', 'asc')->get();
        $bankAccounts = Account::where('name', 'Bank')->orderBy('name', 'asc')->get();
        $discount     = Discount::first();

        return view('erp.pages.sales.sale-return.edit-order', [
            'saleReturn'     => $saleReturn,
            'order'          => $order,
            'products'       => $products,
            'remainingItems' => $expandedItems,
            'customers'      => $customers,
            'cashAccounts'   => $cashAccounts,
            'bankAccounts'   => $bankAccounts,
            'discount'       => $discount
        ]);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'order_number'      => 'required|string',
            'sale_order_id'     => 'required|exists:orders,id',
            'customer_id'         => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'return_date'       => 'required|date',
            'order_item_ids'    => 'required|array',
            'product_id'        => 'required|array',
            'canceled_quantity' => 'required|array',
            'defect_quantity'   => 'required|array',
            'price'             => 'required|array',
            'total'             => 'required|array',
            'sub_total'         => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'edit_note'         => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $saleReturn = SaleReturn::with(['items'])->findOrFail($id);

            if ($saleReturn->hasStockIn()) {
                return back()->with(
                    'error',
                    'Tidak bisa mengubah Sale Return ini karena produk sudah dikembalikan ke Warehouse (completed quantity > 0).'
                );
            }

            $order = Order::with(['orderItems.product', 'orderItems.productBundle.items.product'])
                ->findOrFail($request->sale_order_id);

            // 🔹 VALIDASI TAMBAHAN:
            // Pastikan quantity defect dan canceled tidak kurang dari item sale return
            foreach ($saleReturn->items as $item) {
                // Cek Defect Product
                $defect = DefectProduct::where('sale_return_item_id', $item->id)->first();
                if ($defect && $defect->quantity < $item->defect_quantity) {
                    DB::rollBack();
                    return back()->with(
                        'error',
                        "Gagal memperbarui: Quantity defect untuk produk {$item->product->name} ({$defect->quantity}) lebih kecil dari jumlah di Sale Return ({$item->defect_quantity})."
                    );
                }

                // Cek Canceled Product
                $canceled = CanceledProduct::where('sale_return_item_id', $item->id)->first();
                if ($canceled && $canceled->quantity < $item->canceled_quantity) {
                    DB::rollBack();
                    return back()->with(
                        'error',
                        "Gagal memperbarui: Quantity canceled untuk produk {$item->product->name} ({$canceled->quantity}) lebih kecil dari jumlah di Sale Return ({$item->canceled_quantity})."
                    );
                }
            }

            $address = CustomerAddresses::find($request->customer_address_id);

            $grandTotal = array_sum($request->total);
            $paidAmount = $request->refund_amount ?? $saleReturn->refund_amount ?? 0;
            $remainingAmount = $grandTotal - $paidAmount;
            $status = 'Sale Returns';
            $account = 'Sale Return';
            $paymentStatus = ($paidAmount <= 0) ? 'Unpaid' : (($paidAmount < $grandTotal) ? 'Partially Paid' : 'Refunded');

            // === SNAPSHOT LAMA ===
            $oldHeader = Arr::only($saleReturn->toArray(), [
                'order_number',
                'customer_id',
                'customer_address_id',
                'return_date',
                'payment_status',
                'status',
                'account',
                'total_amount',
                'refund_amount',
                'remaining_amount',
                'return_address',
                'google_map',
                'note'
            ]);

            $oldItems = $saleReturn->items->mapWithKeys(function ($item) {
                return [$item->id => [
                    'product' => $item->product->name ?? '-',
                    'canceled_qty' => (int)$item->canceled_quantity,
                    'defect_qty' => (int)$item->defect_quantity,
                    'price' => (float)$item->price,
                    'total' => (float)$item->total,
                ]];
            });

            // === UPDATE SALE RETURN HEADER ===
            $saleReturn->update([
                'sale_order_id'     => $order->id,
                'customer_id'          => $request->customer_id,
                'customer_address_id'  => $request->customer_address_id,
                'order_number'      => $request->order_number,
                'return_date'       => $request->return_date,
                'payment_status'    => $paymentStatus,
                'status'            => $status,
                'account'           => $account,
                'total_amount'      => $grandTotal,
                'refund_amount'     => $paidAmount,
                'remaining_amount'  => $remainingAmount,
                'business_name'     => $address?->business_name,
                'return_address'    => $address?->address,
                'google_map'        => $address?->google_maps,
                'note'              => $request->edit_note,
            ]);

            // === UPDATE ITEMS ===
            $existingItems = $saleReturn->items->keyBy(fn($i) => $i->order_item_id . '-' . $i->product_id);
            $requestKeys = [];

            foreach ($request->order_item_ids as $index => $orderItemId) {
                $orderItem = $order->orderItems->firstWhere('id', (int)$orderItemId);
                if (!$orderItem) continue;

                $productId = $request->product_id[$index] ?? null;
                $canceledQty = (int)($request->canceled_quantity[$index] ?? 0);
                $defectQty = (int)($request->defect_quantity[$index] ?? 0);

                $totalQty = $canceledQty + $defectQty;

                if ($totalQty <= 0 || !$productId) continue;

                $price = (float)($request->price[$index] ?? 0);
                $subtotal = (float)($request->total[$index] ?? ($totalQty * $price));
                $key = $orderItem->id . '-' . $productId;
                $requestKeys[] = $key;

                // Ambil avg_cost
                $component = \App\Models\OrderItemComponent::where('order_item_id', $orderItem->id)
                    ->where('product_id', $productId)
                    ->first();

                $avgCostAtSale = $component?->avg_cost_at_sale ?? 0;
                $fixedCostAtSale = $component?->fixed_cost_at_sale ?? 0;
                $totalCostAtSale = $avgCostAtSale * $totalQty;
                $totalFixedCostAtSale = $fixedCostAtSale * $totalQty;

                // ===== ITEM SUDAH ADA =====
                if ($existingItems->has($key)) {
                    $item = $existingItems[$key];
                    $item->update([
                        'canceled_quantity'    => $canceledQty,
                        'defect_quantity'      => $defectQty,
                        'price'                => $price,
                        'total'                => $subtotal,
                        'avg_cost_at_return'   => $avgCostAtSale,
                        'fixed_cost_at_return' => $fixedCostAtSale,
                        'total_cost'           => $totalCostAtSale,
                        'total_fixed_cost'     => $totalFixedCostAtSale,
                    ]);
                } else {
                    // ===== ITEM BARU =====
                    $item = SaleReturnItem::create([
                        'sale_return_id'       => $saleReturn->id,
                        'product_id'           => $productId,
                        'order_item_id'        => $orderItem->id,
                        'canceled_quantity'    => $canceledQty,
                        'defect_quantity'      => $defectQty,
                        'price'                => $price,
                        'total'                => $subtotal,
                        'avg_cost_at_return'   => $avgCostAtSale,
                        'fixed_cost_at_return' => $fixedCostAtSale,
                        'total_cost'           => $totalCostAtSale,
                        'total_fixed_cost'     => $totalFixedCostAtSale,
                    ]);
                }

                // ===== DEFECT =====
                if ($defectQty > 0) {
                    $defectProduct = DefectProduct::updateOrCreate(
                        [
                            'sale_return_id' => $saleReturn->id,
                            'product_id'     => $productId,
                        ],
                        [
                            'quantity'     => $defectQty,
                            'defect_date'  => $request->return_date,
                            'defect_type'  => 'from_sale_return',
                            'status'       => 'pending',
                            'note'         => 'Defect product from Sale Return (edit)',
                            'user_id'      => Auth::id(),
                            'sale_return_item_id'  => $item->id ?? null,
                        ]
                    );

                    // pastikan relasi tambahan ikut diupdate juga (kayak store)
                    $defectProduct->update([
                        'sale_return_item_id' => $item->id ?? null,
                        'order_id'            => $order->id,
                        'order_item_id'       => $orderItem->id,
                    ]);
                } else {
                    // kalau defectQty 0, hapus record lama
                    DefectProduct::where('sale_return_id', $saleReturn->id)
                        ->where('product_id', $productId)
                        ->delete();
                }

                // ===== CANCELED =====
                if ($canceledQty > 0) {
                    $productionStock = ProductionStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'production_warehouse_id' => $orderItem->order->warehouse_id ?? 2,
                        ],
                        [
                            'opening_stock' => 0,
                            'finished_product_stock' => 0,
                            'canceled_product_stock' => 0,
                            'available_quantity' => 0,
                        ]
                    );

                    // update stok canceled_product_stock dengan nilai baru (bukan increment terus)
                    $oldCanceled = $existingItems->has($key)
                        ? (int)$existingItems[$key]->canceled_quantity
                        : 0;

                    $delta = $canceledQty - $oldCanceled;
                    if ($delta !== 0) {
                        $productionStock->canceled_product_stock += $delta;
                        $productionStock->save();
                    }

                    $canceled = CanceledProduct::updateOrCreate(
                        [
                            'sale_return_id' => $saleReturn->id,
                            'product_id'     => $productId,
                            'order_item_id'  => $orderItem->id,
                        ],
                        [
                            'production_stock_id'  => $productionStock->id,
                            'warehouse_id'         => $productionStock->production_warehouse_id,
                            'sale_return_item_id'  => $item->id ?? null,
                            'order_id'             => $order->id,
                            'quantity'             => $canceledQty,
                            'avg_cost_at_cancel'   => $avgCostAtSale,
                            'fixed_cost_at_cancel' => $fixedCostAtSale,
                            'total_cost'           => $totalCostAtSale,
                            'total_fixed_cost'     => $totalFixedCostAtSale,
                            'date'                 => $request->return_date,
                            'type'                 => 'from_sale_return',
                            'status'               => 'pending',
                            'note'                 => 'Canceled product from Sale Return (edit)',
                            'created_by'           => Auth::id(),
                        ]
                    );
                } else {
                    // kalau canceledQty 0, hapus record lama
                    CanceledProduct::where('sale_return_id', $saleReturn->id)
                        ->where('product_id', $productId)
                        ->delete();
                }
            }

            // Hapus item yang tidak ada di request
            foreach ($existingItems as $key => $item) {
                if (!in_array($key, $requestKeys)) {
                    $item->delete();
                }
            }

            // === CATAT HISTORY EDIT ===
            SaleReturnEditHistory::create([
                'sale_return_id' => $saleReturn->id,
                'edited_by'      => Auth::id(),
                'changes'        => 'Sale Return updated',
                'text'           => $request->edit_note,
                'edited_at'      => now(),
            ]);

            // === UPDATE FINANCIAL REPORT ===
            try {
                $returnDate = Carbon::parse($request->return_date);
                $returnRevenue = (float)$grandTotal;

                $returnCogs = SaleReturnItem::where('sale_return_id', $saleReturn->id)
                    ->sum(DB::raw('avg_cost_at_return * (COALESCE(canceled_quantity,0) + COALESCE(defect_quantity,0))'));

                $grossLoss = -1 * ($returnRevenue - $returnCogs);
                $netLoss = $grossLoss;

                FinancialReport::updateOrCreate(
                    [
                        'reference_id' => $saleReturn->id,
                        'reference_table' => 'sale_returns',
                    ],
                    [
                        'date'          => $returnDate,
                        'transaction_type' => 'sale_return',
                        'revenue'       => -$returnRevenue,
                        'cogs'          => -$returnCogs,
                        'gross_profit'  => $grossLoss,
                        'expense'       => 0,
                        'net_profit'    => $netLoss,
                        'notes'         => 'Updated from Sale Return edit',
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Gagal update laporan keuangan Sale Return ID ' . $saleReturn->id . ': ' . $e->getMessage());
            }

            DB::commit();
            return redirect('/erp/sales/sale-returns')->with('success', 'Sale return berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale Return Update Failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Gagal memperbarui sale return: ' . $e->getMessage());
        }
    }

    public function delete($id, Request $request)
    {
        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // Ambil SaleReturn + items
            $saleReturn = SaleReturn::with('items')->findOrFail($id);

            foreach ($saleReturn->items as $item) {
                // Cek Defect Product
                $defect = DefectProduct::where('sale_return_item_id', $item->id)->first();
                if ($defect && $defect->quantity < $item->defect_quantity) {
                    DB::rollBack();
                    return back()->with(
                        'error',
                        "Tidak bisa menghapus Sale Return: Quantity defect untuk produk {$item->product->name} ({$defect->quantity}) lebih kecil dari jumlah di Sale Return ({$item->defect_quantity})."
                    );
                }

                // Cek Canceled Product
                $canceled = CanceledProduct::where('sale_return_item_id', $item->id)->first();
                if ($canceled && $canceled->quantity < $item->canceled_quantity) {
                    DB::rollBack();
                    return back()->with(
                        'error',
                        "Tidak bisa menghapus Sale Return: Quantity canceled untuk produk {$item->product->name} ({$canceled->quantity}) lebih kecil dari jumlah di Sale Return ({$item->canceled_quantity})."
                    );
                }
            }

            // Simpan product_id untuk update stok nanti
            $items = $saleReturn->items;

            // Handle transaksi terkait
            $transactions = AccountTransaction::where('sale_return_id', $saleReturn->id)->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Sale Return') {
                    // rollback saldo Sale Return Account
                    if ($trx->debit > 0) {
                        $account->closing_balance -= $trx->debit;
                    }
                    if ($trx->credit > 0) {
                        $account->closing_balance += $trx->credit;
                    }

                    $trx->delete(); // soft delete transaksi
                } else {
                    // Cash / Bank account: jangan dihapus
                    $trx->sale_return_id = null;
                    $trx->note = trim(($trx->note ?? '') . ' [SaleReturn deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // Soft delete SaleReturn → otomatis cascade ke items & editHistories
            $saleReturn->delete();

            // ✅ Kurangi canceled_product_stock pada production_stocks
            foreach ($items as $item) {
                $productId = $item->product_id;
                $qty       = $item->quantity;

                $productionStock = ProductionStock::where('product_id', $productId)->first();
                if ($productionStock) {
                    $productionStock->decrement('canceled_product_stock', $qty);
                }

                // 🚮 Hapus juga ledger canceled_products untuk sale_return ini
                CanceledProduct::where('sale_return_id', $saleReturn->id)
                    ->where('product_id', $productId)
                    ->where('order_item_id', $item->order_item_id)
                    ->delete();

                // Update ulang stok & avg_cost produk
                $product = Products::find($productId);
                if ($product) {
                    ProductCostService::updateCostAndStock($product);
                    $product->stock_after_sales = $product->inventory_stock;
                    $product->save();
                }
            }

            $saleReturn->delete_notes = $request->input('delete_notes'); // catatan hapus dari form
            $saleReturn->deleted_by = Auth::id(); // user yang login
            $saleReturn->save();

            FinancialReport::where('reference_table', 'sale_returns')
                ->where('reference_id', $saleReturn->id)
                ->update(['deleted_at' => now()]);

            DB::commit();
            return redirect()->back()->with('success', 'Sale return berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale return delete failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus sale return: ' . $e->getMessage());
        }
    }

    public function forceDeleteOwner($id, Request $request)
    {
        // ⛔️ Batasi hanya untuk Owner
        if (!Auth::check() || Auth::user()->role !== 'Owner') {
            abort(403, 'Only Owner can force delete Sale Return.');
        }

        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $saleReturn = SaleReturn::with(['items'])->findOrFail($id);

            // 🔹 rollback stok produksi (canceled_product_stock)
            foreach ($saleReturn->items as $item) {
                $productId = $item->product_id;
                $qty       = $item->quantity;

                $productionStock = ProductionStock::firstOrCreate(
                    ['product_id' => $productId, 'production_warehouse_id' => 2],
                    [
                        'available_quantity'     => 0,
                        'finished_product_stock' => 0,
                        'pending_waiting_list'   => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                // rollback canceled stock (pastikan tidak minus)
                $beforeCanceled = (int) $productionStock->canceled_product_stock;
                $productionStock->canceled_product_stock = max(0, $beforeCanceled - $qty);
                $productionStock->save();

                // hapus ledger canceled_products
                CanceledProduct::where('sale_return_id', $saleReturn->id)
                    ->where('product_id', $productId)
                    ->where('order_item_id', $item->order_item_id)
                    ->delete();

                Log::info('Force delete SaleReturn rollback stok', [
                    'sale_return_id' => $saleReturn->id,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'before_canceled' => $beforeCanceled,
                    'after_canceled' => $productionStock->canceled_product_stock,
                ]);
            }

            // 🔹 hapus semua transaksi akunting yang terhubung
            AccountTransaction::where('sale_return_id', $saleReturn->id)->delete();

            // 🔹 hapus edit history (kalau ada relasi)
            if (method_exists($saleReturn, 'editHistories')) {
                $saleReturn->editHistories()->forceDelete();
            }

            // 🔹 hapus semua item SaleReturn
            if ($saleReturn->items()->exists()) {
                $saleReturn->items()->forceDelete();
            }

            // 🔹 hapus SaleReturn itu sendiri (force delete total)
            $saleReturn->delete_notes = $request->input('delete_notes');
            $saleReturn->deleted_by = Auth::id();
            $saleReturn->save();
            $saleReturn->forceDelete();

            // 🔹 tandai laporan keuangan
            FinancialReport::where('reference_table', 'sale_returns')
                ->where('reference_id', $saleReturn->id)
                ->update(['deleted_at' => now()]);

            DB::commit();
            return back()->with('success', 'Sale Return berhasil dihapus permanen oleh Owner.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Force delete SaleReturn failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal menghapus Sale Return: ' . $e->getMessage());
        }
    }

    public function getSaleReturnDetail($id)
    {
        $return = SaleReturn::with('items')->findOrFail($id);
        return view('erp.pages.sales.sale-return.detail-order', compact('return'));
    }

    public function markAsRefund($id, Request $request)
    {
        $request->merge([
            'refund_amount' => str_replace('.', '', $request->refund_amount),
        ]);

        $request->validate([
            'sale_return_id'        => 'required|exists:sale_returns,id',
            'refund_amount'           => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'transaction_date'      => 'required|date',
            'transaction_type'      => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'particular'            => 'nullable|string',
            'payment_proof'        => 'nullable|array',
            'payment_proof.*'      => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'       => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $saleReturn = SaleReturn::findOrFail($request->sale_return_id);

            // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
            $groupId = Str::uuid();

            $saleAccount     = Account::findOrFail($request->transaction_type); // Akun retur penjualan
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id); // Akun kas/bank

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            if ($request->hasFile('payment_proof')) {
                $uploadPath = public_path('uploads/payment_proofs');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);

                    $uploadedProofs[] = [
                        'file' => 'uploads/payment_proofs/' . $fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            // Simpan ke kolom proof (JSON)
            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // **Transaksi CREDIT (sale return refund ke customer)**
            // AccountTransaction::create([
            //     'sale_return_id'      => $saleReturn->id,
            //     'transaction_date'    => $request->transaction_date,
            //     'account_id'          => $saleAccount->id,
            //     'credit'              => 0,
            //     'debit'               => $request->refund_amount,
            //     'note'                => $request->note ?? '',
            //     'particular'          => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
            //     'transaction_group_id' => $groupId,
            // ]);

            // $saleAccount->closing_balance += $request->refund_amount;
            // $saleAccount->save();

            // **Transaksi DEBIT (kas/bank keluar)**
            AccountTransaction::create([
                'sale_return_id'      => $saleReturn->id,
                'order_number'        => $saleReturn->order_number,
                'transaction_date'    => $request->transaction_date,
                'account_id'          => $cashBankAccount->id,
                'debit'               => 0,
                'credit'              => $request->refund_amount,
                'note'                => $request->note ?? '',
                'particular'          => $saleAccount->name . ' - ' . $saleAccount->type,
                'transaction_group_id' => $groupId,
                'proof'               => $proofJson,
            ]);

            $cashBankAccount->closing_balance -= $request->refund_amount;
            $cashBankAccount->save();

            // **Update refund_amount & remaining_amount di SaleReturn**
            $saleReturn->refund_amount = ($saleReturn->refund_amount ?? 0) + $request->refund_amount;
            $saleReturn->remaining_amount = max(0, $saleReturn->total_amount - $saleReturn->refund_amount);

            // **Update Payment Status**
            if ($saleReturn->refund_amount <= 0) {
                $saleReturn->payment_status = 'Unpaid';
            } elseif ($saleReturn->refund_amount == $saleReturn->total_amount) {
                $saleReturn->payment_status = 'Refunded';
            } elseif ($saleReturn->refund_amount > $saleReturn->total_amount) {
                $saleReturn->payment_status = 'Overpaid';
            } else {
                $saleReturn->payment_status = 'Partially Paid';
            }

            // **Simpan transaction_group_id**
            // $saleReturn->transaction_group_id = $groupId;
            $saleReturn->save();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return redirect()->back()->with('success', 'Refund berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale Return Refund Failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return redirect()->back()->with('error', 'Gagal memproses refund: ' . $e->getMessage());
        }
    }

    public function getInvoice($id)
    {
        $order = SaleReturn::with('items')->findOrFail($id);
        $invoice = Invoice::with('termAndConditions')->first();
        return view('erp.pages.sales.invoice.index', compact('order', 'invoice'));
    }

    public function getPaymentHistory($id)
    {
        $saleReturn = SaleReturn::with('customer')->findOrFail($id);

        // Group transaksi per pembayaran
        $transactions = AccountTransaction::with('account')
            ->where('sale_return_id', $saleReturn->id)
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy('transaction_group_id');

        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.sales.sale-return.payment-history', [
            'saleReturn'   => $saleReturn,
            'transactions' => $transactions,
            'cashAccounts' => $cashAccounts,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    // public function updatePayment(Request $request, $groupId)
    // {
    //     $request->merge([
    //         'paid_amount' => str_replace('.', '', $request->paid_amount),
    //     ]);

    //     $request->validate([
    //         'transaction_date'      => 'required|date',
    //         'paid_amount'           => 'required|numeric|min:1',
    //         'cash_bank_account_id'  => 'required|exists:accounts,id',
    //         'note'                  => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
    //         if ($transactions->isEmpty()) {
    //             throw new \Exception("Refund not found");
    //         }

    //         $saleReturnId = $transactions->first()->sale_return_id;
    //         $saleReturn   = SaleReturn::findOrFail($saleReturnId);

    //         // cari transaksi credit lama (Cash/Bank)
    //         $oldCredit = $transactions->firstWhere('credit', '>', 0);
    //         if (!$oldCredit) {
    //             throw new \Exception("Credit transaction not found in this group");
    //         }

    //         $oldAccount = $oldCredit->account;
    //         $oldAmount  = $oldCredit->credit;

    //         // rollback saldo akun lama
    //         $oldAccount->closing_balance += $oldAmount;
    //         $oldAccount->save();

    //         // update transaksi credit lama → ganti akun/amount/date/note
    //         $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
    //         $oldCredit->update([
    //             'transaction_date' => $request->transaction_date,
    //             'account_id'       => $cashBankAccount->id,
    //             'credit'           => $request->paid_amount,
    //             'note'             => $request->note ?? '',
    //         ]);

    //         // update saldo akun baru
    //         $cashBankAccount->closing_balance -= $request->paid_amount;
    //         $cashBankAccount->save();

    //         // update juga tanggal/note untuk baris debit Sale Return biar sinkron
    //         $returnTrx = $transactions->firstWhere('debit', '>', 0);
    //         if ($returnTrx) {
    //             $returnTrx->update([
    //                 'transaction_date' => $request->transaction_date,
    //                 'note'             => $request->note ?? '',
    //             ]);
    //         }

    //         // hitung ulang refund status sale return (sum credit)
    //         $totalRefund = AccountTransaction::where('sale_return_id', $saleReturn->id)
    //             ->where('credit', '>', 0)
    //             ->sum('credit');

    //         $saleReturn->refund_amount    = $totalRefund;
    //         $saleReturn->remaining_amount = max(0, $saleReturn->total_amount - $totalRefund);

    //         if ($saleReturn->refund_amount == 0) {
    //             $saleReturn->payment_status = 'Unpaid';
    //         } elseif ($saleReturn->refund_amount < $saleReturn->total_amount) {
    //             $saleReturn->payment_status = 'Partially Paid';
    //         } elseif ($saleReturn->refund_amount == $saleReturn->total_amount) {
    //             $saleReturn->payment_status = 'Refunded';
    //         } else {
    //             $saleReturn->payment_status = 'Overpaid';
    //         }

    //         $saleReturn->save();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Refund berhasil diperbarui.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal update refund: ' . $e->getMessage());
    //     }
    // }

    public function updatePayment(Request $request, $groupId)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'transaction_date'      => 'required|date',
            'paid_amount'           => 'required|numeric|min:1',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'payment_proof'         => 'nullable|array',
            'payment_proof.*'       => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'        => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Refund not found");
            }

            $saleReturnId = $transactions->first()->sale_return_id;
            $saleReturn   = SaleReturn::findOrFail($saleReturnId);

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            // Ambil proof lama biar gak hilang
            $oldProofs = [];
            $oldProofJson = $transactions->first()?->proof;
            if ($oldProofJson && is_string($oldProofJson)) {
                $decoded = json_decode($oldProofJson, true);
                if (is_array($decoded)) {
                    $oldProofs = $decoded;
                }
            }

            if ($request->hasFile('payment_proof')) {
                $uploadPath = public_path('uploads/payment_proofs');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);

                    $path = 'uploads/payment_proofs/' . $fileName;
                    $uploadedProofs[] = [
                        'file' => str_replace('\\', '/', $path),
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            // 🔹 Kalau gak ada file baru → pakai proof lama tapi update note kalau dikirim ulang
            if (empty($uploadedProofs)) {
                foreach ($oldProofs as $index => &$proof) {
                    $proof['note'] = $notes[$index] ?? ($proof['note'] ?? '');
                }
                $uploadedProofs = $oldProofs;
            }

            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // =====================================================
            // 🔹 Refund Process (asli dari kode lama)
            // =====================================================
            $oldCredit = $transactions->firstWhere('credit', '>', 0);
            if (!$oldCredit) {
                throw new \Exception("Credit transaction not found in this group");
            }

            $oldAccount = $oldCredit->account;
            $oldAmount  = $oldCredit->credit;

            // rollback saldo akun lama
            $oldAccount->closing_balance += $oldAmount;
            $oldAccount->save();

            // update transaksi credit lama → ganti akun/amount/date/note/proof
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldCredit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'credit'           => $request->paid_amount,
                'note'             => $request->note ?? '',
                'proof'            => $proofJson, // 🔹 disimpan ke kolom proof
            ]);

            // update saldo akun baru
            $cashBankAccount->closing_balance -= $request->paid_amount;
            $cashBankAccount->save();

            // update juga tanggal/note untuk baris debit Sale Return biar sinkron
            $returnTrx = $transactions->firstWhere('debit', '>', 0);
            if ($returnTrx) {
                $returnTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note'             => $request->note ?? '',
                ]);
            }

            // hitung ulang refund status sale return
            $totalRefund = AccountTransaction::where('sale_return_id', $saleReturn->id)
                ->where('credit', '>', 0)
                ->sum('credit');

            $saleReturn->refund_amount    = $totalRefund;
            $saleReturn->remaining_amount = max(0, $saleReturn->total_amount - $totalRefund);

            if ($saleReturn->refund_amount == 0) {
                $saleReturn->payment_status = 'Unpaid';
            } elseif ($saleReturn->refund_amount < $saleReturn->total_amount) {
                $saleReturn->payment_status = 'Partially Paid';
            } elseif ($saleReturn->refund_amount == $saleReturn->total_amount) {
                $saleReturn->payment_status = 'Refunded';
            } else {
                $saleReturn->payment_status = 'Overpaid';
            }

            $saleReturn->save();

            DB::commit();
            return redirect()->back()->with('success', 'Refund berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update refund: ' . $e->getMessage());
        }
    }

    public function getEditHistory($id)
    {
        $saleReturn = SaleReturn::findOrFail($id);

        $histories = SaleReturnEditHistory::with('user')
            ->where('sale_return_id', $id)
            ->orderBy('edited_at', 'desc')
            ->get();

        return view('erp.pages.sales.sale-return.edit-order-histories', compact('saleReturn', 'histories'));
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();

        try {
            $saleReturn = SaleReturn::onlyTrashed()->findOrFail($id);

            // 🔥 trigger booted() => otomatis hapus semua relasi
            $saleReturn->forceDelete();

            FinancialReport::withTrashed()
                ->where('reference_table', 'sale_returns')
                ->where('reference_id', $saleReturn->id)
                ->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Order beserta item & relasinya berhasil dihapus permanen!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete sale return gagal', [
                'sale_return_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus permanen order!');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $saleReturn = SaleReturn::onlyTrashed()
                ->with('items') // ambil items sekalian
                ->findOrFail($id);

            // ✅ Restore saleReturn + relasi items
            $saleReturn->restore();
            if (method_exists($saleReturn, 'items')) {
                $saleReturn->items()->withTrashed()->restore();
            }

            // ✅ Restore transaksi terkait
            $transactions = AccountTransaction::withTrashed()
                ->where(function ($q) use ($saleReturn) {
                    $q->where('sale_return_id', $saleReturn->id)
                        ->orWhere('note', 'like', '%[SaleReturn deleted]%');
                })
                ->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Sale Return') {
                    // aktifkan kembali transaksi Sale Return Account
                    if ($trx->trashed()) {
                        $trx->restore();
                    }

                    // rollback saldo Sale Return Account
                    if ($trx->debit > 0) {
                        $account->closing_balance += $trx->debit;
                    }
                    if ($trx->credit > 0) {
                        $account->closing_balance -= $trx->credit;
                    }
                } else {
                    // Cash / Bank account → balikin sale_return_id
                    $trx->sale_return_id = $saleReturn->id;
                    $trx->note = str_replace('[SaleReturn deleted]', '', $trx->note ?? '');
                    $trx->save();
                }

                $account->save();
            }

            // ✅ Tambahkan kembali canceled_product_stock + update cost produk
            foreach ($saleReturn->items as $item) {
                $productId = $item->product_id;
                $qty       = $item->quantity;

                $productionStock = ProductionStock::where('product_id', $productId)->first();
                if ($productionStock) {
                    $productionStock->increment('canceled_product_stock', $qty);
                }

                // Update ulang stok & avg_cost produk
                $product = Products::find($productId);
                if ($product) {
                    ProductCostService::updateCostAndStock($product);
                    $product->stock_after_sales = $product->inventory_stock;
                    $product->save();
                }
            }

            FinancialReport::withTrashed()
                ->where('reference_table', 'sale_returns')
                ->where('reference_id', $saleReturn->id)
                ->update(['deleted_at' => null]);

            DB::commit();
            return redirect()->back()->with('success', 'Sale Return berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore sale return gagal', [
                'sale_return_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan sale return!');
        }
    }
}
