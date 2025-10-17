<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use App\Models\Customers;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAddresses;
use App\Models\Discount;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Products;
use App\Models\PurchaseProduct;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\ProductCombination;
use App\Models\Bank;
use App\Models\CanceledProduct;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\FinancialReport;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\OrderEditHistory;
use App\Models\OrderItemComponent;
use App\Models\OrderProgress;
use App\Models\OrderProgressHistory;
use App\Models\OrderProgressItem;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\ProductionStock;
use App\Models\SaleReturn;
use App\Services\ProductCostService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaleListController extends Controller
{
    public function getSaleList()
    {
        $order_number = Order::first();
        $transactionTypes = Account::where('name', 'Order')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        // $saleReturnAccount = Account::whereIn('type', ['Sale Return'])
        //     ->firstOrFail();

        return view('erp.pages.sales.sale-list.sale-list', compact('order_number', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function dataSaleList(Request $request)
    {
        $orders = Order::with('customer')
            ->where('status', 'sale list');

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $orders->whereDate('order_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $orders->whereBetween('order_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $orders->whereMonth('order_date', Carbon::now()->month)
                        ->whereYear('order_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $orders->whereBetween('order_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $orders->whereBetween('order_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $orders->whereYear('order_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $orders->whereBetween('order_date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $orders->whereIn('payment_status', ['Paid', 'Overpaid']);
            } else {
                $orders->where('payment_status', $request->payment_status);
            }
        } elseif ($request->search_type === 'due_date') {
            $direction = strtolower($request->due_date_order ?? 'asc');

            if ($direction === 'asc') {
                $orders->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('due_date', 'asc');
            } else {
                $orders->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('due_date', 'desc');
            }
        } elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $orders->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $orders->where('order_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        $orders = $orders->latest()->get();

        return DataTables::of($orders)
            ->addIndexColumn()
            // ->addColumn('order_number', function ($order) {
            //     return $order->order_number;
            // })
            ->addColumn('order_number', function ($order) {
                $date = Carbon::parse($order->order_date)->format('j M y');
                $dueDate = $order->due_date ? Carbon::parse($order->due_date)->format('j M y') : '-';

                $editedBadge = $order->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                // 🔎 Tambahkan badge Sale Return jika order punya sale return
                $returnBadge = $order->saleReturns()->exists()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Sale Return</span></div>'
                    : '';

                return $returnBadge . '
                    <div>
                        <div>' . $order->order_number . $editedBadge . '</div>
                        <small class="text-muted">' . $date . '</small>,
                        <small class="text-danger">Due: ' . $dueDate . '</small>
                    </div>';
            })
            ->addColumn('order_date', function ($order) {
                return Carbon::parse($order->order_date)->format('j M y');
            })
            ->addColumn('customer', function ($order) {
                return $order->customer->name;
            })
            ->addColumn('total_amount', function ($order) {
                return 'Rp ' . number_format($order->total_amount, 0, ',', '.');
            })
            ->addColumn('discount', function ($order) {
                return '<span class="text-warning">Rp ' . number_format($order->discount, 0, ',', '.') . '</span>';
            })
            ->addColumn('grand_total', function ($order) {
                return '<span class="text-primary">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>';
            })
            ->addColumn('paid_amount', function ($order) {
                return '<span class="text-success">Rp ' . number_format($order->paid_amount, 0, ',', '.') . '</span>';
            })
            ->addColumn('remaining_amount', function ($order) {
                return '<span class="text-danger">Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</span>';
            })
            ->addColumn('payment_status', function ($order) {
                $status = $order->payment_status; // langsung dari DB

                switch (strtolower($status)) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $status . '</div>';
                    case 'unpaid':
                        return '<div class="badge bg-soft-dark text-dark">' . $status . '</div>';
                    case 'overdue':
                        return '<div class="badge bg-soft-danger text-danger">' . $status . '</div>';
                    case 'overpaid':
                        return '<div class="badge bg-soft-primary text-primary">' . $status . '</div>';
                    case 'partially paid':
                        return '<div class="badge bg-soft-warning text-warning">' . $status . '</div>';
                    default:
                        return '<div class="badge bg-secondary">' . $status . '</div>';
                }
            })
            ->addColumn('status', function ($order) {
                $status = strtolower($order->status); // buat lebih aman lowercase dulu

                switch ($status) {
                    case 'sale list':
                        $badgeClass = 'bg-soft-dark text-dark';
                        break;
                }

                return '<div class="badge ' . $badgeClass . '">' . ucfirst($status) . '</div>';
            })
            // ->addColumn('notes', function ($order) {
            //     return $order->notes;
            // })
            // ->addColumn('products', function ($order) {
            //     return view('erp.pages.sales.sale-list.partials.product-list', compact('order'))->render();
            // })
            ->addColumn('products', function ($row) {
                return $row->orderItems->map(function ($item) {
                    // Cek apakah item punya product atau productBundle
                    $name = $item->product ? $item->product->name : ($item->productBundle ? $item->productBundle->name : '-');

                    return [
                        'name'  => $name,
                        'sku'   => $item->product ? $item->product->sku : ($item->productBundle ? $item->productBundle->sku : '-'),
                        'qty'   => $item->quantity,
                        'price' => number_format($item->price ?? 0, 0, ',', '.')
                    ];
                })->toArray();
            })
            ->addColumn('payment_method', function ($order) {
                return $order->payment_method;
            })
            ->addColumn('action', function ($order) {
                $order->is_fully_returned = $order->orderItems->every(function ($item) use ($order) {
                    $returnedQty = \App\Models\SaleReturnItem::where('product_id', $item->product_id)
                        ->whereHas('saleReturn', function ($q) use ($order) {
                            $q->where('sale_order_id', $order->id);
                        })->sum('quantity');
                    return $returnedQty >= $item->quantity;
                });

                return view('erp.pages.sales.sale-list.partials.action-button', compact('order'))->render();
            })
            ->rawColumns(['order_number', 'grand_total', 'discount', 'paid_amount', 'remaining_amount', 'payment_status', 'status', 'action', 'products'])
            ->make(true);
    }

    public function dataDeletedSaleList(Request $request)
    {
        $orders = Order::onlyTrashed()
            ->with(['customer', 'orderItems.product', 'orderItems.productBundle'])
            ->where('status', 'sale list');

        // Filter by customer (optional sama kayak SaleList)
        if ($request->search_type === 'customer' && $request->filled('search_keyword')) {
            $orders->whereHas('customer', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search_keyword . '%');
            });
        }

        $orders = $orders->latest()->get();

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('order_number', function ($order) {
                $date = \Carbon\Carbon::parse($order->order_date)->format('j M y');
                return '<div>
                <div>' . $order->order_number . '</div>
                <small class="text-muted">' . $date . '</small>
            </div>';
            })
            ->addColumn('customer', fn($order) => $order->customer->name ?? '-')
            ->addColumn('grand_total', fn($order) => '<span class="text-primary">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>')
            ->addColumn('deleted_at', fn($order) => $order->deleted_at ? $order->deleted_at->format('j M y H:i') : '-')
            ->addColumn('products', function ($row) {
                return $row->orderItems->map(function ($item) {
                    $name = $item->product
                        ? $item->product->name
                        : ($item->productBundle ? $item->productBundle->name : '-');

                    return [
                        'name'  => $name,
                        'sku'   => $item->product
                            ? $item->product->sku
                            : ($item->productBundle ? $item->productBundle->sku : '-'),
                        'qty'   => $item->quantity,
                        'price' => number_format($item->price ?? 0, 0, ',', '.')
                    ];
                })->toArray();
            })
            ->addColumn('delete_notes', fn($order) => $order->delete_notes ?? '-')
            ->addColumn('deleted_by', fn($order) => $order->deletedByUser->name ?? '-')
            ->addColumn('action', function ($order) {
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    return '
                        <div class="d-flex gap-2">
                            <button type="button" 
                                class="btn btn-success btn-sm me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRestoreOrder"
                                data-id="' . $order->id . '" 
                                data-name="' . $order->order_number . '"
                                data-url="' . route('sales.restore', $order->id) . '">
                                    Restore
                            </button>
                            <button type="button" 
                                class="btn btn-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modalForceDeleteOrder"
                                data-id="' . $order->id . '" 
                                data-name="' . $order->order_number . '"
                                data-url="' . route('sales.forceDelete', $order->id) . '">
                                    Hapus Permanen
                            </button>
                        </div>
                    ';
                }

                // kalau bukan Owner -> kosong
                return '';
            })

            ->rawColumns(['order_number', 'grand_total', 'action', 'products'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::with(['categories', 'discounts', 'categories.discounts'])->get();

        $productBundles = ProductBundle::with(['items.product.categories.discounts', 'items.product.discounts'])->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'discounts' => $product->discounts->toArray(),
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'discounts' => $cat->discounts->map(function ($d) use ($cat) {
                            return array_merge($d->toArray(), ['category_id' => $cat->id]);
                        })->toArray()
                    ];
                })->toArray(),
            ];
        })->toArray();

        $productBundlesJson = $productBundles->map(function ($bundle) {
            $bundleDiscounts = [];
            $bundleCategories = [];

            foreach ($bundle->items as $item) {
                $product = $item->product;

                // Diskon langsung dari produk
                foreach ($product->discounts as $discount) {
                    $bundleDiscounts[] = $discount;
                }

                // Kategori + diskon kategori
                foreach ($product->categories as $cat) {
                    $bundleCategories[] = [
                        'id' => $cat->id,
                        'discounts' => $cat->discounts->map(function ($d) use ($cat) {
                            return array_merge($d->toArray(), ['category_id' => $cat->id]);
                        })->toArray()
                    ];
                }
            }

            return [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'sku'  => $bundle->sku,
                'price' => $bundle->price,
                'discounts' => $bundleDiscounts,
                'categories' => $bundleCategories,
            ];
        })->toArray();

        $customers = Customers::with('addresses')->get();
        $discount = Discount::first();
        $transactionTypes = Account::where('name', 'Order')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.sales.sale-list.create-order', compact('products', 'productBundles', 'customers', 'discount', 'cashAccounts', 'bankAccounts', 'transactionTypes', 'productsJson', 'productBundlesJson'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_date'           => 'required|date',
            'due_date_option'      => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'      => 'nullable|date',
            'customers'            => 'required|array',
            'customers.*'          => 'exists:customers,id',
            'addresses'            => 'required|array',
            'addresses.*'          => 'exists:customer_addresses,id',
            'notes'                => 'nullable|string',
            'product_type'         => 'required|array',
            'product_type.*'       => 'in:satuan,bundle',
            'product'              => 'required|array',
            'product.*'            => 'required',
            'qty'                  => 'required|array',
            'qty.*'                => 'numeric|min:1',
            'price_before_discount' => 'required|array',
            'price_before_discount.*' => 'numeric|min:0',
            'total_before_discount' => 'required|array',
            'total_before_discount.*' => 'numeric|min:0',
            'price_after_discount' => 'required|array',
            'price_after_discount.*' => 'numeric|min:0',
            'total_after_discount' => 'required|array',
            'total_after_discount.*' => 'numeric|min:0',
            'sub_total'            => 'required|numeric|min:0',
            'total_discount'       => 'required|numeric|min:0',
            'total_amount'         => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $paidAmount = $request->paid_amount ?? 0;
            $remainingAmount = $request->total_amount - $paidAmount;
            $status = 'Sale List';
            $paymentMethod = 'Sale Account';
            $warehouseId = $request->inventory_warehouse_id ?? 1;

            $orderDate = Carbon::parse($request->order_date);

            $dueDate = null;
            switch ($request->due_date_option) {
                case 'today':
                    $dueDate = $orderDate;
                    break;
                case '1_week':
                    $dueDate = $orderDate->copy()->addWeek();
                    break;
                case '1_month':
                    $dueDate = $orderDate->copy()->addMonth();
                    break;
                case '3_months':
                    $dueDate = $orderDate->copy()->addMonths(3);
                    break;
                case 'custom':
                    $dueDate = $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null;
                    break;
                default:
                    $dueDate = null; // none
            }

            $lastOrder = Order::withTrashed() // ✅ tambahkan ini agar termasuk soft deleted
                ->whereDate('order_date', $orderDate)
                ->orderByDesc('id')
                ->first();

            $lastSequence = 0;

            if ($lastOrder) {
                // Ambil nomor terakhir, misalnya "INV/5/ALS/150925"
                if (preg_match('/INV\/(\d+)\/ALS/', $lastOrder->order_number, $matches)) {
                    $lastSequence = (int) $matches[1];
                }
            }

            $orderNumber = sprintf(
                "INV/%d/ALS/%s",
                $lastSequence + 1,
                $orderDate->format('dmy')
            );

            $addressModel = CustomerAddresses::find($request->addresses[0]);

            // ================== BUAT ORDER ==================
            $order = Order::create([
                'customer_id'      => $request->customers[0],
                'order_number'     => $orderNumber,
                'order_date'       => $request->order_date,
                'due_date'         => $dueDate,
                'payment_method'   => $paymentMethod,
                'status'           => $status,
                'payment_status'   => ($paidAmount <= 0) ? 'Unpaid' : (($paidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid'),
                'paid_amount'      => $paidAmount,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
            ]);

            // === BUAT ORDER ITEMS ===
            foreach ($request->product as $index => $productInputId) {
                $type = strtolower($request->product_type[$index]);
                $qty  = (float) $request->qty[$index];

                // --- PRODUK SATUAN ---
                if ($type === 'satuan') {
                    $product = Products::findOrFail($productInputId);
                    $inventoryStock = InventoryStock::where('product_id', $product->id)->first();
                    $avgCost = $inventoryStock?->avg_cost ?? 0;

                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => $product->id,
                        'product_bundle_id'    => null,
                        'status'               => $paymentMethod,
                        'product_name'         => $product->name,
                        'satuan'               => 'satuan',
                        'quantity'             => $qty,
                        'completed_quantity'   => 0,
                        'stock_out'            => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    // === Simpan ke components
                    OrderItemComponent::create([
                        'order_item_id'    => $orderItem->id,
                        'product_id'       => $product->id,
                        'qty'              => $qty,
                        'avg_cost_at_sale' => $avgCost,
                        'total_cost'       => $avgCost * $qty,
                    ]);

                    // update stok
                    InventoryStock::updateOrCreate(
                        ['product_id' => $product->id, 'inventory_warehouse_id' => $warehouseId],
                        []
                    )->decrement('stock_after_sales', $qty);
                }

                // --- PRODUK BUNDLE ---
                elseif ($type === 'bundle') {
                    $bundle = ProductBundle::with('items.product')->findOrFail($productInputId);

                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => null,
                        'product_bundle_id'    => $bundle->id,
                        'status'               => $paymentMethod,
                        'product_name'         => $bundle->name,
                        'satuan'               => 'bundle',
                        'quantity'             => $qty,
                        'completed_quantity'   => 0,
                        'stock_out'            => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    foreach ($bundle->items as $bundleItem) {
                        $component = $bundleItem->product;
                        if (!$component) continue;

                        $stock = InventoryStock::where('product_id', $component->id)->first();
                        $avgCost = $stock?->avg_cost ?? 0;
                        $totalQty = $qty; // ✅ ambil dari order item, bukan bundleItem->qty

                        OrderItemComponent::create([
                            'order_item_id'    => $orderItem->id,
                            'product_id'       => $component->id,
                            'qty'              => $totalQty,
                            'avg_cost_at_sale' => $avgCost,
                            'total_cost'       => $avgCost * $totalQty,
                        ]);

                        InventoryStock::updateOrCreate(
                            ['product_id' => $component->id, 'inventory_warehouse_id' => $warehouseId],
                            []
                        )->decrement('stock_after_sales', $totalQty);
                    }
                }
            }

            $orderProgress = OrderProgress::create([
                'order_id' => $order->id,
                'date'     => now()->format('Y-m-d'),
                'status'   => 'Pending',
                'notes'    => null,
                'invoice_number' => $order->order_number,

            ]);

            foreach ($order->orderItems as $orderItem) {
                $qty = $orderItem->quantity;

                if ($orderItem->satuan === 'satuan') {
                    // buat progress biasa
                    OrderProgressItem::create([
                        'order_progress_id' => $orderProgress->id,
                        'order_item_id'     => $orderItem->id,
                        'product_id'        => $orderItem->product_id,
                        'quantity'          => $qty,
                        'completed_quantity' => 0,
                    ]);
                } elseif ($orderItem->satuan === 'bundle') {
                    // buat progress per product dalam bundle
                    foreach ($orderItem->productBundle->items as $productBundle) {
                        $bundleProduct = $productBundle->product;
                        if (!$bundleProduct) continue;

                        OrderProgressItem::create([
                            'order_progress_id' => $orderProgress->id,
                            'order_item_id'     => $orderItem->id,
                            'product_id'        => $bundleProduct->id,
                            'quantity'          => $qty,
                            'completed_quantity' => 0,
                        ]);
                    }
                }
            }

            // ================== BUAT DELIVERY ORDER ==================
            $deliveryOrder = DeliveryOrder::create([
                'order_id'        => $order->id,
                'delivery_number' => 'DO/' . $orderNumber, // bisa pakai format sendiri
                'delivery_date'   => now()->format('Y-m-d'),
                'note'            => $request->notes ?? '',
                'status'          => 'Ongoing', // default
                'shipping_address' => $addressModel?->address,
                'created_by'      => Auth::id(),
            ]);

            // Isi Delivery Order Item dari OrderProgressItem
            foreach ($orderProgress->items as $progressItem) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'order_progress_id' => $orderProgress->id,
                    'order_item_id'     => $progressItem->order_item_id,
                    'product_id'        => $progressItem->product_id,
                    'status'            => $orderProgress->status, // Pending
                    'progress_qty'      => $progressItem->quantity,
                    'ready_qty'         => 0, // belum siap kirim
                    'note'              => null,
                ]);
            }

            $groupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type); // Akun pembelian (debit)

            AccountTransaction::create([
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'transaction_date' => $request->order_date,
                'account_id' => $saleAccount->id,
                'debit' => 0,
                'credit' => $request->total_amount,
                'note' => $request->note ?? '',
                'particular' => '',
                'transaction_group_id' => $groupId,
            ]);

            $saleAccount->closing_balance += $request->total_amount;
            $saleAccount->save();

            // ================== CATAT FINANCIAL REPORT ==================
            try {
                $totalRevenue = $request->total_amount;
                $totalCogs = 0;

                // Hitung total COGS berdasarkan avg_cost per produk
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {
                        // Produk satuan
                        $product = $orderItem->product;
                        $avgCost = $product->inventoryStock->avg_cost ?? 0;
                        $totalCogs += $avgCost * $orderItem->quantity;
                    } elseif ($orderItem->product_bundle_id) {
                        // Produk bundle
                        $bundle = $orderItem->productBundle;
                        $bundleCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->inventoryStock->avg_cost ?? 0;
                        });
                        $totalCogs += $bundleCost * $orderItem->quantity;
                    }
                }

                $grossProfit = $totalRevenue - $totalCogs;

                FinancialReport::create([
                    'date'             => $order->order_date,
                    'transaction_type' => 'sale',
                    'reference_id'     => $order->id,
                    'reference_table'  => 'orders',
                    'revenue'          => $totalRevenue,
                    'cogs'             => $totalCogs,
                    'gross_profit'     => $grossProfit,
                    'expense'          => 0,
                    'net_profit'       => $grossProfit,
                    'notes'            => 'Auto-generated from Sale List',
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan laporan keuangan untuk Order ID ' . $order->id . ': ' . $e->getMessage());
            }

            DB::commit();
            return redirect("/erp/sales/sale-list/")->with('success', 'Order berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store order: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan order: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $order = Order::with('orderItems', 'customer.addresses')->findOrFail($id);

        // 🔹 Tentukan default due_date_option berdasarkan nilai due_date
        $dueDateOption = 'none';
        $customDueDate = null;

        if ($order->due_date) {
            $today = now()->startOfDay();
            $due = \Carbon\Carbon::parse($order->due_date)->startOfDay();

            if ($due->equalTo($today)) {
                $dueDateOption = 'today';
            } elseif ($due->equalTo($today->copy()->addWeek())) {
                $dueDateOption = '1_week';
            } elseif ($due->equalTo($today->copy()->addMonth())) {
                $dueDateOption = '1_month';
            } elseif ($due->equalTo($today->copy()->addMonths(3))) {
                $dueDateOption = '3_months';
            } else {
                $dueDateOption = 'custom';
                $customDueDate = $due->toDateString();
            }
        }

        // 🔹 Data lain tetap sama
        $productBundles = ProductBundle::all();
        $productBundles->map(function ($bundle) {
            $bundle->discounts = [];
            return $bundle;
        });

        $products = Products::all();
        $customers = Customers::with('addresses')->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'discounts' => $product->discounts->toArray(),
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'discounts' => $cat->discounts->map(function ($d) use ($cat) {
                            return array_merge($d->toArray(), ['category_id' => $cat->id]);
                        })->toArray()
                    ];
                })->toArray(),
            ];
        })->toArray();

        $productBundlesJson = $productBundles->map(function ($bundle) {
            $bundleDiscounts = [];
            $bundleCategories = [];

            foreach ($bundle->items as $item) {
                $product = $item->product;

                foreach ($product->discounts as $discount) {
                    $bundleDiscounts[] = $discount;
                }

                foreach ($product->categories as $cat) {
                    $bundleCategories[] = [
                        'id' => $cat->id,
                        'discounts' => $cat->discounts->map(function ($d) use ($cat) {
                            return array_merge($d->toArray(), ['category_id' => $cat->id]);
                        })->toArray()
                    ];
                }
            }

            return [
                'id' => $bundle->id,
                'name' => $bundle->name,
                'sku'  => $bundle->sku,
                'price' => $bundle->price,
                'discounts' => $bundleDiscounts,
                'categories' => $bundleCategories,
            ];
        })->toArray();

        // 🔹 kirim juga due_date_option & custom_due_date
        return view('erp.pages.sales.sale-list.edit-order', compact(
            'order',
            'products',
            'customers',
            'productBundles',
            'productsJson',
            'productBundlesJson',
            'dueDateOption',
            'customDueDate'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'order_date'              => 'required|date',
            'due_date_option'         => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'         => 'nullable|date',
            'customers'               => 'required|array',
            'customers.*'             => 'exists:customers,id',
            'address_id'              => 'required|exists:customer_addresses,id',
            'notes'                   => 'nullable|string',
            'product_type'            => 'required|array',
            'product_type.*'          => 'in:satuan,bundle',
            'product'                 => 'required|array',
            'product.*'               => 'required',
            'qty'                     => 'required|array',
            'qty.*'                   => 'numeric|min:1',
            'price_before_discount'   => 'required|array',
            'price_before_discount.*' => 'numeric|min:0',
            'total_before_discount'   => 'required|array',
            'total_before_discount.*' => 'numeric|min:0',
            'price_after_discount'    => 'required|array',
            'price_after_discount.*'  => 'numeric|min:0',
            'total_after_discount'    => 'required|array',
            'total_after_discount.*'  => 'numeric|min:0',
            'sub_total'               => 'required|numeric|min:0',
            'total_discount'          => 'required|numeric|min:0',
            'total_amount'            => 'required|numeric|min:0',
            'edit_note'               => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems')->findOrFail($id);

            // 🔎 Cek apakah order punya Sale Return
            if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
                DB::rollBack(); // rollback supaya transaksi clear
                return back()->with('error', 'Tidak bisa mengupdate order ini karena sudah ada Sale Return.');
            }

            // ===== helper kecil buat bikin map item (key: satuan_{product_id} / bundle_{bundle_id})
            $mapItems = function ($items) {
                return $items->mapWithKeys(function ($item) {
                    $key = $item->satuan === 'satuan'
                        ? 'satuan_' . $item->product_id
                        : 'bundle_' . $item->product_bundle_id;

                    return [$key => [
                        'product'         => $item->product_name,
                        'satuan'          => $item->satuan,
                        'product_id'      => $item->product_id,
                        'bundle_id'       => $item->product_bundle_id,
                        'quantity'        => (int) $item->quantity,
                        'price'           => (float) $item->price,
                        'subtotal'        => (float) $item->subtotal,
                        'discount_price'  => (float) $item->discount_price,
                        'total'           => (float) $item->total_after_discount,
                    ]];
                });
            };

            // ===== 1) SNAPSHOT LAMA (ORDER & ITEMS)
            $oldOrderDataAll = Arr::except($order->toArray(), ['created_at', 'updated_at']);
            // pilih field header yang mau kamu track (boleh tambah/kurang)
            $orderFieldsToTrack = [
                'customer_id',
                'order_number',
                'order_date',
                'due_date',
                'status',
                'payment_method',
                'payment_status',
                'discount',
                'total_amount',
                'grand_total',
                'paid_amount',
                'remaining_amount',
                'shipping_address',
                'google_maps',
                'notes'
            ];
            $oldOrderData = Arr::only($oldOrderDataAll, $orderFieldsToTrack);
            $oldItemsData = $mapItems($order->orderItems); // keyed map

            // ===== 2) UPDATE HEADER ORDER
            $orderDate = Carbon::parse($request->order_date);

            // 🔹 Tentukan due_date berdasarkan option
            $dueDate = null;
            switch ($request->due_date_option) {
                case 'today':
                    $dueDate = $orderDate;
                    break;
                case '1_week':
                    $dueDate = $orderDate->copy()->addWeek();
                    break;
                case '1_month':
                    $dueDate = $orderDate->copy()->addMonth();
                    break;
                case '3_months':
                    $dueDate = $orderDate->copy()->addMonths(3);
                    break;
                case 'custom':
                    $dueDate = $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null;
                    break;
                default:
                    $dueDate = null; // none
            }

            $oldPaidAmount   = $order->paid_amount;
            $newPaidAmount   = $request->has('paid_amount')
                ? $request->paid_amount
                : $order->paid_amount;
            $additionalPay   = max(0, $newPaidAmount - $oldPaidAmount);
            $remainingAmount = $request->total_amount - $newPaidAmount;

            $status        = 'Sale List';
            $paymentMethod = 'Sale Account';
            $addressModel  = CustomerAddresses::find($request->address_id);

            // ================== UPDATE ORDER HEADER ==================
            $order->update([
                'customer_id'      => $request->customers[0],
                'order_date'       => $request->order_date,
                'due_date'         => $dueDate,
                'payment_method'   => $paymentMethod,
                'status'           => $status,
                'payment_status'   => ($newPaidAmount <= 0) ? 'Unpaid' : (($newPaidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid'),
                'paid_amount'      => $newPaidAmount,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
            ]);

            // ================== SYNC ORDER ITEMS ==================
            $existingItems = $order->orderItems->keyBy(function ($item) {
                if ($item->satuan === 'satuan') {
                    return 'satuan_' . $item->product_id;
                }
                return 'bundle_' . $item->product_bundle_id;
            });

            $newKeys = [];

            foreach ($request->product as $index => $productValue) {
                // productValue bisa "satuan_5" atau "bundle_9"
                [$type, $productId] = explode('_', $productValue);

                $qty = (int) $request->qty[$index];
                $key = "{$type}_{$productId}";
                $newKeys[] = $key;

                $warehouseId = $request->inventory_warehouse_id ?? 1;

                if ($existingItems->has($key)) {
                    // update item lama
                    $orderItem = $existingItems[$key];
                    $diffQty   = $qty - $orderItem->quantity;

                    $orderItem->update([
                        'quantity'             => $qty,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    // === HANDLE COMPONENTS ===
                    if ($type === 'satuan') {
                        $product = Products::findOrFail($productId);
                        $stock   = InventoryStock::where('product_id', $product->id)->first();
                        $avgCost = $stock?->avg_cost ?? 0;

                        // update atau buat component
                        $component = $orderItem->components()->first();
                        if ($component) {
                            $component->update([
                                'qty'              => $qty,
                                'avg_cost_at_sale' => $avgCost,
                                'total_cost'       => $avgCost * $qty,
                            ]);
                        } else {
                            $orderItem->components()->create([
                                'product_id'       => $product->id,
                                'qty'              => $qty,
                                'avg_cost_at_sale' => $avgCost,
                                'total_cost'       => $avgCost * $qty,
                            ]);
                        }
                    } elseif ($type === 'bundle') {
                        $bundle = ProductBundle::with('items.product')->findOrFail($productId);

                        foreach ($bundle->items as $bundleItem) {
                            $component = $bundleItem->product;
                            if (!$component) continue;

                            $stock   = InventoryStock::where('product_id', $component->id)->first();
                            $avgCost = $stock?->avg_cost ?? 0;
                            $totalQty = $qty; // ⚡ ambil dari order item, bukan dari bundleItem->quantity

                            $existing = $orderItem->components()
                                ->where('product_id', $component->id)
                                ->first();

                            if ($existing) {
                                $existing->update([
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'total_cost'       => $avgCost * $totalQty,
                                ]);
                            } else {
                                $orderItem->components()->create([
                                    'product_id'       => $component->id,
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'total_cost'       => $avgCost * $totalQty,
                                ]);
                            }
                        }
                    }

                    if ($diffQty !== 0) {
                        if ($type === 'satuan') {
                            $inventoryStock = InventoryStock::firstOrCreate(
                                [
                                    'product_id'             => $productId,
                                    'inventory_warehouse_id' => $warehouseId,
                                ],
                                ['stock_after_sales' => 0]
                            );

                            if ($diffQty > 0) {
                                $inventoryStock->decrement('stock_after_sales', $diffQty);
                            } else {
                                $inventoryStock->increment('stock_after_sales', abs($diffQty));
                            }
                        } elseif ($type === 'bundle') {
                            $bundle = ProductBundle::with('items.product')->find($productId);
                            if ($bundle) {
                                foreach ($bundle->items as $bundleItem) {
                                    $inventoryStock = InventoryStock::firstOrCreate(
                                        [
                                            'product_id'             => $bundleItem->product_id,
                                            'inventory_warehouse_id' => $warehouseId,
                                        ],
                                        ['stock_after_sales' => 0]
                                    );

                                    if ($diffQty > 0) {
                                        $inventoryStock->decrement('stock_after_sales', $diffQty);
                                    } else {
                                        $inventoryStock->increment('stock_after_sales', abs($diffQty));
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // insert item baru
                    if ($type === 'satuan') {
                        $product = Products::findOrFail($productId);

                        // Buat order item
                        OrderItem::create([
                            'order_id'             => $order->id,
                            'product_id'           => $product->id,
                            'product_bundle_id'    => null,
                            'product_name'         => $product->name,
                            'satuan'               => 'satuan',
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        // Update stock_after_sales hanya di inventory_stocks
                        $inventoryStock = InventoryStock::firstOrCreate(
                            [
                                'product_id'             => $product->id,
                                'inventory_warehouse_id' => $warehouseId,
                            ],
                            ['stock_after_sales' => 0]
                        );
                        $inventoryStock->decrement('stock_after_sales', $qty);
                    } elseif ($type === 'bundle') {
                        $bundle = ProductBundle::with('items.product')->findOrFail($productId);

                        // Buat order item untuk bundle
                        OrderItem::create([
                            'order_id'             => $order->id,
                            'product_id'           => null,
                            'product_bundle_id'    => $bundle->id,
                            'product_name'         => $bundle->name,
                            'satuan'               => 'bundle',
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        // Kurangi stock untuk setiap item di dalam bundle
                        foreach ($bundle->items as $bundleItem) {
                            if (!$bundleItem->product_id) continue;

                            $inventoryStock = InventoryStock::firstOrCreate(
                                [
                                    'product_id'             => $bundleItem->product_id,
                                    'inventory_warehouse_id' => $warehouseId,
                                ],
                                ['stock_after_sales' => 0]
                            );
                            $inventoryStock->decrement('stock_after_sales', $qty);
                        }
                    }
                }
            }

            foreach ($existingItems as $key => $item) {
                if (!in_array($key, $newKeys)) {
                    if ($item->satuan === 'satuan') {
                        $inventoryStock = InventoryStock::firstOrCreate(
                            ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
                            ['stock_after_sales' => 0]
                        );
                        $inventoryStock->increment('stock_after_sales', $item->quantity);
                    } else {
                        $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                $inventoryStock = InventoryStock::firstOrCreate(
                                    ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
                                    ['stock_after_sales' => 0]
                                );
                                $inventoryStock->increment('stock_after_sales', $item->quantity);
                            }
                        }
                    }
                    $item->components()->delete();
                    $item->forceDelete();
                }
            }

            // ===== 4) SNAPSHOT BARU (REFRESH)
            $order->load('orderItems'); // refresh relasi tanpa kehilangan instance
            $newOrder = $order->fresh(); // hard refresh kolom
            $newOrderDataAll = Arr::except($newOrder->toArray(), ['created_at', 'updated_at']);
            $newOrderData = Arr::only($newOrderDataAll, $orderFieldsToTrack);
            $newItemsData = $mapItems($newOrder->orderItems);

            // ===== 5) DIFF (ORDER & ITEMS)
            $orderDiff = ['old' => [], 'new' => []];
            foreach ($newOrderData as $field => $newVal) {
                $oldVal = $oldOrderData[$field] ?? null;
                if ($oldVal != $newVal) {
                    $orderDiff['old'][$field] = $oldVal;
                    $orderDiff['new'][$field] = $newVal;
                }
            }

            $itemsDiff = [];
            $allKeys = array_unique(array_merge(array_keys($oldItemsData->toArray()), array_keys($newItemsData->toArray())));
            foreach ($allKeys as $key) {
                $old = $oldItemsData[$key] ?? null;
                $new = $newItemsData[$key] ?? null;

                if ($old && !$new) {
                    // removed
                    $itemsDiff[] = [
                        'product'      => $old['product'],
                        'old_quantity' => $old['quantity'],
                        'new_quantity' => 0,
                        'old_total'    => $old['total'],
                        'new_total'    => 0,
                        'action'       => 'removed',
                    ];
                } elseif (!$old && $new) {
                    // added
                    $itemsDiff[] = [
                        'product'      => $new['product'],
                        'old_quantity' => 0,
                        'new_quantity' => $new['quantity'],
                        'old_total'    => 0,
                        'new_total'    => $new['total'],
                        'action'       => 'added',
                    ];
                } else {
                    // maybe updated (qty/price/subtotal/discount/total)
                    $changed = [];
                    foreach (['quantity', 'price', 'subtotal', 'discount_price', 'total'] as $f) {
                        if ($old[$f] != $new[$f]) {
                            $changed[$f] = ['old' => $old[$f], 'new' => $new[$f]];
                        }
                    }
                    if (!empty($changed)) {
                        $itemsDiff[] = [
                            'product' => $new['product'],
                            'action'  => 'updated',
                            'fields'  => $changed,
                            // ringkasan utama untuk tampilan cepat:
                            'old_quantity' => $old['quantity'],
                            'new_quantity' => $new['quantity'],
                            'old_total'    => $old['total'],
                            'new_total'    => $new['total'],
                        ];
                    }
                }
            }

            $changes = [
                'order' => $orderDiff,
                'items' => $itemsDiff,
            ];

            // ================== SYNC ORDER PROGRESS ITEMS ==================
            $orderProgress = $order->orderProgress()->first();
            if ($orderProgress) {
                // keyBy berdasarkan order_item_id + product_id
                $existingProgressItems = $orderProgress->items->keyBy(function ($item) {
                    return $item->order_item_id . '_' . $item->product_id;
                });

                $newProgressKeys = [];

                foreach ($order->orderItems as $orderItem) {
                    $qty = $orderItem->quantity;

                    if ($orderItem->satuan === 'satuan') {
                        $key = $orderItem->id . '_' . $orderItem->product_id;
                        $newProgressKeys[] = $key;

                        if ($existingProgressItems->has($key)) {
                            // update qty
                            $existingProgressItems[$key]->update([
                                'quantity' => $qty,
                            ]);
                        } else {
                            OrderProgressItem::create([
                                'order_progress_id'  => $orderProgress->id,
                                'order_item_id'      => $orderItem->id,
                                'product_id'         => $orderItem->product_id,
                                'quantity'           => $qty,
                                'completed_quantity' => 0,
                            ]);
                        }
                    } elseif ($orderItem->satuan === 'bundle') {
                        foreach ($orderItem->productBundle->items as $bundleItem) {
                            $bundleProduct = $bundleItem->product;
                            if (!$bundleProduct) continue;

                            $key = $orderItem->id . '_' . $bundleProduct->id;
                            $newProgressKeys[] = $key;

                            if ($existingProgressItems->has($key)) {
                                $existingProgressItems[$key]->update([
                                    'quantity' => $qty,
                                ]);
                            } else {
                                OrderProgressItem::create([
                                    'order_progress_id'  => $orderProgress->id,
                                    'order_item_id'      => $orderItem->id,
                                    'product_id'         => $bundleProduct->id,
                                    'quantity'           => $qty,
                                    'completed_quantity' => 0,
                                ]);
                            }
                        }
                    }
                }

                // Hapus progress item lama yang tidak ada di order_items baru
                foreach ($existingProgressItems as $key => $progressItem) {
                    if (!in_array($key, $newProgressKeys)) {
                        $progressItem->delete();
                    }
                }
            }

            // ================== HANDLE ACCOUNT TRANSACTIONS ==================
            $saleAccount = Account::where('type', 'Sale Account')->firstOrFail();

            $existingSaleTx = AccountTransaction::where('order_id', $order->id)
                ->where('account_id', $saleAccount->id)
                ->where('credit', '>', 0)
                ->first();

            if (!$existingSaleTx) {
                AccountTransaction::create([
                    'order_id'            => $order->id,
                    'order_number'        => $order->order_number,
                    'transaction_date'    => $request->order_date,
                    'account_id'          => $saleAccount->id,
                    'debit'               => 0,
                    'credit'              => $request->total_amount,
                    'note'                => $request->note ?? '',
                    'particular'          => 'Sale Invoice',
                    'transaction_group_id' => Str::uuid(),
                ]);
                $saleAccount->increment('closing_balance', $request->total_amount);
            } else {
                $diff = $request->total_amount - $existingSaleTx->credit;
                $existingSaleTx->update([
                    'transaction_date' => $request->order_date,
                    'credit'           => $request->total_amount,
                ]);
                if ($diff != 0) {
                    $saleAccount->increment('closing_balance', $diff);
                }
            }

            // pembayaran tambahan
            if ($additionalPay > 0 && $request->cash_bank_account_id) {
                $cashBank = Account::findOrFail($request->cash_bank_account_id);

                AccountTransaction::create([
                    'order_id'            => $order->id,
                    'order_number'        => $order->order_number,
                    'transaction_date'    => $request->order_date,
                    'account_id'          => $cashBank->id,
                    'debit'               => $additionalPay,
                    'credit'              => 0,
                    'note'                => $request->note ?? '',
                    'particular'          => 'Additional Payment',
                    'transaction_group_id' => Str::uuid(),
                ]);

                $cashBank->increment('closing_balance', $additionalPay);
            }

            // ================== UPDATE / CREATE FINANCIAL REPORT ==================
            try {
                $financialReport = FinancialReport::where('transaction_type', 'sale')
                    ->where('reference_id', $order->id)
                    ->where('reference_table', 'orders')
                    ->first();

                $totalRevenue = $request->total_amount;
                $totalCogs = 0;

                // Hitung ulang COGS berdasarkan produk dan bundle
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {
                        // Produk satuan
                        $product = $orderItem->product;
                        $avgCost = $product->inventoryStock->avg_cost ?? 0;
                        $totalCogs += $avgCost * $orderItem->quantity;
                    } elseif ($orderItem->product_bundle_id) {
                        // Produk bundle
                        $bundle = $orderItem->productBundle;
                        $bundleCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->inventoryStock->avg_cost ?? 0;
                        });
                        $totalCogs += $bundleCost * $orderItem->quantity;
                    }
                }

                $grossProfit = $totalRevenue - $totalCogs;
                $netProfit   = $grossProfit; // belum ada expense di sini

                if ($financialReport) {
                    // Update report lama
                    $financialReport->update([
                        'date'         => $order->order_date,
                        'revenue'      => $totalRevenue,
                        'cogs'         => $totalCogs,
                        'gross_profit' => $grossProfit,
                        'expense'      => 0,
                        'net_profit'   => $netProfit,
                        'notes'        => 'Auto-updated from Sale List Edit',
                    ]);
                } else {
                    // Buat baru kalau belum ada
                    FinancialReport::create([
                        'date'             => $order->order_date,
                        'transaction_type' => 'sale',
                        'reference_id'     => $order->id,
                        'reference_table'  => 'orders',
                        'revenue'          => $totalRevenue,
                        'cogs'             => $totalCogs,
                        'gross_profit'     => $grossProfit,
                        'expense'          => 0,
                        'net_profit'       => $netProfit,
                        'notes'            => 'Auto-generated from Sale List Edit',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Gagal update laporan keuangan untuk Order ID ' . $order->id . ': ' . $e->getMessage());
            }

            // ===== 7) SIMPAN HISTORY & FLAG SUDAH EDIT
            OrderEditHistory::create([
                'order_id'  => $order->id,
                'edited_by' => Auth::id(),
                'changes'   => $changes,
                'text'      => $request->edit_note,
                'edited_at' => now(),
            ]);

            $order->update(['status_edited' => true]);

            DB::commit();
            return redirect("/erp/sales/sale-list/")->with('success', 'Order berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update order: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengupdate order: ' . $e->getMessage());
        }
    }

    public function delete($id, Request $request)
    {
        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with(['orderItems', 'deliveryOrders.shipments'])->findOrFail($id);

            $hasFinishedDelivery = $order->deliveryOrders
                ->flatMap->shipments
                ->contains(fn($shipment) => $shipment->status === 'Finished');

            if ($hasFinishedDelivery) {
                DB::rollBack();
                return back()->with(
                    'error',
                    'Tidak bisa menghapus order ini karena sudah ada Delivery List.'
                );
            }

            // 🔎 Cek apakah order punya sale return
            if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
                DB::rollBack();
                return back()->with('error', 'Tidak bisa menghapus order ini karena sudah ada Sale Return.');
            }

            $warehouseId = $request->inventory_warehouse_id ?? 1;

            // 🔁 Kembalikan stok untuk setiap item di inventory
            foreach ($order->orderItems as $item) {
                if ($item->satuan === 'satuan' && $item->product_id) {
                    $inventoryStock = InventoryStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
                        ['stock_after_sales' => 0]
                    );
                    $inventoryStock->increment('stock_after_sales', $item->quantity);
                } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
                    $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            if (!$bundleItem->product_id) continue;

                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $inventoryStock->increment('stock_after_sales', $item->quantity);
                        }
                    }
                }
            }

            // 🔁 Ambil semua progress item untuk order ini
            $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->get();

            foreach ($progressItems as $progressItem) {
                $completedQty = (int) $progressItem->completed_quantity;
                if ($completedQty <= 0 || !$progressItem->product_id) continue;

                $productionStock = ProductionStock::firstOrCreate(
                    ['product_id' => $progressItem->product_id, 'production_warehouse_id' => 2],
                    ['canceled_product_stock' => 0, 'finished_product_stock' => 0]
                );

                // 🔎 cek apakah progress ini sudah ada di delivery_order_items dengan shipped_qty > 0
                $hasShipped = DeliveryOrderItem::where(function ($q) use ($progressItem) {
                    $q->where('order_progress_id', $progressItem->id)
                        ->orWhere('order_item_id', $progressItem->order_item_id);
                })
                    ->where('shipped_qty', '>', 0)
                    ->exists();

                // ✅ canceled product selalu bertambah
                $productionStock->increment('canceled_product_stock', $completedQty);

                // ✅ Simpan ke tabel canceled_products (ledger detail)
                CanceledProduct::create([
                    'production_stock_id' => $productionStock->id,
                    'product_id'          => $progressItem->product_id,
                    'warehouse_id'        => $productionStock->production_warehouse_id,
                    'order_id'            => $order->id,
                    'order_item_id'       => $progressItem->order_item_id,
                    'quantity'            => $completedQty,
                    'date'                => now(),
                    'type'                => 'from_order_delete',
                    'status'              => 'pending', // masih di pool canceled
                    'note'                => 'Canceled product from order delete',
                    'created_by'          => Auth::id(),
                ]);

                if (!$hasShipped) {
                    // ✅ hanya kalau BELUM dikirim: kurangi finished_product_stock
                    $productionStock->decrement('finished_product_stock', $completedQty);
                }
            }

            // 🔁 Handle account transactions
            $transactions = AccountTransaction::where('order_id', $order->id)->get();
            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Sale Account') {
                    $account->closing_balance += $trx->debit;
                    $account->closing_balance -= $trx->credit;
                    $trx->delete();
                } else {
                    $trx->order_id = null;
                    $trx->note = trim(($trx->note ?? '') . ' [Order deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // 🔁 Bersihkan relasi lain
            OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->delete();
            OrderItem::where('order_id', $order->id)->delete();
            OrderProgress::where('order_id', $order->id)->delete();
            OrderEditHistory::where('order_id', $order->id)->delete();

            // Simpan delete_notes & deleted_by sebelum soft delete
            $order->delete_notes = $request->input('delete_notes');
            $order->deleted_by   = Auth::id();
            $order->save();

            // Soft delete order
            $order->delete();

            FinancialReport::where('reference_table', 'orders')
                ->where('reference_id', $order->id)
                ->update(['deleted_at' => now()]);

            DB::commit();
            return redirect()->back()->with('success', 'Order berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order delete failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }

    public function getSaleListDetail($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return view('erp.pages.sales.sale-list.detail-order', compact('order'));
    }

    public function markAsPaid($id, Request $request)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($request->order_id);

            // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
            $groupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type); // Akun pembelian (debit)
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id); // Akun kas/bank (kredit)

            // Transaksi DEBIT (akun pembelian bertambah)
            // AccountTransaction::create([
            //     'order_id' => $order->id,
            //     'transaction_date' => $request->transaction_date,
            //     'account_id' => $saleAccount->id,
            //     'debit' => 0,
            //     'credit' => $request->paid_amount,
            //     'note' => $request->note ?? '',
            //     'particular' => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
            //     'transaction_group_id' => $groupId,
            // ]);

            // $saleAccount->closing_balance += $request->paid_amount;
            // $saleAccount->save();

            // Transaksi KREDIT (kas/bank berkurang)
            AccountTransaction::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'transaction_date' => $request->transaction_date,
                'account_id' => $cashBankAccount->id,
                'debit' => $request->paid_amount,
                'credit' => 0,
                'note' => $request->note ?? '',
                'particular' => $saleAccount->name . ' - ' . $saleAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            $cashBankAccount->closing_balance += $request->paid_amount;
            $cashBankAccount->save();

            // Update nilai paid_amount di orders (bertambah)
            $order->paid_amount += $request->paid_amount;

            $order->remaining_amount = $order->grand_total - $order->paid_amount;

            // Kalau sebelumnya Unpaid, bisa ubah jadi Partially Paid atau Paid
            $order->updatePaymentStatus();

            $order->payment_method = $saleAccount->type;
            $order->save();

            DB::commit();
            return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MarkAsPaid Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
        }
    }

    public function markAsWaitingList($id)
    {
        $order = Order::find($id);

        if ($order->status === 'Sale List') {
            $order->status = 'Waiting List';
            $order->save();

            return redirect()->back()->with('success', 'Order marked as Waiting List.');
        }

        return redirect()->back()->with('warning', 'Order status is not Waiting List.');
    }

    public function getInvoice($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $invoice = Invoice::with('termAndConditions')->first();
        return view('erp.pages.sales.invoice.index', compact('order', 'invoice'));
    }

    public function returnMoney($id, Request $request)
    {
        $request->merge([
            'return_amount' => str_replace('.', '', $request->return_amount),
        ]);

        $request->validate([
            'order_id'            => 'required|exists:orders,id',
            'return_amount'       => 'required|numeric|min:1',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date'    => 'required|date',
            // 'transaction_type'    => 'required|exists:accounts,id',
            'note'                => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($id);

            $groupId = Str::uuid();
            // $saleReturnAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount   = Account::findOrFail($request->cash_bank_account_id);

            // transaksi keluar dari kas/bank
            AccountTransaction::create([
                'order_id'           => $order->id,
                'order_number'       => $order->order_number,
                'transaction_date'   => $request->transaction_date,
                'account_id'         => $cashBankAccount->id,
                'debit'              => 0,
                'credit'             => $request->return_amount,
                'note'               => $request->note ?? '',
                // 'particular'         => $saleReturnAccount->name,
                'transaction_group_id' => $groupId,
            ]);

            $cashBankAccount->closing_balance -= $request->return_amount;
            $cashBankAccount->save();

            // update paid_amount dan status
            $order->paid_amount -= $request->return_amount;
            $order->remaining_amount = max(0, $order->grand_total - $order->paid_amount);

            if ($order->paid_amount == $order->grand_total) {
                $order->payment_status = 'Paid';
            } elseif ($order->paid_amount > $order->grand_total) {
                $order->payment_status = 'Overpaid';
            } elseif ($order->paid_amount < $order->grand_total && $order->paid_amount > 0) {
                $order->payment_status = 'Partially Paid';
            } else {
                $order->payment_status = 'Unpaid';
            }

            $order->save();

            DB::commit();
            return redirect()->back()->with('success', 'Return money berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return Money Failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal return money: ' . $e->getMessage());
        }
    }

    public function getPaymentHistory($id)
    {
        $order = Order::with('customer')->findOrFail($id);

        // Group transaksi per pembayaran
        $transactions = AccountTransaction::with('account')
            ->where('order_id', $order->id)
            ->orderBy('transaction_date', 'asc')
            ->get()
            ->groupBy('transaction_group_id');

        // Ambil akun untuk modal (sesuai struktur modelmu: 'name' = Cash/Bank, 'type' = sub-type)
        $cashAccounts = Account::where('name', 'Cash')->orderBy('type')->get();
        $bankAccounts = Account::where('name', 'Bank')->orderBy('type')->get();

        return view(
            'erp.pages.sales.sale-list.payment-history',
            compact('order', 'transactions', 'cashAccounts', 'bankAccounts')
        );
    }

    // public function updatePayment(Request $request, $groupId)
    // {
    //     $request->merge([
    //         'paid_amount' => str_replace('.', '', $request->paid_amount), // hapus titik ribuan
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
    //             throw new \Exception("Payment not found");
    //         }

    //         $orderId = $transactions->first()->order_id;
    //         $order   = Order::findOrFail($orderId);

    //         // rollback saldo lama
    //         foreach ($transactions as $trx) {
    //             $acc = $trx->account;
    //             if ($trx->credit > 0) {
    //                 $acc->closing_balance -= $trx->credit;
    //             } elseif ($trx->debit > 0) {
    //                 $acc->closing_balance += $trx->debit;
    //             }
    //             $acc->save();
    //         }

    //         // hapus transaksi lama
    //         AccountTransaction::where('transaction_group_id', $groupId)->delete();

    //         // ambil akun sale & akun cash/bank
    //         $saleAccount     = Account::where('type', 'Sale Account')->firstOrFail();
    //         $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

    //         // insert ulang transaksi Sale (CREDIT)
    //         // AccountTransaction::create([
    //         //     'order_id'            => $order->id,
    //         //     'transaction_date'    => $request->transaction_date,
    //         //     'account_id'          => $saleAccount->id,
    //         //     'debit'               => 0,
    //         //     'credit'              => $request->paid_amount,
    //         //     'note'                => $request->note ?? '',
    //         //     'particular'          => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
    //         //     'transaction_group_id' => $groupId,
    //         // ]);

    //         // insert ulang transaksi Cash/Bank (DEBIT)
    //         AccountTransaction::create([
    //             'order_id'            => $order->id,
    //             'transaction_date'    => $request->transaction_date,
    //             'account_id'          => $cashBankAccount->id,
    //             'debit'               => $request->paid_amount,
    //             'credit'              => 0,
    //             'note'                => $request->note ?? '',
    //             'particular'          => $saleAccount->name . ' - ' . $saleAccount->type,
    //             'transaction_group_id' => $groupId,
    //         ]);

    //         // update saldo baru
    //         $cashBankAccount->closing_balance += $request->paid_amount;
    //         $cashBankAccount->save();

    //         // $saleAccount->closing_balance -= $request->paid_amount;
    //         // $saleAccount->save();

    //         // hitung ulang payment status order
    //         $totalPaid = AccountTransaction::where('order_id', $order->id)
    //             ->where('credit', '>', 0) // hanya transaksi credit ke akun Sale
    //             ->sum('credit');

    //         $order->paid_amount      = $totalPaid;
    //         $order->remaining_amount = max(0, $order->grand_total - $totalPaid);

    //         if ($order->paid_amount == 0) {
    //             $order->payment_status = 'Unpaid';
    //         } elseif ($order->paid_amount < $order->grand_total) {
    //             $order->payment_status = 'Partially Paid';
    //         } elseif ($order->paid_amount == $order->grand_total) {
    //             $order->payment_status = 'Paid';
    //         } else {
    //             $order->payment_status = 'Overpaid';
    //         }

    //         $order->save();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Payment berhasil diperbarui.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal update payment: ' . $e->getMessage());
    //     }
    // }

    public function updatePayment(Request $request, $groupId)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount), // hapus titik ribuan
        ]);

        $request->validate([
            'transaction_date'      => 'required|date',
            'paid_amount'           => 'required|numeric|min:1',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Payment not found");
            }

            $orderId = $transactions->first()->order_id;
            $order   = Order::findOrFail($orderId);

            // cari transaksi debit lama (Cash/Bank)
            $oldDebit = $transactions->firstWhere('debit', '>', 0);
            if (!$oldDebit) {
                throw new \Exception("Debit transaction not found in this group");
            }

            $oldAccount = $oldDebit->account;
            $oldAmount  = $oldDebit->debit;

            // rollback saldo akun lama
            $oldAccount->closing_balance -= $oldAmount;
            $oldAccount->save();

            // update transaksi debit lama → ganti akun/amount/date/note
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldDebit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => $request->paid_amount,
                'note'             => $request->note ?? '',
            ]);

            // update saldo akun baru
            $cashBankAccount->closing_balance += $request->paid_amount;
            $cashBankAccount->save();

            // update juga tanggal/note untuk baris credit Sale biar sinkron
            $saleTrx = $transactions->firstWhere('credit', '>', 0);
            if ($saleTrx) {
                $saleTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note'             => $request->note ?? '',
                ]);
            }

            // hitung ulang payment status order (sum debit)
            $totalPaid = AccountTransaction::where('order_id', $order->id)
                ->where('debit', '>', 0)
                ->sum('debit');

            $order->paid_amount      = $totalPaid;
            $order->remaining_amount = max(0, $order->grand_total - $totalPaid);

            if ($order->paid_amount == 0) {
                $order->payment_status = 'Unpaid';
            } elseif ($order->paid_amount < $order->grand_total) {
                $order->payment_status = 'Partially Paid';
            } elseif ($order->paid_amount == $order->grand_total) {
                $order->payment_status = 'Paid';
            } else {
                $order->payment_status = 'Overpaid';
            }

            $order->save();

            DB::commit();
            return redirect()->back()->with('success', 'Payment berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update payment: ' . $e->getMessage());
        }
    }

    public function getEditHistory($id)
    {
        $order = Order::findOrFail($id);

        $histories = OrderEditHistory::with('user')
            ->where('order_id', $id)
            ->orderBy('edited_at', 'desc')
            ->get();

        return view('erp.pages.sales.sale-list.edit-order-histories', compact('order', 'histories'));
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::onlyTrashed()->findOrFail($id);

            // 🔥 trigger booted() => otomatis hapus semua relasi
            $order->forceDelete();

            FinancialReport::withTrashed()
                ->where('reference_table', 'orders')
                ->where('reference_id', $order->id)
                ->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Order beserta item & relasinya berhasil dihapus permanen!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete order gagal', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus permanen order!');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::onlyTrashed()->findOrFail($id);

            $hasStockIn = Inventory::whereNotNull('canceled_product_id')
                ->whereHas('items', function ($q) use ($order) {
                    $q->whereIn('order_item_id', $order->orderItems()->withTrashed()->pluck('id'))
                        ->where('stock_in', '>', 0);
                })
                ->exists();

            if ($hasStockIn) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tidak bisa restore! Order ini sudah pernah masuk ke Warehouse (Stock In dari Canceled Product).');
            }

            // ✅ restore order dulu
            $order->restore();

            // ✅ restore relasi kalau ada
            if (method_exists($order, 'orderItems')) {
                $order->orderItems()->withTrashed()->restore();
            }

            $warehouseId = 1; // default atau ambil dari order kalau ada

            // 🔁 Kurangi kembali stock_after_sales (kebalikan dari delete)
            foreach ($order->orderItems as $item) {
                if ($item->satuan === 'satuan' && $item->product_id) {
                    $inventoryStock = InventoryStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
                        ['stock_after_sales' => 0]
                    );
                    $inventoryStock->decrement('stock_after_sales', $item->quantity);
                } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
                    $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            if (!$bundleItem->product_id) continue;

                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $inventoryStock->decrement('stock_after_sales', $item->quantity);
                        }
                    }
                }
            }

            // 🔁 Ambil progress item dan rollback canceled_product_stock
            $progressItems = OrderProgressItem::withTrashed()
                ->whereHas('progress', fn($q) => $q->withTrashed()->where('order_id', $order->id))
                ->get();

            foreach ($progressItems as $progressItem) {
                $completedQty = (int) $progressItem->completed_quantity;
                if ($completedQty <= 0 || !$progressItem->product_id) continue;

                $productionStock = ProductionStock::firstOrCreate(
                    ['product_id' => $progressItem->product_id, 'production_warehouse_id' => 2],
                    ['canceled_product_stock' => 0, 'finished_product_stock' => 0]
                );

                // 🔎 cek apakah progress ini sudah ada delivery dengan shipped_qty > 0
                $hasShipped = DeliveryOrderItem::where('order_progress_id', $progressItem->id)
                    ->where('shipped_qty', '>', 0)
                    ->exists();

                // ✅ canceled selalu dikurangi
                $productionStock->decrement('canceled_product_stock', $completedQty);

                if (!$hasShipped) {
                    // ✅ hanya kalau BELUM dikirim: finished ditambah lagi
                    $productionStock->increment('finished_product_stock', $completedQty);
                }
            }

            // ✅ rollback juga ledger canceled_products
            // CanceledProduct::where('order_id', $order->id)
            //     ->where('type', 'from_order_delete')
            //     ->forceDelete();

            // ✅ Restore relasi yang dihapus soft delete
            OrderProgress::withTrashed()->where('order_id', $order->id)->restore();
            OrderProgressItem::withTrashed()->whereHas('progress', fn($q) => $q->withTrashed()->where('order_id', $order->id))->restore();
            OrderEditHistory::withTrashed()->where('order_id', $order->id)->restore();

            // ✅ Restore transaksi akun
            $transactions = AccountTransaction::whereNull('order_id')
                ->where(function ($q) {
                    $q->where('note', 'like', '%[Order deleted]%');
                })
                ->get();

            foreach ($transactions as $trx) {
                $trx->note = str_replace('[Order deleted]', '', $trx->note);
                $trx->order_id = $order->id;
                $trx->save();
            }

            FinancialReport::withTrashed()
                ->where('reference_table', 'orders')
                ->where('reference_id', $order->id)
                ->update(['deleted_at' => null]);

            DB::commit();
            return redirect()->back()->with('success', 'Order berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore order gagal', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan order!');
        }
    }
}
