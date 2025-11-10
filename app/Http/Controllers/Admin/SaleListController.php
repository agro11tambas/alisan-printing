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
use App\Models\DeliveryList;
use App\Models\DeliveryListItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\FinancialReport;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\OrderEditHistory;
use App\Models\OrderItemComponent;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressHistory;
use App\Models\OrderProgressItem;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\ProductionStock;
use App\Models\SaleReturn;
use App\Services\InvoiceNumberService;
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

        $defaultAccount = Account::where('is_default', true)->first();

        return view('erp.pages.sales.sale-list.sale-list', compact('order_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'defaultAccount'));
    }

    public function dataSaleList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $orders = Order::with(['customer'])
            ->where('status', 'sale list')
            ->orderByDesc('id');

        // 🔹 Filter tanggal
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
            }
        }

        // 🔹 Filter payment status
        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $orders->whereIn('payment_status', ['Paid', 'Overpaid']);
            } else {
                $orders->where('payment_status', $request->payment_status);
            }
        }
        // 🔹 Sort by due_date
        elseif ($request->search_type === 'due_date') {
            $direction = strtolower($request->due_date_order ?? 'asc');
            $orders->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                ->orderBy('due_date', $direction);
        }
        // 🔹 Pencarian keyword
        elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $orders->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $orders->where('order_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $orders;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $orders->skip($start)->take($length)->get();

        // 🔹 Return format sama seperti product → JSON ringan untuk lazy load
        return response()->json([
            'data' => $data->map(function ($order) {
                $orderCreatedAt = Carbon::parse($order->order_date)->format('d M y H:i');
                $date = Carbon::parse($order->order_date)->format('j M y');
                $dueDate = $order->due_date ? Carbon::parse($order->due_date)->format('j M y') : '-';

                $editedBadge = $order->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                $returnBadge = $order->saleReturns()->exists()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Sale Return</span></div>'
                    : '';

                // 🔸 Kolom tampilan
                $orderNumber = $returnBadge . '
                <div>
                    <div>' . e($order->order_number) . $editedBadge . '</div>
                    <small class="text-muted">' . $orderCreatedAt . '</small>,
                    <small class="text-danger">Due: ' . $dueDate . '</small>
                </div>';

                // 🔸 Status pembayaran
                $status = strtolower($order->payment_status);
                $badge = match ($status) {
                    'paid' => 'bg-soft-success text-success',
                    'unpaid' => 'bg-soft-dark text-dark',
                    'overdue' => 'bg-soft-danger text-danger',
                    'overpaid' => 'bg-soft-primary text-primary',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };
                $paymentStatus = '<div class="badge ' . $badge . '">' . ucfirst($status) . '</div>';

                $statusBadge = '<div class="badge bg-soft-dark text-dark">' . ucfirst($order->status) . '</div>';

                $mode = strtolower($order->mode ?? 'printing');
                $modeBadgeClass = match ($mode) {
                    'printing' => 'bg-soft-success text-success',
                    'polosan'    => 'bg-soft-primary text-primary',
                    default  => 'bg-soft-dark text-dark',
                };
                $modeBadge = '<div class="badge ' . $modeBadgeClass . '">' . ucfirst($mode) . '</div>';

                // 🔸 Produk
                $items = $order->orderItems()
                    ->with([
                        'product' => fn($q) => $q->withTrashed(),
                        'productBundle.items.product',
                        'deliveryItems.deliveryOrder',
                    ])
                    ->get()
                    ->map(function ($item) {
                        if ($item->product) {
                            $name = $item->product->name;
                            $sku = $item->product->sku;
                        } elseif ($item->productBundle) {
                            $bundleNames = $item->productBundle->items
                                ->map(fn($b) => $b->product->name ?? '-')
                                ->implode(' + ');
                            $name = $bundleNames ?: '-';
                            $sku = $item->productBundle->sku ?? '-';
                        } else {
                            $name = '-';
                            $sku = '-';
                        }

                        $deliveryData = $item->order
                            ->deliveryOrders()
                            ->with(['items' => function ($q) use ($item) {
                                $q->where('order_item_id', $item->id);
                            }])
                            ->get()
                            ->pluck('items')
                            ->flatten();

                        if ($item->productBundle) {
                            $progressQty = $deliveryData->first()->progress_qty ?? 0;
                            $readyQty = $deliveryData->first()->ready_qty ?? 0;
                            $shippedQty = $deliveryData->first()->shipped_qty ?? 0;
                        } else {
                            $progressQty = $deliveryData->sum('progress_qty');
                            $readyQty = $deliveryData->sum('ready_qty');
                            $shippedQty = $deliveryData->sum('shipped_qty');
                        }

                        $deliveryOrders = $item->order->deliveryOrders()
                            ->with(['shipments', 'items' => function ($q) use ($item) {
                                $q->where('order_item_id', $item->id);
                            }])
                            ->get();

                        $deliveryListItems = $item->order->deliveryOrders()
                            ->with(['items.deliveryListItems.shipment'])
                            ->get()
                            ->pluck('items')
                            ->flatten()
                            ->filter(fn($d) => $d->order_item_id === $item->id)
                            ->flatMap(fn($d) => $d->deliveryListItems ?? collect());

                        // $deliveredQty = $deliveryListItems
                        //     ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
                        //     ->sum('shipped_quantity');

                        // $onDeliveryQty = $deliveryListItems
                        //     ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
                        //     ->sum('shipped_quantity');

                        if ($item->productBundle) {
                            $deliveredQty = $deliveryListItems
                                ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
                                ->first()
                                ->shipped_quantity ?? 0;

                            $onDeliveryQty = $deliveryListItems
                                ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
                                ->first()
                                ->shipped_quantity ?? 0;
                        } else {
                            $deliveredQty = $deliveryListItems
                                ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
                                ->sum('shipped_quantity');

                            $onDeliveryQty = $deliveryListItems
                                ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
                                ->sum('shipped_quantity');
                        }

                        return [
                            'name' => e($name),
                            'sku' => e($sku),
                            'qty' => number_format($item->quantity, 0, ',', '.'),
                            'price' => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                            'progress_qty' => number_format($progressQty, 0, ',', '.'),
                            'ready_qty' => number_format($readyQty, 0, ',', '.'),
                            'shipped_qty' => number_format($shippedQty, 0, ',', '.'),
                            'delivered' => number_format($deliveredQty, 0, ',', '.'),
                            'on_delivery' => number_format($onDeliveryQty, 0, ',', '.'),
                        ];
                    });

                return [
                    'id' => $order->id,
                    'order_number' => $orderNumber,
                    'order_date' => $date,
                    'customer' => e($order->customer->name ?? '-'),
                    'total_amount' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'discount' => '<span class="text-warning">Rp ' . number_format($order->discount, 0, ',', '.') . '</span>',
                    'grand_total' => '<span class="text-primary">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>',
                    'paid_amount' => '<span class="text-success">Rp ' . number_format($order->paid_amount, 0, ',', '.') . '</span>',
                    'remaining_amount' => '<span class="text-danger">Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</span>',
                    'payment_status' => $paymentStatus,
                    'status' => $statusBadge,
                    'payment_method' => e($order->payment_method ?? '-'),
                    'products' => $items,
                    'notes' => e($order->notes ?? '-'),
                    'created_at' => $orderCreatedAt,
                    'mode' => $modeBadge,
                    'action' => view('erp.pages.sales.sale-list.partials.action-button', compact('order'))->render(),
                ];
            })->values(),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function dataDeletedSaleList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start  = (int) $request->input('start', 0);

        $orders = Order::onlyTrashed()
            ->with(['customer'])
            ->where('status', 'sale list')
            ->orderByDesc('deleted_at');

        // 🔹 Filter tanggal
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
            }
        }

        // 🔹 Filter berdasarkan customer
        if ($request->search_type === 'customer' && $request->filled('search_keyword')) {
            $orders->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_keyword . '%');
            });
        }

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $orders;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $orders->skip($start)->take($length)->get();

        return response()->json([
            'data' => $data->map(function ($order) {

                $date = Carbon::parse($order->order_date)->format('j M y');
                $dueDate = $order->due_date ? Carbon::parse($order->due_date)->format('j M y') : '-';

                $orderNumber = '
                <div>
                    <div>' . e($order->order_number) . '</div>
                    <small class="text-muted">' . $date . '</small>,
                    <small class="text-danger">Due: ' . $dueDate . '</small>
                </div>';

                $status = strtolower($order->payment_status ?? 'unknown');
                $badge = match ($status) {
                    'paid' => 'bg-soft-success text-success',
                    'unpaid' => 'bg-soft-dark text-dark',
                    'overdue' => 'bg-soft-danger text-danger',
                    'overpaid' => 'bg-soft-primary text-primary',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };
                $paymentStatus = '<div class="badge ' . $badge . '">' . ucfirst($status) . '</div>';


                // 🔹 Produk (mengikuti logika dataSaleList)
                $items = $order->orderItems()
                    ->with([
                        'product' => fn($q) => $q->withTrashed(),
                        'productBundle.items.product',
                        'deliveryItems.deliveryOrder'
                    ])
                    ->get()
                    ->map(function ($item) use ($order) {
                        if ($item->product) {
                            $name = $item->product->name;
                            $sku = $item->product->sku;
                        } elseif ($item->productBundle) {
                            $bundleNames = $item->productBundle->items
                                ->map(fn($b) => $b->product->name ?? '-')
                                ->implode(' + ');
                            $name = $bundleNames ?: '-';
                            $sku  = $item->productBundle->sku ?? '-';
                        } else {
                            $name = '-';
                            $sku  = '-';
                        }

                        // 💡 gunakan $order langsung (bukan $item->order)
                        $deliveryData = $order->deliveryOrders()
                            ->with(['items' => function ($q) use ($item) {
                                $q->where('order_item_id', $item->id);
                            }])
                            ->get()
                            ->pluck('items')
                            ->flatten();

                        if ($item->productBundle) {
                            $progressQty = $deliveryData->first()->progress_qty ?? 0;
                            $readyQty    = $deliveryData->first()->ready_qty ?? 0;
                            $shippedQty  = $deliveryData->first()->shipped_qty ?? 0;
                        } else {
                            $progressQty = $deliveryData->sum('progress_qty');
                            $readyQty    = $deliveryData->sum('ready_qty');
                            $shippedQty  = $deliveryData->sum('shipped_qty');
                        }

                        $deliveryListItems = $order->deliveryOrders()
                            ->with(['items.deliveryListItems.shipment'])
                            ->get()
                            ->pluck('items')
                            ->flatten()
                            ->filter(fn($d) => $d->order_item_id === $item->id)
                            ->flatMap(fn($d) => $d->deliveryListItems ?? collect());

                        $deliveredQty = $deliveryListItems
                            ->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')
                            ->sum('shipped_quantity');

                        $onDeliveryQty = $deliveryListItems
                            ->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')
                            ->sum('shipped_quantity');

                        return [
                            'name'           => e($name),
                            'sku'            => e($sku),
                            'qty'            => number_format($item->quantity, 0, ',', '.'),
                            'price'          => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                            'progress_qty'   => number_format($progressQty, 0, ',', '.'),
                            'ready_qty'      => number_format($readyQty, 0, ',', '.'),
                            'shipped_qty'    => number_format($shippedQty, 0, ',', '.'),
                            'delivered'      => number_format($deliveredQty, 0, ',', '.'),
                            'on_delivery'    => number_format($onDeliveryQty, 0, ',', '.'),
                        ];
                    });

                // 🔹 Tombol aksi untuk Owner saja
                $action = '';
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    $action = '
                    <div class="d-flex gap-2">
                        <button type="button" 
                            class="btn btn-success btn-sm me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRestoreOrder"
                            data-id="' . $order->id . '" 
                            data-name="' . e($order->order_number) . '"
                            data-url="' . route('sales.restore', $order->id) . '">
                            Restore
                        </button>
                        <button type="button" 
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalForceDeleteOrder"
                            data-id="' . $order->id . '" 
                            data-name="' . e($order->order_number) . '"
                            data-url="' . route('sales.forceDelete', $order->id) . '">
                            Hapus Permanen
                        </button>
                    </div>
                ';
                }

                return [
                    'id'               => $order->id,
                    'order_number'     => $orderNumber,
                    'customer'         => e($order->customer->name ?? '-'),
                    'grand_total'      => '<span class="text-primary">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>',
                    'deleted_at'       => $order->deleted_at ? $order->deleted_at->format('j M y H:i') : '-',
                    'deleted_by'       => e(optional($order->deletedByUser)->name ?? '-'),
                    'delete_notes'     => e($order->delete_notes ?? '-'),
                    'products'         => $items,
                    'payment_status'   => $paymentStatus,
                    'action'           => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }


    public function create()
    {
        $products = Products::with(['categories', 'discounts', 'categories.discounts'])
            ->orderBy('name', 'asc')
            ->get();

        $productBundles = ProductBundle::with([
            'items.product.categories.discounts',
            'items.product.discounts'
        ])->orderBy('name', 'asc')->get();

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

            // 🔹 Buat nama bundle otomatis dari nama produk di dalamnya
            $bundleName = $bundle->items->map(function ($item) {
                return $item->product->name ?? '-';
            })->implode(' + ');

            return [
                'id' => $bundle->id,
                'name' => $bundleName ?: $bundle->name, // fallback ke nama asli kalau kosong
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

        return view('erp.pages.sales.sale-list.create-order', compact(
            'products',
            'productBundles',
            'customers',
            'discount',
            'cashAccounts',
            'bankAccounts',
            'transactionTypes',
            'productsJson',
            'productBundlesJson'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_date'           => 'required|date',
            'due_date_option'      => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'      => 'nullable|date',
            // 'customers'            => 'required|array',
            // 'customers.*'          => 'exists:customers,id',
            'customer_id'          => 'required|exists:customers,id',
            'customer_address_id'  => 'required|exists:customer_addresses,id',
            // 'addresses'            => 'required|array',
            // 'addresses.*'          => 'exists:customer_addresses,id',
            'notes'                => 'nullable|string',
            'product_type'         => 'required|array',
            'product_type.*'       => 'in:satuan,bundle',
            'product'              => 'required|array',
            'product.*'            => 'required',
            'qty'                  => 'required|array',
            'qty.*'                => 'numeric|min:1',
            'mode'             => 'required|in:printing,polosan',
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
            'notes'                => 'nullable|string',
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

            $orderNumber = InvoiceNumberService::generate('INV', $orderDate);

            $addressModel = CustomerAddresses::find($request->customer_address_id);

            // ================== BUAT ORDER ==================
            $order = Order::create([
                'customer_id'        => $request->customer_id,
                'customer_address_id' => $request->customer_address_id,
                'order_number'     => $orderNumber,
                'order_date'       => $request->order_date,
                'due_date'         => $dueDate,
                'payment_method'   => $paymentMethod,
                'status'           => $status,
                'payment_status'   => ($paidAmount <= 0) ? 'Unpaid' : (($paidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid'),
                'paid_amount'      => $paidAmount,
                'business_name'    => $addressModel?->business_name,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'mode'             => $request->mode,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
                'discount_active' => (int) $request->input('discount_active_hidden', 1),
            ]);

            // === BUAT ORDER ITEMS ===
            foreach ($request->product as $index => $productInputId) {
                $type = strtolower($request->product_type[$index]);
                $qty  = (float) $request->qty[$index];

                // --- PRODUK SATUAN ---
                if ($type === 'satuan') {
                    $product = Products::findOrFail($productInputId);
                    $inventoryStock = InventoryStock::where('product_id', $product->id)->first();
                    $avgCost = $product?->avg_cost ?? 0;
                    $fixedCostAtSale = $product?->fixed_cost ?? 0;

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
                        'fixed_cost_at_sale' => $fixedCostAtSale,
                        'total_cost'       => $avgCost * $qty,
                        'total_fixed_cost' => $fixedCostAtSale * $qty,
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

                    // 🔹 Gabungkan nama-nama produk di dalam bundle
                    $bundleProductNames = $bundle->items->map(function ($item) {
                        return $item->product->name ?? '-';
                    })->implode(' + ');

                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => null,
                        'product_bundle_id'    => $bundle->id,
                        'status'               => $paymentMethod,
                        'product_name'         => $bundleProductNames,
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
                        $product = Products::find($component->id);
                        $avgCost = $product?->avg_cost ?? 0;
                        $fixedCostAtSale = $product?->fixed_cost ?? 0;
                        $totalQty = $qty; // ✅ ambil dari order item, bukan bundleItem->qty

                        OrderItemComponent::create([
                            'order_item_id'    => $orderItem->id,
                            'product_id'       => $component->id,
                            'qty'              => $totalQty,
                            'avg_cost_at_sale' => $avgCost,
                            'fixed_cost_at_sale' => $fixedCostAtSale,
                            'total_cost'       => $avgCost * $totalQty,
                            'total_fixed_cost' => $fixedCostAtSale * $totalQty,
                        ]);

                        InventoryStock::updateOrCreate(
                            ['product_id' => $component->id, 'inventory_warehouse_id' => $warehouseId],
                            []
                        )->decrement('stock_after_sales', $totalQty);
                    }
                }
            }

            if ($request->mode === 'printing') {
                // ================== GANTI OrderProgress → Design ==================
                $designNumber = $orderNumber;

                $design = Design::create([
                    'order_id' => $order->id,
                    'design_number' => $designNumber,
                    'date' => now()->format('Y-m-d'),
                    'status' => 'Pending',
                    'notes' => null,
                    'verification_status' => 'pending',
                ]);

                foreach ($order->orderItems as $orderItem) {
                    $qty = $orderItem->quantity;

                    if ($orderItem->satuan === 'satuan') {
                        DesignItem::create([
                            'design_id' => $design->id,
                            'order_item_id' => $orderItem->id,
                            'product_id' => $orderItem->product_id,
                            'quantity' => $qty,
                            'completed_quantity' => 0,
                            'design_file' => null,
                            'preview_image' => null,
                            'verification_status' => 'pending',
                        ]);
                    } elseif ($orderItem->satuan === 'bundle') {
                        foreach ($orderItem->productBundle->items as $bundleItem) {
                            $bundleProduct = $bundleItem->product;
                            if (!$bundleProduct) continue;

                            DesignItem::create([
                                'design_id' => $design->id,
                                'order_item_id' => $orderItem->id,
                                'product_id' => $bundleProduct->id,
                                'quantity' => $qty,
                                'completed_quantity' => 0,
                                'design_file' => null,
                                'preview_image' => null,
                                'verification_status' => 'pending',
                            ]);
                        }
                    }
                }
            } else {
                // ================== MODE POLOSAN → SIMPAN KE DELIVERY ORDER ==================
                $deliveryNumber = $orderNumber;
                $customer = $order->customer?->name ?? '-';
                $address = $order->shipping_address ?? '-';
                $maps = $order->google_maps ?? null;

                $deliveryOrder = DeliveryOrder::create([
                    'order_id'         => $order->id,
                    'design_id'        => null,
                    'delivery_number'  => $deliveryNumber,
                    'delivery_date'    => $request->order_date,
                    'note'             => $request->notes,
                    'status'           => 'Ongoing',
                    'customer'         => $customer,
                    'shipping_address' => $address,
                    'google_map_link'  => $maps,
                    'created_by'       => Auth::id(),
                ]);

                foreach ($order->orderItems as $orderItem) {
                    // Produk satuan
                    if ($orderItem->satuan === 'satuan') {
                        DeliveryOrderItem::create([
                            'delivery_order_id' => $deliveryOrder->id,
                            'order_item_id'     => $orderItem->id,
                            'product_id'        => $orderItem->product_id,
                            'status'            => 'Pending',
                            'progress_qty'      => 0,
                            'ready_qty'         => $orderItem->quantity,
                            'shipped_qty'       => 0,
                            'note'              => null,
                        ]);

                        $productionStock = \App\Models\ProductionStock::where('product_id', $orderItem->product_id)->first();
                        if ($productionStock) {
                            $productionStock->decrement('available_quantity', $orderItem->quantity);
                        }

                        // $inventoryStock = \App\Models\InventoryStock::where('product_id', $orderItem->product_id)
                        //     ->where('inventory_warehouse_id', $warehouseId)
                        //     ->first();
                        // if ($inventoryStock) {
                        //     $inventoryStock->decrement('stock_after_sales', $orderItem->quantity);
                        // }
                    }

                    // Produk bundle
                    elseif ($orderItem->satuan === 'bundle') {
                        foreach ($orderItem->productBundle->items as $bundleItem) {
                            $bundleProduct = $bundleItem->product;
                            if (!$bundleProduct) continue;

                            DeliveryOrderItem::create([
                                'delivery_order_id' => $deliveryOrder->id,
                                'order_item_id'     => $orderItem->id,
                                'product_id'        => $bundleProduct->id,
                                'status'            => 'Pending',
                                'progress_qty'      => 0,
                                'ready_qty'         => $orderItem->quantity,
                                'shipped_qty'       => 0,
                                'note'              => null,
                            ]);

                            $productionStock = \App\Models\ProductionStock::where('product_id', $bundleProduct->id)->first();
                            if ($productionStock) {
                                $productionStock->decrement('available_quantity', $orderItem->quantity);
                            }

                            $inventoryStock = \App\Models\InventoryStock::where('product_id', $bundleProduct->id)
                                ->where('inventory_warehouse_id', $warehouseId)
                                ->first();
                            if ($inventoryStock) {
                                $inventoryStock->decrement('stock_after_sales', $orderItem->quantity);
                            }
                        }
                    }
                }
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
                $totalFixedCost = 0;

                // Hitung total COGS berdasarkan avg_cost per produk
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {
                        // Produk satuan
                        $product = $orderItem->product;
                        $avgCost = $product->avg_cost ?? 0;
                        $fixedCost = $product?->fixed_cost ?? 0;
                        $totalCogs += $avgCost * $orderItem->quantity;
                        $totalFixedCost += $fixedCost * $orderItem->quantity;
                    } elseif ($orderItem->product_bundle_id) {
                        // Produk bundle
                        $bundle = $orderItem->productBundle;

                        $bundleAvgCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->avg_cost ?? 0;
                        });

                        $bundleFixedCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->fixed_cost ?? 0;
                        });

                        $totalCogs       += $bundleAvgCost * $orderItem->quantity;
                        $totalFixedCost  += $bundleFixedCost * $orderItem->quantity;
                    }
                }

                $grossProfit = $totalRevenue - $totalCogs;
                $grossProfitAtFixedCost = $totalRevenue - $totalFixedCost;

                FinancialReport::create([
                    'date'             => $order->order_date,
                    'transaction_type' => 'sale',
                    'reference_id'     => $order->id,
                    'reference_table'  => 'orders',
                    'revenue'          => $totalRevenue,
                    'cogs'             => $totalCogs,
                    'cogs_fixed_cost'   => $totalFixedCost,
                    'gross_profit'     => $grossProfit,
                    'gross_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                    'expense'          => 0,
                    'net_profit'       => $grossProfit,
                    'net_profit_at_fixed_cost' => $grossProfitAtFixedCost,
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
        $order = Order::with(['orderItems', 'customer.addresses', 'customerAddress'])->findOrFail($id);

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

        // 🔹 Data produk & bundle
        $productBundles = ProductBundle::with([
            'items.product.categories.discounts',
            'items.product.discounts'
        ])->orderBy('name', 'asc')->get();

        $products = Products::with(['categories', 'discounts', 'categories.discounts'])
            ->orderBy('name', 'asc')
            ->get();

        $customers = Customers::with('addresses')->orderBy('name', 'asc')->get();

        // 🔹 JSON untuk produk tunggal
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

        // 🔹 JSON untuk bundle (gabung nama produk di dalamnya)
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

            // 🔹 Gabungkan nama produk jadi 1 string
            $bundleName = $bundle->items->map(function ($item) {
                return $item->product->name ?? '-';
            })->implode(' + ');

            return [
                'id' => $bundle->id,
                'name' => $bundleName ?: $bundle->name, // fallback ke nama bundle asli
                'sku'  => $bundle->sku,
                'price' => $bundle->price,
                'discounts' => $bundleDiscounts,
                'categories' => $bundleCategories,
            ];
        })->toArray();

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
        // dd($request->all());
        $request->validate([
            'order_date'              => 'required|date',
            'due_date_option'         => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'         => 'nullable|date',
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
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
            'mode'                    => 'required|in:printing,polosan',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems')->findOrFail($id);

            $hasProgressHistory = \App\Models\OrderProgressHistory::whereHas('progressItem', function ($q) use ($order) {
                $q->whereHas('progress', function ($p) use ($order) {
                    $p->where('order_id', $order->id);
                });
            })->exists();

            if ($hasProgressHistory) {
                DB::rollBack();
                return back()->with('error', 'Tidak dapat mengupdate order ini karena sudah memiliki progress history produksi.');
            }

            // Cek apakah sudah ada assign
            $hasAssign = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->exists();

            if ($hasAssign) {
                DB::rollBack();
                return back()->with('error', 'Tidak dapat mengupdate order ini karena sudah memiliki progress assign produksi.');
            }

            // 🔹 CEK Finished Delivery
            $hasFinishedDelivery = $order->deliveryOrders()
                ->with('shipments')
                ->get()
                ->flatMap->shipments
                ->contains(fn($shipment) => $shipment->status === 'Finished');

            if ($hasFinishedDelivery) {
                DB::rollBack();
                return back()->with('error', 'Tidak dapat mengupdate order ini karena sudah ada Delivery List yang selesai.');
            }

            $designVerified = \App\Models\Design::where('order_id', $id)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(status) = ?', ['verified'])
                        ->orWhereRaw('LOWER(verification_status) = ?', ['verified']);
                })
                ->exists();

            if ($designVerified) {
                return back()->with('error', 'Order tidak dapat diupdate karena design sudah diverifikasi.');
            }

            if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
                DB::rollBack(); // rollback supaya transaksi clear
                return back()->with('error', 'Tidak bisa mengupdate order ini karena sudah ada Sale Return.');
            }

            $orderMode = $request->mode ?? $order->mode;

            // 🚫 Cegah perubahan mode jika design sudah diverifikasi
            if ($order->mode !== $orderMode) {
                $designVerified = \App\Models\Design::where('order_id', $order->id)
                    ->whereRaw('LOWER(status) = ?', ['verified'])
                    ->exists();

                if ($designVerified) {
                    DB::rollBack();
                    return back()->with('error', 'Tidak dapat mengubah mode order (Printing ⇄ Polosan) karena design sudah diverifikasi.');
                }
            }

            // ================== HANDLE PERUBAHAN MODE PRINTING ↔ POLOSAN ==================
            if ($order->mode !== $orderMode) {
                $warehouseId = $request->inventory_warehouse_id ?? 1;

                // 📦 Ambil snapshot produk LAMA sebelum perubahan mode
                $oldProducts = [];
                foreach ($order->orderItems as $oldItem) {
                    if ($oldItem->satuan === 'satuan') {
                        $oldProducts[$oldItem->product_id] = ($oldProducts[$oldItem->product_id] ?? 0) + $oldItem->quantity;
                    } elseif ($oldItem->satuan === 'bundle') {
                        $bundle = ProductBundle::with('items')->find($oldItem->product_bundle_id);
                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                if ($bundleItem->product_id) {
                                    $oldProducts[$bundleItem->product_id] = ($oldProducts[$bundleItem->product_id] ?? 0) + $oldItem->quantity;
                                }
                            }
                        }
                    }
                }

                // 📦 Ambil snapshot produk BARU dari request
                $newProducts = [];
                foreach ($request->product as $index => $productValue) {
                    [$type, $productId] = explode('_', $productValue);
                    $qty = (int) $request->qty[$index];

                    if ($type === 'satuan') {
                        $newProducts[$productId] = ($newProducts[$productId] ?? 0) + $qty;
                    } elseif ($type === 'bundle') {
                        $bundle = ProductBundle::with('items')->find($productId);
                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                if ($bundleItem->product_id) {
                                    $newProducts[$bundleItem->product_id] = ($newProducts[$bundleItem->product_id] ?? 0) + $qty;
                                }
                            }
                        }
                    }
                }

                // 🔹 Jika sebelumnya printing dan sekarang jadi polosan
                if ($order->mode === 'printing' && $orderMode === 'polosan') {
                    // --- RELEASE DESIGN & PROGRESS BEFORE SWITCHING TO POLOSAN ---
                    // Kumpulkan qty per product dari design_items (baik satuan maupun bundle)
                    $designs = $order->designs()->with('items')->get();

                    $releaseQtyPerProduct = []; // [product_id => total_qty]
                    foreach ($designs as $dsg) {
                        foreach ($dsg->items as $di) {
                            if ($di->product_id) {
                                $releaseQtyPerProduct[$di->product_id] = ($releaseQtyPerProduct[$di->product_id] ?? 0) + (int)$di->quantity;
                            }
                        }
                    }

                    // Kurangi pending_waiting_list per product (pakai lock biar aman)
                    foreach ($releaseQtyPerProduct as $pid => $qty) {
                        $ps = \App\Models\ProductionStock::where('product_id', $pid)->lockForUpdate()->first();
                        if ($ps && $qty > 0) {
                            // cegah minus
                            $decr = min($ps->pending_waiting_list, $qty);
                            if ($decr > 0) {
                                $ps->decrement('pending_waiting_list', $decr);
                            }
                        }
                    }

                    // Hapus design items & design (supaya tidak dianggap 'verified' lagi)
                    foreach ($designs as $dsg) {
                        $dsg->items()->delete();
                        $dsg->delete();
                    }

                    // Bersihkan Order Progress (items dulu, lalu header)
                    if ($progress = $order->orderProgress()->with('items')->first()) {
                        $progress->items()->delete();
                        $progress->delete();
                    }

                    // (Opsional) Bersihkan DO yang masih berjalan (Pending/Ongoing) karena pindah ke polosan
                    $deliveryOrders = $order->deliveryOrders()->with('items')->get();
                    foreach ($deliveryOrders as $do) {
                        if (in_array(strtolower($do->status), ['pending', 'ongoing'])) {
                            $do->items()->delete();
                            $do->delete();
                        }
                    }
                    // --- END RELEASE ---

                    // ✅ STOCK ADJUSTMENT
                    // Produk yang HILANG
                    foreach ($oldProducts as $productId => $oldQty) {
                        if (!isset($newProducts[$productId])) {
                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $productId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $inventoryStock->increment('stock_after_sales', $oldQty);

                            // 🔻 Decrement available_quantity (printing → polosan)
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->decrement('available_quantity', $oldQty);
                        }
                    }

                    // Produk BARU → decrement stock_after_sales & decrement available_quantity
                    foreach ($newProducts as $productId => $newQty) {
                        if (!isset($oldProducts[$productId])) {
                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $productId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $inventoryStock->decrement('stock_after_sales', $newQty);

                            // 🔻 Decrement available_quantity (printing → polosan)
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->decrement('available_quantity', $newQty);
                        }
                    }

                    // Produk SAMA tapi QTY BEDA
                    foreach ($oldProducts as $productId => $oldQty) {
                        if (isset($newProducts[$productId])) {
                            $diff = $newProducts[$productId] - $oldQty;
                            if ($diff !== 0) {
                                $inventoryStock = InventoryStock::firstOrCreate(
                                    ['product_id' => $productId, 'inventory_warehouse_id' => $warehouseId],
                                    ['stock_after_sales' => 0]
                                );

                                $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                    ['product_id' => $productId],
                                    ['available_quantity' => 0]
                                );

                                if ($diff > 0) {
                                    $inventoryStock->decrement('stock_after_sales', $diff);
                                    $productionStock->decrement('available_quantity', $diff); // 🔻
                                } else {
                                    $inventoryStock->increment('stock_after_sales', abs($diff));
                                    $productionStock->increment('available_quantity', abs($diff)); // 🔺
                                }
                            }
                        }
                    }
                }

                // 🔹 Jika sebelumnya polosan dan sekarang jadi printing
                if ($order->mode === 'polosan' && $orderMode === 'printing') {
                    // 🧹 FORCE DELETE semua Delivery Order & Item karena pindah dari polosan ke printing
                    $deliveryOrders = $order->deliveryOrders()->withTrashed()->with('items')->get();
                    foreach ($deliveryOrders as $do) {
                        // hapus semua item di dalamnya permanen
                        $do->items()->withTrashed()->forceDelete();
                        // hapus DO-nya juga permanen
                        $do->forceDelete();
                    }
                    // ✅ STOCK ADJUSTMENT

                    // Produk yang HILANG → tambahkan ke produksi
                    foreach ($oldProducts as $productId => $oldQty) {
                        if (!isset($newProducts[$productId])) {
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $oldQty); // 🔺 Tambah ke produksi
                        }
                    }

                    // Produk BARU → tambahkan ke produksi
                    foreach ($newProducts as $productId => $newQty) {
                        if (!isset($oldProducts[$productId])) {
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $newQty); // 🔺 Tambah ke produksi
                        }
                    }

                    // Produk SAMA tapi QTY BEDA
                    foreach ($oldProducts as $productId => $oldQty) {
                        if (isset($newProducts[$productId])) {
                            $diff = $newProducts[$productId] - $oldQty;
                            if ($diff !== 0) {
                                $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                    ['product_id' => $productId],
                                    ['available_quantity' => 0]
                                );

                                if ($diff > 0) {
                                    // qty naik → tambah produksi
                                    $productionStock->increment('available_quantity', $diff);
                                } else {
                                    // qty turun → kurangi produksi
                                    $productionStock->decrement('available_quantity', abs($diff));
                                }
                            }
                        }
                    }

                    // 🔹 Jika tidak ada perubahan produk/qty tapi hanya perubahan mode,
                    // tetap tambahkan ke produksi (stok pindah ke area produksi)
                    if (
                        empty(array_diff_key($newProducts, $oldProducts)) &&
                        empty(array_diff_key($oldProducts, $newProducts))
                    ) {
                        foreach ($oldProducts as $productId => $qty) {
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $qty); // 🔺 Tambah ke produksi
                        }
                    }
                }

                $order->update(['mode' => $orderMode]);
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
                'customer_address_id',
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
            $addressModel = CustomerAddresses::find($request->customer_address_id);

            // ================== UPDATE ORDER HEADER ==================
            $order->update([
                'customer_id' => $request->customer_id,
                'customer_address_id' => $request->customer_address_id,
                'order_date'       => $request->order_date,
                'due_date'         => $dueDate,
                'payment_method'   => $paymentMethod,
                'status'           => $status,
                'payment_status'   => ($newPaidAmount <= 0) ? 'Unpaid' : (($newPaidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid'),
                'paid_amount'      => $newPaidAmount,
                'business_name'    => $addressModel?->business_name,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
                'discount_active'  => (int) $request->input('discount_active_hidden', 1),
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

                // 🔎 CEK COMPLETED QUANTITY — pastikan quantity baru tidak lebih kecil dari completed_quantity
                // 🔎 CEK PROGRESS (COMPLETED + ACTIVE ASSIGN)
                if ($type === 'satuan') {
                    $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $existingItems[$key]->id ?? null)
                        ->where('product_id', $productId)
                        ->first();

                    if ($progressItem) {
                        // Hitung total active assign dari tabel order_progress_assigns
                        $activeAssign = DB::table('order_progress_assigns')
                            ->where('order_progress_item_id', $progressItem->id)
                            ->selectRaw('COALESCE(SUM(assigned_quantity - (completed_quantity + defect_quantity + reject_quantity)), 0) as active_assign')
                            ->value('active_assign');

                        $requiredMinQty = $progressItem->completed_quantity + $activeAssign;

                        if ($qty < $requiredMinQty) {
                            DB::rollBack();
                            return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity (" . number_format($qty) . ") tidak boleh lebih kecil dari total progress (" . number_format($requiredMinQty) . ") (Completed + Assigning).");
                        }
                    }
                } elseif ($type === 'bundle') {
                    $bundle = \App\Models\ProductBundle::with('items')->find($productId);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $existingItems[$key]->id ?? null)
                                ->where('product_id', $bundleItem->product_id)
                                ->first();

                            if ($progressItem) {
                                $activeAssign = DB::table('order_progress_assigns')
                                    ->where('order_progress_item_id', $progressItem->id)
                                    ->selectRaw('COALESCE(SUM(assigned_quantity - (completed_quantity + defect_quantity + reject_quantity)), 0) as active_assign')
                                    ->value('active_assign');

                                $requiredMinQty = $progressItem->completed_quantity + $activeAssign;

                                if ($qty < $requiredMinQty) {
                                    DB::rollBack();
                                    return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity untuk produk bundle ID {$bundleItem->product_id} (" . number_format($qty) . ") tidak boleh lebih kecil dari total progress (" . number_format($requiredMinQty) . ") (Completed + Assigning).");
                                }
                            }
                        }
                    }
                }

                $warehouseId = $request->inventory_warehouse_id ?? 1;

                // 🔎 CEK PROGRESS HISTORY — pastikan qty baru tidak < total change_quantity
                if ($type === 'satuan') {
                    $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $existingItems[$key]->id ?? null)
                        ->where('product_id', $productId)
                        ->first();

                    if ($progressItem) {
                        $totalChanged = \App\Models\OrderProgressHistory::where('order_progress_item_id', $progressItem->id)
                            ->sum('change_quantity');

                        if ($qty < $totalChanged) {
                            DB::rollBack();
                            return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity (" . number_format($qty) . ") tidak boleh lebih kecil dari total progress (" . number_format($totalChanged) . ").");
                        }
                    }
                } elseif ($type === 'bundle') {
                    $bundle = \App\Models\ProductBundle::with('items')->find($productId);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $existingItems[$key]->id ?? null)
                                ->where('product_id', $bundleItem->product_id)
                                ->first();

                            if ($progressItem) {
                                $totalChanged = \App\Models\OrderProgressHistory::where('order_progress_item_id', $progressItem->id)
                                    ->sum('change_quantity');

                                if ($qty < $totalChanged) {
                                    DB::rollBack();
                                    return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity untuk produk bundle ID {$bundleItem->product_id} (" . number_format($qty) . ") tidak boleh lebih kecil dari total progress (" . number_format($totalChanged) . ").");
                                }
                            }
                        }
                    }
                }

                if ($existingItems->has($key)) {
                    // update item lama
                    $orderItem = $existingItems[$key];
                    $diffQty   = $qty - $orderItem->quantity;

                    // 🔥 CEK jika produk berubah di mode polosan
                    if ($orderMode === 'polosan' && $type === 'satuan') {
                        $oldProductId = $orderItem->product_id;
                        if ($oldProductId != $productId) {
                            // Kembalikan stok produk lama
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $oldProductId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $orderItem->quantity);

                            // Kurangi stok produk baru
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->decrement('available_quantity', $qty);
                        }
                    }

                    // ✅ CEK jika design sudah diverifikasi - PERBAIKAN QUERY
                    $designVerified = \App\Models\Design::where('order_id', $order->id)
                        ->where(function ($q) {
                            $q->whereRaw('LOWER(status) = ?', ['verified'])
                                ->orWhereRaw('LOWER(verification_status) = ?', ['verified']); // tambahkan ini juga
                        })
                        ->exists();

                    // 🔥🔥 LOGGING UNTUK DEBUG
                    if ($designVerified) {
                        Log::info("Design Verified detected for order {$order->id}");
                    }

                    if ($designVerified) {
                        if ($type === 'satuan') {
                            $oldProductId = $orderItem->product_id;

                            // 🔥 Jika produk berubah (A → B)
                            if ($oldProductId != $productId) {
                                Log::info("Product changed from {$oldProductId} to {$productId}");

                                // 🔥 BATALKAN pending_waiting_list produk LAMA (A) - DECREMENT!
                                $oldProductionStock = \App\Models\ProductionStock::where('product_id', $oldProductId)
                                    ->where('production_warehouse_id', 2) // 🔥 TAMBAHKAN INI!
                                    ->lockForUpdate()
                                    ->first();

                                if ($oldProductionStock) {
                                    $decrementQty = min($oldProductionStock->pending_waiting_list, $orderItem->quantity);
                                    if ($decrementQty > 0) {
                                        $oldProductionStock->decrement('pending_waiting_list', $decrementQty);
                                        Log::info("DECREMENTED (cancel booking) pending_waiting_list for product {$oldProductId} by {$decrementQty}");
                                    }
                                }

                                $newProductionStock = \App\Models\ProductionStock::firstOrCreate(
                                    [
                                        'product_id' => $productId,
                                        'production_warehouse_id' => 2 // 🔥 TAMBAHKAN INI!
                                    ],
                                    [
                                        'pending_waiting_list' => 0,
                                        'available_quantity' => 0,
                                        'opening_stock' => 0,
                                        'finished_product_stock' => 0,
                                        'canceled_product_stock' => 0
                                    ]
                                );
                                $newProductionStock->increment('pending_waiting_list', $qty);
                                Log::info("INCREMENTED (new booking) pending_waiting_list for product {$productId} by {$qty}");
                            }
                            // 🔥 Jika produk SAMA tapi quantity berubah
                            elseif ($diffQty !== 0) {
                                $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                    [
                                        'product_id' => $productId,
                                        'production_warehouse_id' => 2 // 🔥 TAMBAHKAN INI!
                                    ],
                                    [
                                        'pending_waiting_list' => 0,
                                        'available_quantity' => 0,
                                        'opening_stock' => 0,
                                        'finished_product_stock' => 0,
                                        'canceled_product_stock' => 0
                                    ]
                                );

                                if ($diffQty > 0) {
                                    // Qty naik → INCREMENT (booking lebih banyak)
                                    $productionStock->increment('pending_waiting_list', $diffQty);
                                } else {
                                    // Qty turun → DECREMENT (kurangi booking)
                                    $productionStock->decrement('pending_waiting_list', abs($diffQty));
                                }
                            } elseif ($type === 'bundle') {
                                $oldBundleId = $orderItem->product_bundle_id;

                                if ($oldBundleId != $productId) {
                                    // BATALKAN pending_waiting_list untuk produk di bundle LAMA
                                    $oldBundle = \App\Models\ProductBundle::with('items')->find($oldBundleId);
                                    if ($oldBundle) {
                                        foreach ($oldBundle->items as $oldBundleItem) {
                                            $oldProductionStock = \App\Models\ProductionStock::where('product_id', $oldBundleItem->product_id)
                                                ->where('production_warehouse_id', 2) // 🔥 TAMBAHKAN INI!
                                                ->lockForUpdate()
                                                ->first();

                                            if ($oldProductionStock) {
                                                $decrementQty = min($oldProductionStock->pending_waiting_list, $orderItem->quantity);
                                                if ($decrementQty > 0) {
                                                    $oldProductionStock->decrement('pending_waiting_list', $decrementQty);
                                                }
                                            }
                                        }
                                    }

                                    // BOOKING BARU pending_waiting_list untuk produk di bundle BARU
                                    $newBundle = \App\Models\ProductBundle::with('items')->find($productId);
                                    if ($newBundle) {
                                        foreach ($newBundle->items as $newBundleItem) {
                                            $newProductionStock = \App\Models\ProductionStock::firstOrCreate(
                                                [
                                                    'product_id' => $newBundleItem->product_id,
                                                    'production_warehouse_id' => 2 // 🔥 TAMBAHKAN INI!
                                                ],
                                                [
                                                    'pending_waiting_list' => 0,
                                                    'available_quantity' => 0,
                                                    'opening_stock' => 0,
                                                    'finished_product_stock' => 0,
                                                    'canceled_product_stock' => 0
                                                ]
                                            );
                                            $newProductionStock->increment('pending_waiting_list', $qty);
                                        }
                                    }
                                }
                                // Bundle sama tapi quantity berubah
                            } elseif ($diffQty !== 0) {
                                $bundle = \App\Models\ProductBundle::with('items')->find($productId);
                                if ($bundle) {
                                    foreach ($bundle->items as $bundleItem) {
                                        $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                            [
                                                'product_id' => $bundleItem->product_id,
                                                'production_warehouse_id' => 2 // 🔥 TAMBAHKAN INI!
                                            ],
                                            [
                                                'pending_waiting_list' => 0,
                                                'available_quantity' => 0,
                                                'opening_stock' => 0,
                                                'finished_product_stock' => 0,
                                                'canceled_product_stock' => 0
                                            ]
                                        );

                                        if ($diffQty > 0) {
                                            $productionStock->increment('pending_waiting_list', $diffQty);
                                        } else {
                                            $productionStock->decrement('pending_waiting_list', abs($diffQty));
                                        }
                                    }
                                }
                            }
                        }
                    }

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
                        $avgCost = $product?->avg_cost ?? 0;

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

                            $product = Products::findOrFail($component->id);
                            $avgCost = $product?->avg_cost ?? 0;
                            $fixedCostAtSale = $product?->fixed_cost ?? 0;
                            $totalQty = $qty;

                            $existing = $orderItem->components()
                                ->where('product_id', $component->id)
                                ->first();

                            if ($existing) {
                                $existing->update([
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'fixed_cost_at_sale' => $fixedCostAtSale,
                                    'total_cost'       => $avgCost * $totalQty,
                                    'total_fixed_cost' => $fixedCostAtSale * $totalQty,
                                ]);
                            } else {
                                $orderItem->components()->create([
                                    'product_id'       => $component->id,
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'fixed_cost_at_sale' => $fixedCostAtSale,
                                    'total_cost'       => $avgCost * $totalQty,
                                    'total_fixed_cost' => $fixedCostAtSale * $totalQty,
                                ]);
                            }
                        }
                    }

                    // 🔥 BARU: Update inventory stock - handle perubahan produk DAN quantity
                    if ($type === 'satuan') {
                        $oldProductId = $orderItem->product_id;

                        // Jika produk berubah
                        if ($oldProductId != $productId) {
                            // Kembalikan stock produk lama
                            $oldInventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $oldProductId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $oldInventoryStock->increment('stock_after_sales', $orderItem->quantity);

                            // Kurangi stock produk baru
                            $newInventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $productId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );
                            $newInventoryStock->decrement('stock_after_sales', $qty);
                        }
                        // Produk sama, tapi quantity berubah
                        elseif ($diffQty !== 0) {
                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $productId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );

                            if ($diffQty > 0) {
                                $inventoryStock->decrement('stock_after_sales', $diffQty);
                            } else {
                                $inventoryStock->increment('stock_after_sales', abs($diffQty));
                            }
                        }
                    } elseif ($type === 'bundle') {
                        $oldBundleId = $orderItem->product_bundle_id;

                        // Jika bundle berubah
                        if ($oldBundleId != $productId) {
                            // Kembalikan stock produk di bundle lama
                            $oldBundle = ProductBundle::with('items.product')->find($oldBundleId);
                            if ($oldBundle) {
                                foreach ($oldBundle->items as $oldBundleItem) {
                                    $oldInventoryStock = InventoryStock::firstOrCreate(
                                        ['product_id' => $oldBundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
                                        ['stock_after_sales' => 0]
                                    );
                                    $oldInventoryStock->increment('stock_after_sales', $orderItem->quantity);
                                }
                            }

                            // Kurangi stock produk di bundle baru
                            $newBundle = ProductBundle::with('items.product')->find($productId);
                            if ($newBundle) {
                                foreach ($newBundle->items as $newBundleItem) {
                                    $newInventoryStock = InventoryStock::firstOrCreate(
                                        ['product_id' => $newBundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
                                        ['stock_after_sales' => 0]
                                    );
                                    $newInventoryStock->decrement('stock_after_sales', $qty);
                                }
                            }
                        }
                        // Bundle sama, tapi quantity berubah
                        elseif ($diffQty !== 0) {
                            $bundle = ProductBundle::with('items.product')->find($productId);
                            if ($bundle) {
                                foreach ($bundle->items as $bundleItem) {
                                    $inventoryStock = InventoryStock::firstOrCreate(
                                        ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
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

                    // 🔥 BARU: Kembalikan available_quantity untuk mode polosan
                    if ($orderMode === 'polosan') {
                        if ($item->satuan === 'satuan') {
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $item->product_id],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $item->quantity);
                        } elseif ($item->satuan === 'bundle') {
                            $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                            if ($bundle) {
                                foreach ($bundle->items as $bundleItem) {
                                    $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                        ['product_id' => $bundleItem->product_id],
                                        ['available_quantity' => 0]
                                    );
                                    $productionStock->increment('available_quantity', $item->quantity);
                                }
                            }
                        }
                    }

                    // (code existing untuk inventoryStock tetap ada di bawahnya)

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

            // ================== SYNC MODE PRINTING ATAU POLOSAN ==================
            if ($orderMode === 'printing') {

                // ================== SYNC DESIGN & DESIGN ITEMS ==================
                $design = $order->designs()->with('items')->first();

                if ($design) {
                    // Jika design sudah ada → update tanggal, status, dll
                    $design->update([
                        'date'                 => now()->format('Y-m-d'),
                        'notes'                => $request->notes ?? $design->notes,
                    ]);

                    // Ambil design_items yang sudah ada
                    $existingDesignItems = $design->items->keyBy(function ($item) {
                        return $item->order_item_id . '_' . $item->product_id;
                    });

                    $newDesignKeys = [];

                    foreach ($order->orderItems as $orderItem) {
                        $qty = $orderItem->quantity;

                        if ($orderItem->satuan === 'satuan') {
                            $key = $orderItem->id . '_' . $orderItem->product_id;
                            $newDesignKeys[] = $key;

                            if ($existingDesignItems->has($key)) {
                                // update item design yang sudah ada
                                $existingDesignItems[$key]->update([
                                    'quantity'             => $qty,
                                ]);
                            } else {
                                // buat baru
                                DesignItem::create([
                                    'design_id'            => $design->id,
                                    'order_item_id'        => $orderItem->id,
                                    'product_id'           => $orderItem->product_id,
                                    'quantity'             => $qty,
                                    'completed_quantity'   => 0,
                                    'design_file'          => null,
                                    'preview_image'        => null,
                                    'verification_status'  => 'pending',
                                ]);
                            }
                        } elseif ($orderItem->satuan === 'bundle') {
                            foreach ($orderItem->productBundle->items as $bundleItem) {
                                $bundleProduct = $bundleItem->product;
                                if (!$bundleProduct) continue;

                                $key = $orderItem->id . '_' . $bundleProduct->id;
                                $newDesignKeys[] = $key;

                                if ($existingDesignItems->has($key)) {
                                    $existingDesignItems[$key]->update([
                                        'quantity'             => $qty,
                                    ]);
                                } else {
                                    DesignItem::create([
                                        'design_id'            => $design->id,
                                        'order_item_id'        => $orderItem->id,
                                        'product_id'           => $bundleProduct->id,
                                        'quantity'             => $qty,
                                        'completed_quantity'   => 0,
                                        'design_file'          => null,
                                        'preview_image'        => null,
                                        'verification_status'  => 'pending',
                                    ]);
                                }
                            }
                        }
                    }

                    // Hapus design_items lama yang tidak ada di order_items baru
                    foreach ($existingDesignItems as $key => $designItem) {
                        if (!in_array($key, $newDesignKeys)) {
                            $designItem->delete();
                        }
                    }
                } else {
                    // ✅ CEK DULU kalau ada design soft-deleted dengan order_id sama, jangan bikin baru
                    $existingDesign = \App\Models\Design::withTrashed()
                        ->where('order_id', $order->id)
                        ->first();

                    if ($existingDesign) {
                        // kalau ada, restore & update aja
                        $existingDesign->restore();
                        $existingDesign->update([
                            'date'                => now()->format('Y-m-d'),
                            'status'              => 'Pending',
                            'notes'               => $request->notes ?? $existingDesign->notes,
                            'verification_status' => 'pending',
                        ]);
                        $design = $existingDesign;
                    } else {
                        // Kalau belum ada design sama sekali (benar-benar baru)
                        $design = Design::create([
                            'order_id'            => $order->id,
                            'design_number'       => $order->order_number,
                            'date'                => now()->format('Y-m-d'),
                            'status'              => 'Pending',
                            'notes'               => $request->notes ?? null,
                            'verification_status' => 'pending',
                        ]);
                    }

                    // ⬇️ Bagian di bawah ini TIDAK dihapus, tetap sama seperti punyamu
                    foreach ($order->orderItems as $orderItem) {
                        $qty = $orderItem->quantity;

                        if ($orderItem->satuan === 'satuan') {
                            DesignItem::create([
                                'design_id'            => $design->id,
                                'order_item_id'        => $orderItem->id,
                                'product_id'           => $orderItem->product_id,
                                'quantity'             => $qty,
                                'completed_quantity'   => 0,
                                'design_file'          => null,
                                'preview_image'        => null,
                                'verification_status'  => 'pending',
                            ]);
                        } elseif ($orderItem->satuan === 'bundle') {
                            foreach ($orderItem->productBundle->items as $bundleItem) {
                                $bundleProduct = $bundleItem->product;
                                if (!$bundleProduct) continue;

                                DesignItem::create([
                                    'design_id'            => $design->id,
                                    'order_item_id'        => $orderItem->id,
                                    'product_id'           => $bundleProduct->id,
                                    'quantity'             => $qty,
                                    'completed_quantity'   => 0,
                                    'design_file'          => null,
                                    'preview_image'        => null,
                                    'verification_status'  => 'pending',
                                ]);
                            }
                        }
                    }
                }


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

                // ================== SYNC DELIVERY ORDER & ITEMS ==================
                $orderProgress = $order->orderProgress()->first(); // ambil progress di awal
                if ($orderProgress) {
                    $deliveryOrder = $order->deliveryOrders()->with('items')->first();

                    if ($deliveryOrder) {
                        // Ambil data customer & alamat terbaru dari order
                        $customerName     = $order->customer?->name ?? $deliveryOrder->customer;
                        $shippingAddress  = $order->shipping_address ?? $deliveryOrder->shipping_address;
                        $googleMapLink    = $order->google_maps ?? $deliveryOrder->google_map_link;

                        // ✅ Update header Delivery Order
                        $deliveryOrder->update([
                            'delivery_date'     => now()->format('Y-m-d'),
                            'note'              => $request->notes ?? $deliveryOrder->note,
                            // 'status'            => 'Pending',
                            'customer'          => $customerName,
                            'shipping_address'  => $shippingAddress,
                            'google_map_link'   => $googleMapLink,
                        ]);

                        // data lama
                        $existingDoItems = $deliveryOrder->items->keyBy(function ($item) {
                            return $item->order_progress_id . '_' . $item->product_id;
                        });

                        $newDoKeys = [];

                        foreach ($order->orderItems as $orderItem) {
                            $qty = $orderItem->quantity;

                            if ($orderItem->satuan === 'satuan') {
                                $productId = $orderItem->product_id;
                                $progressId = $orderProgress->id;

                                $key = "{$progressId}_{$productId}";
                                $newDoKeys[] = $key;

                                if ($existingDoItems->has($key)) {
                                    $existingDoItems[$key]->update([
                                        'progress_qty' => $qty,
                                        'note'         => $request->notes ?? $existingDoItems[$key]->note,
                                    ]);
                                } else {
                                    \App\Models\DeliveryOrderItem::create([
                                        'delivery_order_id'   => $deliveryOrder->id,
                                        'order_progress_id'   => $progressId,
                                        'order_item_id'       => $orderItem->id,
                                        'order_progress_item_id' => \App\Models\OrderProgressItem::where('order_progress_id', $progressId)
                                            ->where('order_item_id', $orderItem->id)
                                            ->where('product_id', $productId)
                                            ->value('id'),
                                        'product_id'          => $productId,
                                        'status'              => 'pending',
                                        'progress_qty'        => $qty,
                                        'ready_qty'           => 0,
                                        'shipped_qty'         => 0,
                                        'note'                => $request->notes ?? null,
                                    ]);
                                }
                            } elseif ($orderItem->satuan === 'bundle') {
                                foreach ($orderItem->productBundle->items as $bundleItem) {
                                    $productId = $bundleItem->product_id;
                                    if (!$productId) continue;

                                    $progressId = $orderProgress->id;
                                    $key = "{$progressId}_{$productId}";
                                    $newDoKeys[] = $key;

                                    if ($existingDoItems->has($key)) {
                                        $existingDoItems[$key]->update([
                                            'progress_qty' => $qty,
                                            'note'         => $request->notes ?? $existingDoItems[$key]->note,
                                        ]);
                                    } else {
                                        \App\Models\DeliveryOrderItem::create([
                                            'delivery_order_id'   => $deliveryOrder->id,
                                            'order_progress_id'   => $progressId,
                                            'order_item_id'       => $orderItem->id,
                                            'order_progress_item_id' => \App\Models\OrderProgressItem::where('order_progress_id', $progressId)
                                                ->where('order_item_id', $orderItem->id)
                                                ->where('product_id', $productId)
                                                ->value('id'),
                                            'product_id'          => $productId,
                                            'status'              => 'pending',
                                            'progress_qty'        => $qty,
                                            'ready_qty'           => 0,
                                            'shipped_qty'         => 0,
                                            'note'                => $request->notes ?? null,
                                        ]);
                                    }
                                }
                            }
                        }

                        // hapus item DO lama yang sudah tidak ada di order
                        foreach ($existingDoItems as $key => $doItem) {
                            if (!in_array($key, $newDoKeys)) {
                                $doItem->delete();
                            }
                        }
                    } else {
                        // ❗Jika belum ada Delivery Order, tapi progress ada, boleh buat baru
                        $deliveryOrder = \App\Models\DeliveryOrder::create([
                            'order_id'          => $order->id,
                            'delivery_number'   => 'DO-' . now()->format('YmdHis'),
                            'delivery_date'     => now()->format('Y-m-d'),
                            'note'              => $request->notes ?? null,
                            'status'            => 'Pending',
                            'customer'          => $order->customer->name ?? '-',
                            'shipping_address'  => $order->shipping_address,
                            'google_map_link'   => $order->google_maps,
                            'created_by'        => Auth::id(),
                        ]);

                        foreach ($order->orderItems as $orderItem) {
                            $qty = $orderItem->quantity;

                            if ($orderItem->satuan === 'satuan') {
                                \App\Models\DeliveryOrderItem::create([
                                    'delivery_order_id'   => $deliveryOrder->id,
                                    'order_progress_id'   => $orderProgress->id,
                                    'order_item_id'       => $orderItem->id,
                                    'order_progress_item_id' => \App\Models\OrderProgressItem::where('order_progress_id', $orderProgress->id)
                                        ->where('order_item_id', $orderItem->id)
                                        ->where('product_id', $orderItem->product_id)
                                        ->value('id'),
                                    'product_id'          => $orderItem->product_id,
                                    'status'              => 'pending',
                                    'progress_qty'        => $qty,
                                    'ready_qty'           => 0,
                                    'shipped_qty'         => 0,
                                    'note'                => $request->notes ?? null,
                                ]);
                            } elseif ($orderItem->satuan === 'bundle') {
                                foreach ($orderItem->productBundle->items as $bundleItem) {
                                    $productId = $bundleItem->product_id;
                                    if (!$productId) continue;

                                    \App\Models\DeliveryOrderItem::create([
                                        'delivery_order_id'   => $deliveryOrder->id,
                                        'order_progress_id'   => $orderProgress->id,
                                        'order_item_id'       => $orderItem->id,
                                        'order_progress_item_id' => \App\Models\OrderProgressItem::where('order_progress_id', $orderProgress->id)
                                            ->where('order_item_id', $orderItem->id)
                                            ->where('product_id', $productId)
                                            ->value('id'),
                                        'product_id'          => $productId,
                                        'status'              => 'pending',
                                        'progress_qty'        => $qty,
                                        'ready_qty'           => 0,
                                        'shipped_qty'         => 0,
                                        'note'                => $request->notes ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    // ❌ Tidak ada OrderProgress → skip update DO
                    Log::info("Skip sinkronisasi Delivery Order karena OrderProgress belum ada untuk Order ID {$order->id}");
                }
            } else {
                // ================== MODE POLOSAN (Tanpa Design & Progress) ==================
                $warehouseId = $request->inventory_warehouse_id ?? 1;

                // ✅ Cek termasuk yang soft deleted
                $deliveryOrder = \App\Models\DeliveryOrder::withTrashed()
                    ->where('order_id', $order->id)
                    ->first();

                if ($deliveryOrder) {
                    // kalau DO-nya soft deleted, restore aja
                    if ($deliveryOrder->trashed()) {
                        $deliveryOrder->restore();
                    }

                    // update aja DO-nya
                    $deliveryOrder->update([
                        'delivery_date'    => $order->order_date,
                        'note'             => $request->notes,
                        'status'           => 'Ongoing',
                        'customer'         => $order->customer?->name ?? '-',
                        'shipping_address' => $order->shipping_address,
                        'google_map_link'  => $order->google_maps,
                    ]);
                } else {
                    // kalau memang belum ada sama sekali, baru buat
                    $deliveryOrder = \App\Models\DeliveryOrder::create([
                        'order_id'         => $order->id,
                        'design_id'        => null,
                        'delivery_number'  => $order->order_number,
                        'delivery_date'    => $order->order_date,
                        'note'             => $request->notes,
                        'status'           => 'Ongoing',
                        'customer'         => $order->customer?->name ?? '-',
                        'shipping_address' => $order->shipping_address,
                        'google_map_link'  => $order->google_maps,
                        'created_by'       => Auth::id(),
                    ]);
                }

                // Sinkronisasi Delivery Order Items
                // Sinkronisasi Delivery Order Items
                $existingDoItems = $deliveryOrder->items->keyBy('product_id');
                $newDoKeys = [];

                foreach ($order->orderItems as $orderItem) {
                    $pidList = [];

                    if ($orderItem->satuan === 'satuan') {
                        $pidList[] = $orderItem->product_id;
                    } elseif ($orderItem->satuan === 'bundle') {
                        foreach ($orderItem->productBundle->items as $bundleItem) {
                            if ($bundleItem->product_id) {
                                $pidList[] = $bundleItem->product_id;
                            }
                        }
                    }

                    foreach ($pidList as $pid) {
                        $newDoKeys[] = $pid;

                        // update atau buat DO item
                        $existingItem = $existingDoItems->get($pid);
                        if ($existingItem) {
                            // 🔥 HITUNG DIFF QUANTITY untuk update ProductionStock
                            $oldQty = $existingItem->ready_qty;
                            $newQty = $orderItem->quantity;
                            $diffQty = $newQty - $oldQty;

                            $existingItem->update([
                                'ready_qty'    => $orderItem->quantity,
                                'progress_qty' => 0,
                                'shipped_qty'  => 0,
                                'note'         => $request->notes,
                            ]);

                            // 🔥 UPDATE AVAILABLE_QUANTITY di ProductionStock untuk mode POLOSAN
                            if ($diffQty !== 0) {
                                $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                    ['product_id' => $pid],
                                    ['available_quantity' => 0]
                                );

                                if ($diffQty > 0) {
                                    // Qty bertambah → kurangi available_quantity (stok keluar lebih banyak)
                                    $productionStock->decrement('available_quantity', $diffQty);
                                } else {
                                    // Qty berkurang → tambah available_quantity (stok kembali)
                                    $productionStock->increment('available_quantity', abs($diffQty));
                                }
                            }
                        } else {
                            \App\Models\DeliveryOrderItem::create([
                                'delivery_order_id' => $deliveryOrder->id,
                                'order_item_id'     => $orderItem->id,
                                'product_id'        => $pid,
                                'status'            => 'Pending',
                                'progress_qty'      => 0,
                                'ready_qty'         => $orderItem->quantity,
                                'shipped_qty'       => 0,
                                'note'              => $request->notes,
                            ]);

                            // 🔥 UNTUK ITEM BARU, decrement available_quantity
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $pid],
                                ['available_quantity' => 0]
                            );
                            $productionStock->decrement('available_quantity', $orderItem->quantity);
                        }
                    }
                }

                // Simpan snapshot delivery order items SEBELUM loop update
                $existingDoItemsSnapshot = $deliveryOrder->items()
                    ->get(['id', 'product_id', 'ready_qty'])
                    ->keyBy('product_id');

                // ... (loop update/create tetap sama seperti code Anda)

                // Hapus DO item lama yang tidak ada di order baru
                foreach ($existingDoItemsSnapshot as $pid => $oldItem) {
                    if (!in_array($pid, $newDoKeys)) {
                        // 🔥 KEMBALIKAN available_quantity sebelum delete
                        $restoreQty = $oldItem->ready_qty ?? 0;
                        if ($restoreQty > 0) {
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $pid],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $restoreQty);
                        }

                        // Hapus item dari database
                        $deliveryOrder->items()->where('product_id', $pid)->delete();
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

            try {
                $financialReport = FinancialReport::where('transaction_type', 'sale')
                    ->where('reference_id', $order->id)
                    ->where('reference_table', 'orders')
                    ->first();

                $totalRevenue = $request->total_amount;
                $totalCogs = 0;
                $totalFixedCost = 0;

                // Hitung ulang COGS & Fixed Cost berdasarkan produk dan bundle
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {
                        // Produk satuan
                        $product = $orderItem->product;
                        $avgCost = $product->avg_cost ?? 0;
                        $fixedCost = $product->fixed_cost ?? 0;
                        $totalCogs += $avgCost * $orderItem->quantity;
                        $totalFixedCost += $fixedCost * $orderItem->quantity;
                    } elseif ($orderItem->product_bundle_id) {
                        // Produk bundle
                        $bundle = $orderItem->productBundle;

                        $bundleAvgCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->avg_cost ?? 0;
                        });

                        $bundleFixedCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;
                            return $product->fixed_cost ?? 0;
                        });

                        $totalCogs += $bundleAvgCost * $orderItem->quantity;
                        $totalFixedCost += $bundleFixedCost * $orderItem->quantity;
                    }
                }

                $grossProfit = $totalRevenue - $totalCogs;
                $grossProfitAtFixedCost = $totalRevenue - $totalFixedCost;
                $netProfit = $grossProfit; // belum ada expense
                $netProfitAtFixedCost = $grossProfitAtFixedCost;

                if ($financialReport) {
                    // Update report lama
                    $financialReport->update([
                        'date'                      => $order->order_date,
                        'revenue'                   => $totalRevenue,
                        'cogs'                      => $totalCogs,
                        'cogs_fixed_cost'           => $totalFixedCost,
                        'gross_profit'              => $grossProfit,
                        'gross_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'expense'                   => 0,
                        'net_profit'                => $netProfit,
                        'net_profit_at_fixed_cost'  => $netProfitAtFixedCost,
                        'notes'                     => 'Auto-updated from Sale List Edit',
                    ]);
                } else {
                    // Buat baru kalau belum ada
                    FinancialReport::create([
                        'date'                      => $order->order_date,
                        'transaction_type'          => 'sale',
                        'reference_id'              => $order->id,
                        'reference_table'           => 'orders',
                        'revenue'                   => $totalRevenue,
                        'cogs'                      => $totalCogs,
                        'cogs_fixed_cost'           => $totalFixedCost,
                        'gross_profit'              => $grossProfit,
                        'gross_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'expense'                   => 0,
                        'net_profit'                => $netProfit,
                        'net_profit_at_fixed_cost'  => $netProfitAtFixedCost,
                        'notes'                     => 'Auto-generated from Sale List Edit',
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

    // public function delete($id, Request $request)
    // {
    //     $request->validate([
    //         'delete_notes' => 'required|string|max:1000',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $order = Order::with(['orderItems', 'deliveryOrders.shipments'])->findOrFail($id);

    //         $hasProgressHistory = OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->exists();

    //         if ($hasProgressHistory) {
    //             DB::rollBack();
    //             return back()->with('error', 'Tidak dapat menghapus order ini karena sudah memiliki progress history produksi.');
    //         }

    //         // 🔹 CEK 2: Progress Assign
    //         $hasAssign = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->exists();

    //         if ($hasAssign) {
    //             DB::rollBack();
    //             return back()->with('error', 'Tidak dapat menghapus order ini karena sudah memiliki progress assign produksi.');
    //         }

    //         // 🔹 CEK 3: Finished Delivery
    //         $hasFinishedDelivery = $order->deliveryOrders
    //             ->flatMap->shipments
    //             ->contains(fn($shipment) => $shipment->status === 'Finished');

    //         if ($hasFinishedDelivery) {
    //             DB::rollBack();
    //             return back()->with('error', 'Tidak bisa menghapus order ini karena sudah ada Delivery List.');
    //         }

    //         // 🔎 Cek apakah order punya sale return
    //         if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
    //             DB::rollBack();
    //             return back()->with('error', 'Tidak bisa menghapus order ini karena sudah ada Sale Return.');
    //         }

    //         $warehouseId = $request->inventory_warehouse_id ?? 1;

    //         // ======================================================
    //         // 🔹 HANDLE PERUBAHAN STOK BERDASARKAN MODE
    //         // ======================================================

    //         if ($order->mode === 'printing') {
    //             // MODE PRINTING → rollback stok produksi
    //             $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))
    //                 ->get(['id', 'product_id', 'order_item_id', 'quantity']);

    //             foreach ($progressItems as $progressItem) {
    //                 if (!$progressItem->product_id || $progressItem->quantity <= 0) continue;

    //                 $productionStock = ProductionStock::firstOrCreate(
    //                     ['product_id' => $progressItem->product_id],
    //                     ['available_quantity' => 0, 'pending_waiting_list' => 0, 'finished_product_stock' => 0]
    //                 );

    //                 // kembalikan stok ke available
    //                 $productionStock->increment('available_quantity', $progressItem->quantity);
    //             }
    //         }

    //         if ($order->mode === 'polosan') {
    //             // MODE POLOSAN → barang sudah keluar ke delivery, kembalikan stok ke produksi
    //             foreach ($order->orderItems as $item) {
    //                 if ($item->satuan === 'satuan' && $item->product_id) {
    //                     $productionStock = ProductionStock::firstOrCreate(
    //                         ['product_id' => $item->product_id],
    //                         ['available_quantity' => 0]
    //                     );
    //                     $productionStock->increment('available_quantity', $item->quantity);
    //                 } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
    //                     $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
    //                     if ($bundle) {
    //                         foreach ($bundle->items as $bundleItem) {
    //                             if (!$bundleItem->product_id) continue;
    //                             $productionStock = ProductionStock::firstOrCreate(
    //                                 ['product_id' => $bundleItem->product_id],
    //                                 ['available_quantity' => 0]
    //                             );
    //                             $productionStock->increment('available_quantity', $item->quantity);
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         // 🔁 Kembalikan stok untuk setiap item di inventory
    //         foreach ($order->orderItems as $item) {
    //             if ($item->satuan === 'satuan' && $item->product_id) {
    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
    //                     ['stock_after_sales' => 0]
    //                 );
    //                 $inventoryStock->increment('stock_after_sales', $item->quantity);
    //             } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
    //                 $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
    //                 if ($bundle) {
    //                     foreach ($bundle->items as $bundleItem) {
    //                         if (!$bundleItem->product_id) continue;

    //                         $inventoryStock = InventoryStock::firstOrCreate(
    //                             ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $warehouseId],
    //                             ['stock_after_sales' => 0]
    //                         );
    //                         $inventoryStock->increment('stock_after_sales', $item->quantity);
    //                     }
    //                 }
    //             }
    //         }

    //         // 🔁 Ambil semua progress item untuk order ini
    //         // $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->get();

    //         // foreach ($progressItems as $progressItem) {
    //         //     $completedQty = (int) $progressItem->completed_quantity;
    //         //     if ($completedQty <= 0 || !$progressItem->product_id) continue;

    //         //     $productionStock = ProductionStock::firstOrCreate(
    //         //         ['product_id' => $progressItem->product_id, 'production_warehouse_id' => 2],
    //         //         ['canceled_product_stock' => 0, 'finished_product_stock' => 0]
    //         //     );

    //         //     // 🔎 cek apakah progress ini sudah ada di delivery_order_items dengan shipped_qty > 0
    //         //     $hasShipped = DeliveryOrderItem::where(function ($q) use ($progressItem) {
    //         //         $q->where('order_progress_id', $progressItem->id)
    //         //             ->orWhere('order_item_id', $progressItem->order_item_id);
    //         //     })
    //         //         ->where('shipped_qty', '>', 0)
    //         //         ->exists();

    //         //     // ✅ canceled product selalu bertambah
    //         //     $productionStock->increment('canceled_product_stock', $completedQty);

    //         //     // ✅ Simpan ke tabel canceled_products (ledger detail)
    //         //     CanceledProduct::create([
    //         //         'production_stock_id' => $productionStock->id,
    //         //         'product_id'          => $progressItem->product_id,
    //         //         'warehouse_id'        => $productionStock->production_warehouse_id,
    //         //         'order_id'            => $order->id,
    //         //         'order_item_id'       => $progressItem->order_item_id,
    //         //         'quantity'            => $completedQty,
    //         //         'date'                => now(),
    //         //         'type'                => 'from_order_delete',
    //         //         'status'              => 'pending', // masih di pool canceled
    //         //         'note'                => 'Canceled product from order delete',
    //         //         'created_by'          => Auth::id(),
    //         //     ]);

    //         //     if (!$hasShipped) {
    //         //         // ✅ hanya kalau BELUM dikirim: kurangi finished_product_stock
    //         //         $productionStock->decrement('finished_product_stock', $completedQty);
    //         //     }
    //         // }

    //         // 🔁 Ambil semua progress item untuk order ini
    //         $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))
    //             ->get(['id', 'product_id', 'order_item_id', 'quantity']);

    //         foreach ($progressItems as $progressItem) {
    //             $qty = (int) $progressItem->quantity;
    //             if ($qty <= 0 || !$progressItem->product_id) continue;

    //             $productionStock = ProductionStock::firstOrCreate(
    //                 ['product_id' => $progressItem->product_id, 'production_warehouse_id' => 2],
    //                 [
    //                     'available_quantity'     => 0,
    //                     'finished_product_stock' => 0,
    //                     'pending_waiting_list'   => 0,
    //                     'canceled_product_stock' => 0,
    //                 ]
    //             );

    //             // snapshot sebelum
    //             $beforeAvail   = (int) $productionStock->available_quantity;
    //             $beforeFinish  = (int) $productionStock->finished_product_stock;
    //             $beforePending = (int) $productionStock->pending_waiting_list;

    //             // ✅ Kurangi finished & pending (tidak boleh minus)
    //             // $productionStock->finished_product_stock = max(0, $beforeFinish - min($qty, $beforeFinish));
    //             $productionStock->pending_waiting_list   = max(0, $beforePending - min($qty, $beforePending));

    //             // 🔍 Cek apakah ada assign di order_progress_assigns untuk progress item ini
    //             $totalAssigned = \App\Models\OrderProgressAssign::where('order_progress_item_id', $progressItem->id)
    //                 ->sum('assigned_quantity');

    //             if ($totalAssigned > 0) {
    //                 // ✅ Kembalikan assign ke stok available & pending
    //                 $productionStock->available_quantity   = $beforeAvail + $totalAssigned;
    //             }

    //             $productionStock->save();
    //         }

    //         // 🔁 Handle account transactions
    //         $transactions = AccountTransaction::where('order_id', $order->id)->get();
    //         foreach ($transactions as $trx) {
    //             $account = Account::find($trx->account_id);
    //             if (!$account) continue;

    //             if ($account->type === 'Sale Account') {
    //                 $account->closing_balance += $trx->debit;
    //                 $account->closing_balance -= $trx->credit;
    //                 $trx->delete();
    //             } else {
    //                 $trx->order_id = null;
    //                 $trx->note = trim(($trx->note ?? '') . ' [Order deleted]');
    //                 $trx->save();
    //             }

    //             $account->save();
    //         }

    //         // 🔁 Bersihkan relasi lain
    //         // OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->delete();
    //         // OrderItem::where('order_id', $order->id)->delete();
    //         // OrderProgress::where('order_id', $order->id)->delete();

    //         $progresses = OrderProgress::where('order_id', $order->id)->get();

    //         foreach ($progresses as $progress) {
    //             $progress->delete(); // ini akan trigger booted() di OrderProgress
    //         }

    //         // Setelah itu, hapus design dan item-nya juga
    //         $designs = Design::where('order_id', $order->id)->get();
    //         foreach ($designs as $design) {
    //             $design->delete(); // trigger booted() di Design kalau ditambah event
    //         }

    //         OrderEditHistory::where('order_id', $order->id)->delete();

    //         // Simpan delete_notes & deleted_by sebelum soft delete
    //         $order->delete_notes = $request->input('delete_notes');
    //         $order->deleted_by   = Auth::id();
    //         $order->save();

    //         // Soft delete order
    //         $order->delete();

    //         FinancialReport::where('reference_table', 'orders')
    //             ->where('reference_id', $order->id)
    //             ->update(['deleted_at' => now()]);

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Order berhasil dihapus.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Order delete failed: ' . $e->getMessage());
    //         return back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
    //     }
    // }

    public function delete($id, Request $request)
    {
        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with(['orderItems', 'deliveryOrders.shipments'])->findOrFail($id);

            // =========================================================
            // 🔹 CEK 1: Progress History
            // =========================================================
            $hasProgressHistory = OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->exists();

            if ($hasProgressHistory) {
                DB::rollBack();
                $msg = 'Tidak dapat menghapus order ini karena sudah memiliki progress history produksi.';
                return $this->deleteResponse($request, false, $msg);
            }

            // =========================================================
            // 🔹 CEK 2: Progress Assign
            // =========================================================
            $hasAssign = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->exists();

            if ($hasAssign) {
                DB::rollBack();
                $msg = 'Tidak dapat menghapus order ini karena sudah memiliki progress assign produksi.';
                return $this->deleteResponse($request, false, $msg);
            }

            // =========================================================
            // 🔹 CEK 3: Finished Delivery
            // =========================================================
            $hasFinishedDelivery = $order->deliveryOrders
                ->flatMap->shipments
                ->contains(fn($shipment) => $shipment->status === 'Finished');

            if ($hasFinishedDelivery) {
                DB::rollBack();
                $msg = 'Tidak bisa menghapus order ini karena sudah ada Delivery List.';
                return $this->deleteResponse($request, false, $msg);
            }

            // =========================================================
            // 🔹 CEK 4: Sale Return
            // =========================================================
            if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
                DB::rollBack();
                $msg = 'Tidak bisa menghapus order ini karena sudah ada Sale Return.';
                return $this->deleteResponse($request, false, $msg);
            }

            $warehouseId = $request->inventory_warehouse_id ?? 1;

            // ======================================================
            // 🔹 HANDLE PERUBAHAN STOK BERDASARKAN MODE
            // ======================================================
            if ($order->mode === 'printing') {
                $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))
                    ->get(['id', 'product_id', 'order_item_id', 'quantity']);

                // 🔍 Cek apakah design verified
                $hasVerifiedDesign = Design::where('order_id', $order->id)
                    ->where('status', 'Verified')
                    ->exists();

                foreach ($progressItems as $progressItem) {
                    if (!$progressItem->product_id || $progressItem->quantity <= 0) continue;

                    $productionStock = ProductionStock::firstOrCreate(
                        ['product_id' => $progressItem->product_id],
                        ['available_quantity' => 0, 'pending_waiting_list' => 0, 'finished_product_stock' => 0]
                    );

                    if ($hasVerifiedDesign) {
                        // 🔹 Kalau design verified → kurangi pending_waiting_list
                        $beforePending = (int) $productionStock->pending_waiting_list;
                        $productionStock->pending_waiting_list = max(0, $beforePending - min($progressItem->quantity, $beforePending));
                    }

                    // ❌ JANGAN SENTUH available_quantity
                    $productionStock->save();
                }
            }


            if ($order->mode === 'polosan') {
                foreach ($order->orderItems as $item) {
                    if ($item->satuan === 'satuan' && $item->product_id) {
                        $productionStock = ProductionStock::firstOrCreate(
                            ['product_id' => $item->product_id],
                            ['available_quantity' => 0]
                        );
                        $productionStock->increment('available_quantity', $item->quantity);
                    } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
                        $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                if (!$bundleItem->product_id) continue;
                                $productionStock = ProductionStock::firstOrCreate(
                                    ['product_id' => $bundleItem->product_id],
                                    ['available_quantity' => 0]
                                );
                                $productionStock->increment('available_quantity', $item->quantity);
                            }
                        }
                    }
                }
            }

            // ======================================================
            // 🔁 Kembalikan stok di inventory
            // ======================================================
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

            // ======================================================
            // 🔁 Handle progress assign rollback
            // ======================================================
            $progressItems = OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))
                ->get(['id', 'product_id', 'order_item_id', 'quantity']);

            // 🔍 Cek apakah order ini punya design yang verified
            $hasVerifiedDesign = Design::where('order_id', $order->id)
                ->where('status', 'Verified')
                ->exists();

            foreach ($progressItems as $progressItem) {
                $qty = (int) $progressItem->quantity;
                if ($qty <= 0 || !$progressItem->product_id) continue;

                $productionStock = ProductionStock::firstOrCreate(
                    ['product_id' => $progressItem->product_id, 'production_warehouse_id' => 2],
                    [
                        'available_quantity'     => 0,
                        'finished_product_stock' => 0,
                        'pending_waiting_list'   => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                $beforeAvail   = (int) $productionStock->available_quantity;
                $beforePending = (int) $productionStock->pending_waiting_list;

                // 🔹 Hanya decrement pending waiting list jika design sudah verified
                // if ($hasVerifiedDesign) {
                //     $productionStock->pending_waiting_list = max(0, $beforePending - min($qty, $beforePending));
                // }

                // 🔍 Cek apakah ada assign di order_progress_assigns untuk progress item ini
                // $totalAssigned = \App\Models\OrderProgressAssign::where('order_progress_item_id', $progressItem->id)
                //     ->sum('assigned_quantity');

                // if ($totalAssigned > 0) {
                //     // ✅ Kembalikan assign ke stok available
                //     $productionStock->available_quantity = $beforeAvail + $totalAssigned;
                // }

                if ($hasVerifiedDesign) {
                    $beforePending = (int) $productionStock->pending_waiting_list;
                    $productionStock->pending_waiting_list = max(0, $beforePending - min($qty, $beforePending));
                }

                $productionStock->save();
            }

            // ======================================================
            // 🔁 Handle transaksi keuangan
            // ======================================================
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

            // ======================================================
            // 🔁 Hapus relasi lain
            // ======================================================
            $progresses = OrderProgress::where('order_id', $order->id)->get();
            foreach ($progresses as $progress) {
                $progress->delete();
            }

            $designs = Design::where('order_id', $order->id)->get();
            foreach ($designs as $design) {
                $design->delete();
            }

            OrderEditHistory::where('order_id', $order->id)->delete();

            $order->delete_notes = $request->input('delete_notes');
            $order->deleted_by   = Auth::id();
            $order->save();
            $order->delete();

            FinancialReport::where('reference_table', 'orders')
                ->where('reference_id', $order->id)
                ->update(['deleted_at' => now()]);

            DB::commit();
            return $this->deleteResponse($request, true, 'Order berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order delete failed: ' . $e->getMessage());
            return $this->deleteResponse($request, false, 'Gagal menghapus order: ' . $e->getMessage());
        }
    }

    /**
     * Helper function untuk kirim response yang sesuai (AJAX vs non-AJAX)
     */
    private function deleteResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => $success ? 'success' : 'error',
                'message' => $message,
            ], $success ? 200 : 400);
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', $message);
    }

    // public function forceDeleteOwner($id, Request $request)
    // {
    //     // ⛔️ batasi hanya Owner
    //     if (!Auth::check() || Auth::user()->role !== 'Owner') {
    //         abort(403, 'Only Owner can force delete.');
    //     }

    //     $request->validate([
    //         'delete_notes' => 'required|string|max:1000',
    //         'inventory_warehouse_id' => 'nullable|integer',
    //         'production_warehouse_id' => 'nullable|integer',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         // ambil order + relasi inti
    //         $order = Order::with(['orderItems'])->findOrFail($id);

    //         $hasSaleReturn = SaleReturn::where('sale_order_id', $order->id)->exists();

    //         if ($hasSaleReturn) {
    //             DB::rollBack();
    //             return back()->with('swal_warning', 'Order ini masih memiliki Sale Return. Hapus Sale Return terlebih dahulu sebelum menghapus order ini.');
    //         }

    //         $inventoryWarehouseId  = $request->inventory_warehouse_id  ?? 1;
    //         $productionWarehouseId = $request->production_warehouse_id ?? 2;

    //         /**
    //          * 1) Rollback INVENTORY STOCK (seperti delete biasa)
    //          */
    //         foreach ($order->orderItems as $item) {
    //             if ($item->satuan === 'satuan' && $item->product_id) {
    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     ['product_id' => $item->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
    //                     ['stock_after_sales' => 0]
    //                 );
    //                 $inventoryStock->increment('stock_after_sales', $item->quantity);
    //             } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
    //                 $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
    //                 if ($bundle) {
    //                     foreach ($bundle->items as $bundleItem) {
    //                         if (!$bundleItem->product_id) continue;

    //                         // NOTE: tetap pakai quantity = qty order item (sesuai delete() normal kamu)
    //                         $inventoryStock = InventoryStock::firstOrCreate(
    //                             ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
    //                             ['stock_after_sales' => 0]
    //                         );
    //                         $inventoryStock->increment('stock_after_sales', $item->quantity);
    //                     }
    //                 }
    //             }
    //         }

    //         /**
    //          * 2) Rollback PRODUCTION STOCK berdasar PROGRESS HISTORY
    //          *    - Kalau design verified → jangan ubah available_quantity
    //          *    - Semua field boleh minus
    //          *    - finished_product_stock dikurangi berdasarkan (ready_qty - shipped_qty)
    //          *      dari delivery_order_items (khusus mode printing)
    //          */
    //         $progressItems = OrderProgressItem::whereHas('progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->get(['id', 'product_id', 'order_item_id', 'quantity']);

    //         // 🔍 cek apakah order ini punya design verified
    //         $hasVerifiedDesign = Design::where('order_id', $order->id)
    //             ->where('status', 'Verified')
    //             ->exists();

    //         foreach ($progressItems as $pi) {
    //             $qty = (float) $pi->quantity;
    //             if ($qty <= 0 || !$pi->product_id) continue;

    //             // 🎯 default decrement = quantity progress item
    //             $decrementFinished = $qty;

    //             // 🔹 kalau mode printing → hitung dari delivery_order_items
    //             if ($order->mode === 'printing') {
    //                 $deliveryItem = DeliveryOrderItem::where(function ($q) use ($pi) {
    //                     $q->where('order_progress_id', $pi->id)
    //                         ->orWhere('order_item_id', $pi->order_item_id);
    //                 })->first(['ready_qty', 'shipped_qty']);

    //                 if ($deliveryItem) {
    //                     // 🔥 langsung kurangi mentah (boleh minus)
    //                     $decrementFinished = ($deliveryItem->ready_qty ?? 0) - ($deliveryItem->shipped_qty ?? 0);
    //                 }
    //             }

    //             $ps = ProductionStock::firstOrCreate(
    //                 ['product_id' => $pi->product_id, 'production_warehouse_id' => $productionWarehouseId],
    //                 [
    //                     'available_quantity'     => 0,
    //                     'finished_product_stock' => 0,
    //                     'pending_waiting_list'   => 0,
    //                     'canceled_product_stock' => 0,
    //                 ]
    //             );

    //             $beforeAvail   = (float) $ps->available_quantity;
    //             $beforeFinish  = (float) $ps->finished_product_stock;
    //             $beforePending = (float) $ps->pending_waiting_list;

    //             if ($hasVerifiedDesign) {
    //                 // ✅ Design verified → JANGAN ubah available_quantity
    //                 $ps->finished_product_stock = $beforeFinish - $decrementFinished;
    //                 $ps->pending_waiting_list   = $beforePending - $qty;
    //             } else {
    //                 // ✅ Belum verified → rollback penuh (boleh minus semua)
    //                 $ps->available_quantity     = $beforeAvail + $qty;
    //                 $ps->finished_product_stock = $beforeFinish - $decrementFinished;
    //                 $ps->pending_waiting_list   = $beforePending - $qty;
    //             }

    //             $ps->save();

    //             Log::info('Force delete rollback stock', [
    //                 'product_id'       => $pi->product_id,
    //                 'rollback_qty'     => $qty,
    //                 'decrement_finish' => $decrementFinished,
    //                 'design_verified'  => $hasVerifiedDesign,
    //                 'avail_before'     => $beforeAvail,
    //                 'avail_after'      => $ps->available_quantity,
    //                 'finish_before'    => $beforeFinish,
    //                 'finish_after'     => $ps->finished_product_stock,
    //                 'pending_before'   => $beforePending,
    //                 'pending_after'    => $ps->pending_waiting_list,
    //             ]);
    //         }

    //         /**
    //          * 3) Handle transaksi akun (sama seperti delete biasa)
    //          *    - Sale Account: balikin balance dan hapus trx
    //          *    - Lainnya: lepas link ke order, tandai notenya
    //          */
    //         $transactions = AccountTransaction::where('order_id', $order->id)->get();
    //         foreach ($transactions as $trx) {
    //             $account = Account::find($trx->account_id);
    //             if (!$account) {
    //                 $trx->delete();
    //                 continue;
    //             }

    //             if ($account->type === 'Sale Account') {
    //                 $account->closing_balance += $trx->debit;
    //                 $account->closing_balance -= $trx->credit;
    //                 $trx->delete();
    //             } else {
    //                 $trx->order_id = null;
    //                 $trx->note = trim(($trx->note ?? '') . ' [Order force-deleted by Owner]');
    //                 $trx->save();
    //             }
    //             $account->save();
    //         }

    //         /**
    //          * 4) Bersihkan relasi lain (lebih tegas daripada delete biasa)
    //          */
    //         // Hapus semua history progress lebih dulu
    //         OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->delete();

    //         // Lalu item progress, progress, item order, edit history
    //         OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->delete();
    //         OrderProgress::where('order_id', $order->id)->delete();
    //         OrderItem::where('order_id', $order->id)->delete();
    //         OrderEditHistory::where('order_id', $order->id)->delete();

    //         /**
    //          * 4.5) 🧹 Hapus semua DELIVERY ORDER dan DELIVERY LIST terkait order ini
    //          */
    //         // Hapus semua Delivery Order dan item/shipments-nya
    //         $deliveryOrders = DeliveryOrder::with(['items', 'shipments'])
    //             ->where('order_id', $order->id)
    //             ->get();

    //         foreach ($deliveryOrders as $do) {
    //             // Hapus semua item di delivery order
    //             if (method_exists($do, 'items')) {
    //                 $do->items()->forceDelete();
    //             }

    //             // Hapus semua shipments kalau ada
    //             if (method_exists($do, 'shipments')) {
    //                 $do->shipments()->forceDelete();
    //             }

    //             $do->forceDelete();

    //             Log::info('Force deleted DeliveryOrder', [
    //                 'delivery_order_id' => $do->id,
    //                 'order_id' => $order->id,
    //             ]);
    //         }

    //         // Hapus semua Delivery List dan item-nya
    //         $deliveryLists = DeliveryList::with(['items'])
    //             ->whereHas('deliveryOrder', fn($q) => $q->where('order_id', $order->id))
    //             ->get();

    //         foreach ($deliveryLists as $dl) {
    //             if (method_exists($dl, 'items')) {
    //                 $dl->items()->forceDelete();
    //             }

    //             $dl->forceDelete();

    //             Log::info('Force deleted DeliveryList', [
    //                 'delivery_list_id' => $dl->id,
    //                 'order_id' => $order->id,
    //             ]);
    //         }

    //         /**
    //          * 5) Tandai laporan keuangan
    //          */
    //         FinancialReport::where('reference_table', 'orders')
    //             ->where('reference_id', $order->id)
    //             ->update(['deleted_at' => now()]);

    //         /**
    //          * 6) Soft delete Order + catat notes
    //          */
    //         $order->delete_notes = $request->input('delete_notes');
    //         $order->deleted_by   = Auth::id();
    //         $order->save();
    //         $order->delete();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Order berhasil dihapus (force delete oleh Owner).');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Force delete owner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return back()->with('error', 'Gagal force delete: ' . $e->getMessage());
    //     }
    // }

    // public function forceDeleteOwner($id, Request $request)
    // {
    //     // ⛔️ batasi hanya Owner
    //     if (!Auth::check() || Auth::user()->role !== 'Owner') {
    //         abort(403, 'Only Owner can force delete.');
    //     }

    //     $request->validate([
    //         'delete_notes' => 'required|string|max:1000',
    //         'inventory_warehouse_id' => 'nullable|integer',
    //         'production_warehouse_id' => 'nullable|integer',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $order = Order::with(['orderItems'])->findOrFail($id);

    //         $hasSaleReturn = SaleReturn::where('sale_order_id', $order->id)->exists();
    //         if ($hasSaleReturn) {
    //             DB::rollBack();
    //             return back()->with('swal_warning', 'Order ini masih memiliki Sale Return. Hapus Sale Return terlebih dahulu sebelum menghapus order ini.');
    //         }

    //         $inventoryWarehouseId  = $request->inventory_warehouse_id  ?? 1;
    //         $productionWarehouseId = $request->production_warehouse_id ?? 2;

    //         // ===================== 1) Rollback INVENTORY STOCK =====================
    //         foreach ($order->orderItems as $item) {
    //             if ($item->satuan === 'satuan' && $item->product_id) {
    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     ['product_id' => $item->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
    //                     ['stock_after_sales' => 0]
    //                 );
    //                 $inventoryStock->increment('stock_after_sales', $item->quantity);
    //             } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
    //                 $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
    //                 if ($bundle) {
    //                     foreach ($bundle->items as $bundleItem) {
    //                         if (!$bundleItem->product_id) continue;
    //                         $inventoryStock = InventoryStock::firstOrCreate(
    //                             ['product_id' => $bundleItem->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
    //                             ['stock_after_sales' => 0]
    //                         );
    //                         $inventoryStock->increment('stock_after_sales', $item->quantity);
    //                     }
    //                 }
    //             }
    //         }

    //         // ===================== 2) Rollback PRODUCTION STOCK =====================
    //         $progressItems = OrderProgressItem::whereHas('progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->get(['id', 'product_id', 'order_item_id', 'quantity']);

    //         $hasVerifiedDesign = Design::where('order_id', $order->id)
    //             ->where('status', 'Verified')
    //             ->exists();

    //         foreach ($progressItems as $pi) {
    //             $qty = (float) $pi->quantity;
    //             if ($qty <= 0 || !$pi->product_id) continue;

    //             $decrementFinished = $qty;

    //             if ($order->mode === 'printing') {
    //                 $deliveryItem = DeliveryOrderItem::where(function ($q) use ($pi) {
    //                     $q->where('order_progress_id', $pi->id)
    //                         ->orWhere('order_item_id', $pi->order_item_id);
    //                 })->first(['ready_qty', 'shipped_qty']);

    //                 if ($deliveryItem) {
    //                     $decrementFinished = ($deliveryItem->ready_qty ?? 0) - ($deliveryItem->shipped_qty ?? 0);
    //                 }
    //             }

    //             $ps = ProductionStock::firstOrCreate(
    //                 ['product_id' => $pi->product_id, 'production_warehouse_id' => $productionWarehouseId],
    //                 [
    //                     'available_quantity'     => 0,
    //                     'finished_product_stock' => 0,
    //                     'pending_waiting_list'   => 0,
    //                     'canceled_product_stock' => 0,
    //                 ]
    //             );

    //             $beforeAvail   = (float) $ps->available_quantity;
    //             $beforeFinish  = (float) $ps->finished_product_stock;
    //             $beforePending = (float) $ps->pending_waiting_list;

    //             if ($hasVerifiedDesign) {
    //                 $ps->finished_product_stock = $beforeFinish - $decrementFinished;
    //                 $ps->pending_waiting_list   = $beforePending - $qty;
    //             } else {
    //                 $ps->available_quantity     = $beforeAvail + $qty;
    //                 $ps->finished_product_stock = $beforeFinish - $decrementFinished;
    //                 $ps->pending_waiting_list   = $beforePending - $qty;
    //             }

    //             $ps->save();
    //         }

    //         // ===================== 3) Transaksi Akun =====================
    //         $transactions = AccountTransaction::where('order_id', $order->id)->get();
    //         foreach ($transactions as $trx) {
    //             $account = Account::find($trx->account_id);
    //             if (!$account) {
    //                 $trx->forceDelete();
    //                 continue;
    //             }

    //             if ($account->type === 'Sale Account') {
    //                 $account->closing_balance += $trx->debit;
    //                 $account->closing_balance -= $trx->credit;
    //                 $trx->forceDelete();
    //             } else {
    //                 $trx->order_id = null;
    //                 $trx->note = trim(($trx->note ?? '') . ' [Order force-deleted by Owner]');
    //                 $trx->save();
    //             }
    //             $account->save();
    //         }

    //         // ===================== 4) Hapus Relasi Lain SECARA PERMANEN =====================
    //         OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
    //             $q->where('order_id', $order->id);
    //         })->forceDelete();

    //         OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->forceDelete();
    //         OrderProgress::where('order_id', $order->id)->forceDelete();
    //         OrderItem::where('order_id', $order->id)->forceDelete();
    //         OrderEditHistory::where('order_id', $order->id)->forceDelete();
    //         Design::withTrashed()->where('order_id', $order->id)->forceDelete();

    //         // ===================== 4.5) Hapus Delivery Order dan Delivery List =====================
    //         $deliveryOrders = DeliveryOrder::with(['items', 'shipments'])
    //             ->where('order_id', $order->id)
    //             ->get();

    //         foreach ($deliveryOrders as $do) {
    //             if (method_exists($do, 'items')) $do->items()->forceDelete();
    //             if (method_exists($do, 'shipments')) $do->shipments()->forceDelete();
    //             $do->forceDelete();
    //         }

    //         $deliveryLists = DeliveryList::with(['items'])
    //             ->whereHas('deliveryOrder', fn($q) => $q->where('order_id', $order->id))
    //             ->get();

    //         foreach ($deliveryLists as $dl) {
    //             if (method_exists($dl, 'items')) $dl->items()->forceDelete();
    //             $dl->forceDelete();
    //         }

    //         // ===================== 5) Hapus Laporan Keuangan =====================
    //         FinancialReport::withTrashed()
    //             ->where('reference_table', 'orders')
    //             ->where('reference_id', $order->id)
    //             ->forceDelete();

    //         // ===================== 6) FORCE DELETE ORDER =====================
    //         $order->delete_notes = $request->input('delete_notes');
    //         $order->deleted_by   = Auth::id();
    //         $order->saveQuietly();
    //         $order->forceDelete();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Order dan seluruh relasi berhasil dihapus permanen (force delete).');
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Force delete owner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return back()->with('error', 'Gagal force delete: ' . $e->getMessage());
    //     }
    // }

    public function forceDeleteOwner($id, Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'Owner') {
            abort(403, 'Only Owner can force delete.');
        }

        $request->validate([
            'delete_notes' => 'required|string|max:1000',
            'inventory_warehouse_id' => 'nullable|integer',
            'production_warehouse_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with(['orderItems'])->findOrFail($id);

            $hasSaleReturn = SaleReturn::where('sale_order_id', $order->id)->exists();
            if ($hasSaleReturn) {
                DB::rollBack();
                return back()->with('swal_warning', 'Order ini masih memiliki Sale Return. Hapus Sale Return terlebih dahulu sebelum menghapus order ini.');
            }

            $inventoryWarehouseId  = $request->inventory_warehouse_id  ?? 1;
            $productionWarehouseId = $request->production_warehouse_id ?? 2;

            /**
             * 1️⃣ Hitung quantity balik ke 0 dan implementasikan ke semua stok
             */
            foreach ($order->orderItems as $item) {
                if (!$item->product_id) continue;

                $qty = (float) $item->quantity;

                // 🔹 INVENTORY STOCK (waktu order stok keluar → sekarang balikin masuk)
                $inventoryStock = InventoryStock::firstOrCreate(
                    ['product_id' => $item->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
                    ['stock_after_sales' => 0]
                );
                $inventoryStock->stock_after_sales += $qty;
                $inventoryStock->save();

                // 🔹 PRODUCTION STOCK
                $ps = ProductionStock::firstOrCreate(
                    ['product_id' => $item->product_id, 'production_warehouse_id' => $productionWarehouseId],
                    [
                        'available_quantity'     => 0,
                        'finished_product_stock' => 0,
                        'pending_waiting_list'   => 0,
                        'canceled_product_stock' => 0,
                    ]
                );

                if ($order->mode === 'printing') {
                    // 🧩 Mode PRINTING — rollback sesuai urutan proses

                    // --- hitung per-produk yang relevan (filter ke product/baris ini) ---
                    $assignedQty = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->whereHas('progressItem', fn($q) => $q->where('product_id', $item->product_id))
                        ->sum('assigned_quantity');

                    // NOTE: sesuaikan kolom jumlah progress history-mu: 'quantity' / 'completed_quantity'
                    $producedQty = \App\Models\OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->whereHas('progressItem', fn($q) => $q->where('product_id', $item->product_id))
                        ->selectRaw('COALESCE(SUM(completed_quantity + defect_quantity + reject_quantity), 0) as total')
                        ->value('total');

                    $shippedQty = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->where('product_id', $item->product_id)
                        ->sum('shipped_qty');

                    $hasVerifiedDesign = \App\Models\Design::where('order_id', $order->id)
                        ->where('status', 'Verified')
                        ->exists();

                    // 1) Verified → pending_waiting_list sempat NAIK qty → rollback: TURUNKAN qty
                    if ($hasVerifiedDesign) {
                        $ps->pending_waiting_list -= $qty;
                    }

                    // 2) Assign → pending & available sempat TURUN assignedQty → rollback: NAIKKAN lagi
                    if ($assignedQty > 0) {
                        $ps->pending_waiting_list += $assignedQty;
                        $ps->available_quantity   += $assignedQty;
                    }

                    // 3) Progress → finished sempat NAIK producedQty → rollback: TURUNKAN producedQty
                    if ($producedQty > 0) {
                        $ps->finished_product_stock -= $producedQty;
                    }

                    // 4) Delivery → finished sempat TURUN shippedQty → rollback: NAIKKAN shippedQty
                    if ($shippedQty > 0) {
                        $ps->finished_product_stock += $shippedQty;
                    }
                } else {
                    // 🧩 Mode POLOSAN → cukup kembalikan stok available
                    $ps->available_quantity += $qty;
                }

                $ps->save();

                Log::info('Force delete rollback efek order', [
                    'mode' => $order->mode,
                    'product_id' => $item->product_id,
                    'order_qty' => $qty,
                    'avail' => $ps->available_quantity,
                    'pending' => $ps->pending_waiting_list,
                    'finish' => $ps->finished_product_stock,
                    'stock_after_sales' => $inventoryStock->stock_after_sales,
                ]);
            }

            /**
             * 2️⃣ Hapus relasi + transaksi (tetap force delete semua)
             */
            AccountTransaction::where('order_id', $order->id)->forceDelete();
            OrderProgressHistory::whereHas('progressItem.progress', fn($q) => $q->where('order_id', $order->id))->forceDelete();
            OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->forceDelete();
            OrderProgress::where('order_id', $order->id)->forceDelete();
            OrderItem::where('order_id', $order->id)->forceDelete();
            OrderEditHistory::where('order_id', $order->id)->forceDelete();
            Design::withTrashed()->where('order_id', $order->id)->forceDelete();
            FinancialReport::withTrashed()->where('reference_table', 'orders')->where('reference_id', $order->id)->forceDelete();

            // 🔹 Delivery Order dan List
            $deliveryOrders = DeliveryOrder::with(['items', 'shipments'])->where('order_id', $order->id)->get();
            foreach ($deliveryOrders as $do) {
                if (method_exists($do, 'items')) $do->items()->forceDelete();
                if (method_exists($do, 'shipments')) $do->shipments()->forceDelete();
                $do->forceDelete();
            }

            $deliveryLists = DeliveryList::with(['items'])
                ->whereHas('deliveryOrder', fn($q) => $q->where('order_id', $order->id))
                ->get();
            foreach ($deliveryLists as $dl) {
                if (method_exists($dl, 'items')) $dl->items()->forceDelete();
                $dl->forceDelete();
            }

            /**
             * 3️⃣ FORCE DELETE ORDER
             */
            $order->delete_notes = $request->input('delete_notes');
            $order->deleted_by   = Auth::id();
            $order->saveQuietly();
            $order->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Order berhasil dihapus total. Semua stok direset ke 0 (efek order hilang sepenuhnya).');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Force delete owner failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal force delete: ' . $e->getMessage());
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
            'payment_proof'        => 'nullable|array',
            'payment_proof.*'      => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'       => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($request->order_id);

            // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
            $groupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type); // Akun pembelian (debit)
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id); // Akun kas/bank (kredit)

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
                'proof' => $proofJson,
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
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MarkAsPaid Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menyimpan pembayaran: ' . $e->getMessage(),
                ], 500);
            }

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

    //         // cari transaksi debit lama (Cash/Bank)
    //         $oldDebit = $transactions->firstWhere('debit', '>', 0);
    //         if (!$oldDebit) {
    //             throw new \Exception("Debit transaction not found in this group");
    //         }

    //         $oldAccount = $oldDebit->account;
    //         $oldAmount  = $oldDebit->debit;

    //         // rollback saldo akun lama
    //         $oldAccount->closing_balance -= $oldAmount;
    //         $oldAccount->save();

    //         // update transaksi debit lama → ganti akun/amount/date/note
    //         $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
    //         $oldDebit->update([
    //             'transaction_date' => $request->transaction_date,
    //             'account_id'       => $cashBankAccount->id,
    //             'debit'            => $request->paid_amount,
    //             'note'             => $request->note ?? '',
    //         ]);

    //         // update saldo akun baru
    //         $cashBankAccount->closing_balance += $request->paid_amount;
    //         $cashBankAccount->save();

    //         // update juga tanggal/note untuk baris credit Sale biar sinkron
    //         $saleTrx = $transactions->firstWhere('credit', '>', 0);
    //         if ($saleTrx) {
    //             $saleTrx->update([
    //                 'transaction_date' => $request->transaction_date,
    //                 'note'             => $request->note ?? '',
    //             ]);
    //         }

    //         // hitung ulang payment status order (sum debit)
    //         $totalPaid = AccountTransaction::where('order_id', $order->id)
    //             ->where('debit', '>', 0)
    //             ->sum('debit');

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
            'paid_amount'           => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'payment_proof'        => 'nullable|array',
            'payment_proof.*'      => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'       => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Payment not found");
            }

            $orderId = $transactions->first()->order_id;
            $order   = Order::findOrFail($orderId);

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            // Ambil proof lama dulu biar gak hilang
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

            // 🔹 Kalau gak ada file baru → tetap pakai proof lama
            if (empty($uploadedProofs)) {
                // Update note lama kalau dikirim ulang
                foreach ($oldProofs as $index => &$proof) {
                    $proof['note'] = $notes[$index] ?? ($proof['note'] ?? '');
                }
                $uploadedProofs = $oldProofs;
            }

            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // 🔹 Jika paid_amount = 0, hapus semua transaksi dalam group dan ubah order jadi Unpaid
            if ($request->paid_amount == 0) {
                foreach ($transactions as $trx) {
                    $account = $trx->account;
                    if ($trx->debit > 0) {
                        // rollback saldo debit account
                        $account->decrement('closing_balance', $trx->debit);
                    } elseif ($trx->credit > 0) {
                        // rollback saldo credit account
                        $account->increment('closing_balance', $trx->credit);
                    }
                    $trx->delete();
                }

                // update order status jadi unpaid
                $order->update([
                    'paid_amount' => 0,
                    'remaining_amount' => $order->grand_total,
                    'payment_status' => 'Unpaid',
                ]);

                DB::commit();
                return redirect()->back()->with('success', 'Payment dihapus dan status order jadi Unpaid.');
            }

            // 🔹 Jika paid_amount > 0 → jalankan update seperti biasa
            $oldDebit = $transactions->firstWhere('debit', '>', 0);
            if (!$oldDebit) {
                throw new \Exception("Debit transaction not found in this group");
            }

            $oldAccount = $oldDebit->account;
            $oldAmount  = $oldDebit->debit;

            // rollback saldo akun lama
            $oldAccount->decrement('closing_balance', $oldAmount);

            // update transaksi debit lama → ganti akun/amount/date/note
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldDebit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => $request->paid_amount,
                'note'             => $request->note ?? '',
                'proof'   => $proofJson,
            ]);

            // update saldo akun baru
            $cashBankAccount->increment('closing_balance', $request->paid_amount);

            // update juga tanggal/note untuk baris credit Sale biar sinkron
            // $saleTrx = $transactions->firstWhere('credit', '>', 0);
            // if ($saleTrx) {
            //     $saleTrx->update([
            //         'transaction_date' => $request->transaction_date,
            //         'note'             => $request->note ?? '',
            //     ]);
            // }

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
            $order = Order::onlyTrashed()
                ->with([
                    'orderItems' => fn($q) => $q->withTrashed(),
                ])
                ->findOrFail($id);

            // ======================================================
            // 🔒 CEK: Jangan restore kalau sudah ada Stock In dari Canceled Product
            // ======================================================
            $hasStockIn = Inventory::whereNotNull('canceled_product_id')
                ->whereHas('items', function ($q) use ($order) {
                    $q->whereIn('order_item_id', $order->orderItems->pluck('id'))
                        ->where('stock_in', '>', 0);
                })
                ->exists();

            if ($hasStockIn) {
                DB::rollBack();
                return back()->with('error', 'Tidak bisa restore! Order ini sudah pernah masuk ke Warehouse (Stock In dari Canceled Product).');
            }

            // ======================================================
            // ✅ 1️⃣ Restore Order (relasi ikut lewat event restoring di model)
            // ======================================================
            $order->restore();

            $warehouseId = 1; // default warehouse
            $mode = $order->mode;

            // ======================================================
            // ✅ 2️⃣ Balikkan efek stok INVENTORY (kebalikan dari delete)
            // ======================================================
            foreach ($order->orderItems as $item) {
                if ($item->satuan === 'satuan' && $item->product_id) {
                    $inventoryStock = InventoryStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
                        ['stock_after_sales' => 0]
                    );
                    // Saat delete: increment → saat restore: decrement
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

            // ======================================================
            // ✅ 3️⃣ Balikkan efek stok PRODUKSI berdasarkan MODE
            // ======================================================

            if ($mode === 'printing') {
                // Saat delete printing → pending_waiting_list turun
                // Saat restore → naikkan kembali pending_waiting_list
                $progressItems = OrderProgressItem::withTrashed()
                    ->whereHas('progress', fn($q) => $q->withTrashed()->where('order_id', $order->id))
                    ->get(['product_id', 'quantity']);

                // 🔍 Cek apakah design verified
                $hasVerifiedDesign = Design::where('order_id', $order->id)
                    ->where('status', 'Verified')
                    ->exists();

                foreach ($progressItems as $progressItem) {
                    if (!$progressItem->product_id || $progressItem->quantity <= 0) continue;

                    $productionStock = ProductionStock::firstOrCreate(
                        ['product_id' => $progressItem->product_id],
                        [
                            'available_quantity'     => 0,
                            'finished_product_stock' => 0,
                            'pending_waiting_list'   => 0,
                            'canceled_product_stock' => 0,
                        ]
                    );

                    // 🔹 Kalau design verified → naikkan pending_waiting_list
                    if ($hasVerifiedDesign) {
                        $productionStock->increment('pending_waiting_list', $progressItem->quantity);
                    }

                    // ❌ Jangan ubah available_quantity sama sekali
                    $productionStock->save();
                }
            } elseif ($mode === 'polosan') {
                // Saat delete polosan → available_quantity naik
                // Saat restore → turunkan kembali
                foreach ($order->orderItems as $item) {
                    if ($item->satuan === 'satuan' && $item->product_id) {
                        $productionStock = ProductionStock::firstOrCreate(
                            ['product_id' => $item->product_id],
                            ['available_quantity' => 0]
                        );
                        $productionStock->decrement('available_quantity', $item->quantity);
                    } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
                        $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                if (!$bundleItem->product_id) continue;
                                $productionStock = ProductionStock::firstOrCreate(
                                    ['product_id' => $bundleItem->product_id],
                                    ['available_quantity' => 0]
                                );
                                $productionStock->decrement('available_quantity', $item->quantity);
                            }
                        }
                    }
                }
            }

            // ======================================================
            // ✅ 4️⃣ Restore transaksi akun (hapus tag [Order deleted])
            // ======================================================
            $transactions = AccountTransaction::where('note', 'like', '%[Order deleted]%')
                ->whereNull('order_id')
                ->get();

            foreach ($transactions as $trx) {
                $trx->note = trim(str_replace('[Order deleted]', '', $trx->note));
                $trx->order_id = $order->id;
                $trx->save();
            }

            // ======================================================
            // ✅ 5️⃣ Restore laporan keuangan
            // ======================================================
            FinancialReport::withTrashed()
                ->where('reference_table', 'orders')
                ->where('reference_id', $order->id)
                ->update(['deleted_at' => null]);

            DB::commit();
            return back()->with('success', 'Order berhasil direstore!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Restore order gagal', [
                'order_id' => $id,
                'error'    => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal mengembalikan order: ' . $e->getMessage());
        }
    }
}
