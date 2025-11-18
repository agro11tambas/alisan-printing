<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Products;
use App\Models\Customers;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAddresses;
use Carbon\Carbon;
use App\Models\Discount;
use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Str;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Bank;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\FinancialReport;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\OrderItemComponent;
use App\Models\OrderProgress;
use App\Models\OrderProgressHistory;
use App\Models\OrderProgressItem;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Services\InvoiceNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SaleOrderController extends Controller
{
    public function getSaleOrder()
    {
        $order_number = Order::first();
        $transactionTypes = Account::where('name', 'Order')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.sales.sale-orders.sale-orders', compact('order_number', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function dataSaleOrder(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $orders = Order::with(['customer', 'customerAddress'])
            ->where('status', 'sale order')
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

        // 🔹 Filter pencarian
        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $orders->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', $request->search_keyword . '%');
                });
            } else {
                $orders->where('order_number', 'like', $request->search_keyword . '%');
            }
        }

        // 🔹 Hindari query count dua kali
        $totalQuery = clone $orders;
        $totalData = $totalQuery->count();

        // 🔹 Ambil data sesuai offset dan limit
        $data = $orders->skip($start)->take($length)->get();

        // 🔹 Return format JSON ringan (lazy load style)
        return response()->json([
            'data' => $data->map(function ($order) {
                $date = Carbon::parse($order->order_date)->format('d M y H:i');
                $orderNumber = '
                <div>
                    <div>' . e($order->order_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                $paymentStatus = strtolower($order->payment_status);
                $paymentBadge = match ($paymentStatus) {
                    'paid' => '<div class="badge bg-soft-success text-success">' . e($order->payment_status) . '</div>',
                    'unpaid' => '<div class="badge bg-soft-danger text-danger">' . e($order->payment_status) . '</div>',
                    default => '<div class="badge bg-soft-warning text-warning">' . e($order->payment_status) . '</div>',
                };

                $mode = strtolower($order->mode ?? 'printing');
                $modeBadgeClass = match ($mode) {
                    'printing' => 'bg-soft-success text-success',
                    'polosan'    => 'bg-soft-primary text-primary',
                    default  => 'bg-soft-dark text-dark',
                };
                $modeBadge = '<div class="badge ' . $modeBadgeClass . '">' . ucfirst($mode) . '</div>';

                // 🔹 Produk (termasuk bundle & soft deleted)
                $items = $order->orderItems()
                    ->with([
                        'product' => fn($q) => $q->withTrashed(),
                        'productBundle.items.product'
                    ])
                    ->get()
                    ->map(function ($item) {
                        if ($item->product) {
                            $name = $item->product->name;
                            $sku  = $item->product->sku;
                        } elseif ($item->productBundle) {
                            $bundleNames = $item->productBundle->items->map(function ($bundleItem) {
                                return $bundleItem->product->name ?? '-';
                            })->implode(' + ');
                            $name = $bundleNames ?: '-';
                            $sku  = $item->productBundle->sku ?? '-';
                        } else {
                            $name = '-';
                            $sku  = '-';
                        }

                        return [
                            'name'  => e($name),
                            'sku'   => e($sku),
                            'qty'   => number_format($item->quantity, 0, ',', '.'),
                            'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        ];
                    });

                $status = strtolower($order->status);
                $badgeClass = match ($status) {
                    'sale order' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };
                $statusBadge = '<div class="badge ' . $badgeClass . '">' . ucfirst($status) . '</div>';

                return [
                    'id' => $order->id,
                    'order_number' => $orderNumber,
                    'order_date' => $date,
                    'customer' => '
                        <div style="white-space: normal; word-break: break-word; max-width:180px;">
                            <div class="fw-semibold">' . e($order->customerAddress->business_name ?? '-') . '</div>
                            <small class="text-muted">' . e($order->customer->name ?? '-') . '</small>
                        </div>
                    ',
                    'total_amount' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'discount' => '<span class="text-warning">Rp ' . number_format($order->discount, 0, ',', '.') . '</span>',
                    'grand_total' => '<span class="text-primary">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>',
                    'payment_status' => $paymentBadge,
                    'products' => $items,
                    'status' => $statusBadge,
                    'notes' => e($order->notes ?? '-'),
                    'created_at' => $date,
                    'mode' => $modeBadge,
                    'action' => view('erp.pages.sales.sale-orders.partials.action-button', compact('order'))->render(),
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

        // Produk bundle + relasi produk di dalamnya
        $productBundles = ProductBundle::with([
            'items.product.categories.discounts',
            'items.product.discounts'
        ])->orderBy('name', 'asc')->get();

        // Kalau belum ada relasi diskon di bundle, beri array kosong
        $productBundles->map(function ($bundle) {
            $bundle->discounts = [];
            return $bundle;
        });

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

        // 🔹 JSON untuk produk bundle
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

            // 🔹 Gabungkan nama produk di dalam bundle
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

        // 🔹 Data pendukung lain
        $customers = Customers::with('addresses')->get();
        $discount = Discount::first();
        $transactionTypes = Account::where('name', 'Sale')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.sales.sale-orders.create-order', compact(
            'products',
            'customers',
            'discount',
            'cashAccounts',
            'bankAccounts',
            'transactionTypes',
            'productBundles',
            'productsJson',
            'productBundlesJson'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_date'            => 'required|date_format:Y-m-d\TH:i',
            // 'customers'             => 'required|array',
            // 'customers.*'           => 'exists:customers,id',
            // 'addresses'             => 'required|array',
            // 'addresses.*'           => 'exists:customer_addresses,id',
            'customer_id'          => 'required|exists:customers,id',
            'customer_address_id'  => 'required|exists:customer_addresses,id',
            'notes'                 => 'nullable|string',
            'product'               => 'required|array',
            'product.*'             => 'required',
            'product_type'          => 'required|array',
            'product_type.*'        => 'in:satuan,bundle',
            'qty'                   => 'required|array',
            'qty.*'                 => 'numeric|min:1',
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
            'mode'                  => 'required|in:printing,polosan',
        ]);

        DB::beginTransaction();

        try {
            $paidAmount = $request->paid_amount ?? 0;
            $remainingAmount = $request->total_amount - $paidAmount;
            $status = 'Sale Order';

            $orderDate = Carbon::parse($request->order_date);

            $orderNumber = InvoiceNumberService::generate('SO', $orderDate);

            $addressModel = CustomerAddresses::find($request->customer_address_id);

            // ================== BUAT ORDER ==================
            $order = Order::create([
                'customer_id'      => $request->customer_id,
                'customer_address_id' => $request->customer_address_id,
                'order_number'     => $orderNumber,
                'order_date'       => $request->order_date,
                'status'           => $status,
                'payment_status'   => ($paidAmount <= 0) ? 'Unpaid' : (($paidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid'),
                'paid_amount'      => $paidAmount,
                'business_name'    => $addressModel?->business_name,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
                'mode'              => $request->mode,
                'discount_active' => (int) $request->input('discount_active_hidden', 1),
            ]);

            // ================== BUAT ORDER ITEMS ==================
            foreach ($request->product as $index => $productInputId) {
                $type = strtolower($request->product_type[$index]);
                $qty  = (float) $request->qty[$index];

                // ======================================================
                // PRODUK SATUAN
                // ======================================================
                if ($type === 'satuan') {
                    $product = Products::findOrFail($productInputId);

                    // Ambil avg cost dari tabel inventory_stocks
                    $inventoryStock = \App\Models\InventoryStock::where('product_id', $product->id)->first();
                    $avgCost = $product?->avg_cost ?? 0;
                    $fixedCost = $product->fixed_cost ?? 0;

                    // Buat order item utama
                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => $product->id,
                        'product_bundle_id'    => null,
                        'product_name'         => $product->name,
                        'satuan'               => 'satuan',
                        'quantity'             => $qty,
                        'completed_quantity'   => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    // Simpan juga ke order_item_components (biar struktur seragam)
                    OrderItemComponent::create([
                        'order_item_id'    => $orderItem->id,
                        'product_id'       => $product->id,
                        'qty'         => $qty,
                        'avg_cost_at_sale' => $avgCost,
                        'fixed_cost_at_sale' => $fixedCost,
                        'total_cost'       => $avgCost * $qty,
                        'total_fixed_cost' => $fixedCost * $qty,
                    ]);
                }

                // ======================================================
                // PRODUK BUNDLE
                // ======================================================
                elseif ($type === 'bundle') {
                    $bundle = ProductBundle::with('items.product')->findOrFail($productInputId);

                    $bundleProductNames = $bundle->items->map(function ($bundleItem) {
                        return $bundleItem->product->name ?? '-';
                    })->implode(' + ');

                    // Hitung total avg cost bundle (sum dari komponen real di inventory_stocks)
                    $totalAvgCost = 0;
                    $totalFixedCost = 0;
                    foreach ($bundle->items as $bundleItem) {
                        $component = $bundleItem->product;
                        $component = \App\Models\InventoryStock::where('product_id', $component->id)->first();
                        $componentAvgCost = $component?->avg_cost ?? 0;
                        $componentFixedCost = $component?->fixed_cost ?? 0;
                        $totalAvgCost += ($componentAvgCost * $bundleItem->quantity);
                        $totalFixedCost += ($componentFixedCost * $bundleItem->quantity);
                    }

                    // Buat order_item utama untuk bundle
                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => null,
                        'product_bundle_id'    => $bundle->id,
                        'product_name'         => $bundleProductNames ?: $bundle->name,
                        'satuan'               => 'bundle',
                        'quantity'             => $qty,
                        'completed_quantity'   => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    // Simpan semua komponen bundle ke order_item_components
                    foreach ($bundle->items as $bundleItem) {
                        $component = $bundleItem->product;
                        $componentStock = \App\Models\InventoryStock::where('product_id', $component->id)->first();
                        $componentAvgCost = $componentStock?->avg_cost ?? 0;

                        OrderItemComponent::create([
                            'order_item_id'    => $orderItem->id,
                            'product_id'       => $component->id,
                            'qty'              => $qty, // per bundle × jumlah bundle
                            'avg_cost_at_sale' => $componentAvgCost,
                            'fixed_cost_at_sale' => $component->fixed_cost ?? 0,
                            'total_cost'       => $componentAvgCost * $qty,
                            'total_fixed_cost' => ($component->fixed_cost ?? 0) * $qty,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect("/erp/sales/sale-orders")->with('success', 'Order berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
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

        // 🔹 Ambil data produk dan bundle
        $productBundles = ProductBundle::with([
            'items.product.categories.discounts',
            'items.product.discounts'
        ])->orderBy('name', 'asc')->get();

        // Kalau belum ada relasi diskon di bundle, beri array kosong
        $productBundles->map(function ($bundle) {
            $bundle->discounts = [];
            return $bundle;
        });

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

        // 🔹 JSON untuk produk bundle (gabungkan nama produk)
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

            // 🔹 Gabungkan nama-nama produk di dalam bundle
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

        return view('erp.pages.sales.sale-orders.edit-order', compact(
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
            'order_date'              => 'required|date_format:Y-m-d\TH:i',
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'notes'                   => 'nullable|string',
            'product'                 => 'required|array',
            'product.*'               => 'required',
            'product_type'            => 'required|array',
            'product_type.*'          => 'in:satuan,bundle',
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
            'notes'                   => 'nullable|string',
            'mode'                    => 'required|in:printing,polosan',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with('orderItems.components')->findOrFail($id);

            // === BALIKKAN STOK LAMA ===
            foreach ($order->orderItems as $oldItem) {
                foreach ($oldItem->components as $component) {
                    $product = Products::find($component->product_id);
                    if ($product) {
                        $product->increment('stock_after_sales', $component->qty);
                    }
                }
            }

            // === UPDATE HEADER ===
            $paidAmount      = $request->paid_amount ?? 0;
            $remainingAmount = $request->total_amount - $paidAmount;
            $status          = 'Sale Order';
            $paymentStatus   = ($paidAmount <= 0)
                ? 'Unpaid'
                : (($paidAmount < $request->total_amount) ? 'Partially Paid' : 'Paid');

            $addressModel = CustomerAddresses::find($request->customer_address_id);

            $order->update([
                'customer_id'      => $request->customer_id,
                'customer_address_id' => $request->customer_address_id,
                'order_date'       => $request->order_date,
                'status'           => $status,
                'payment_status'   => $paymentStatus,
                'paid_amount'      => $paidAmount,
                'business_name'    => $addressModel?->business_name,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
                'discount_active'  => (int) $request->input('discount_active_hidden', 1),
                'mode'               => $request->mode,
            ]);

            // === UPDATE / INSERT ORDER ITEMS BARU ===
            $processedItemIds = [];

            foreach ($request->product as $index => $productInputId) {
                [$type, $pid] = explode('_', $productInputId);
                $type = strtolower($type);
                $qty  = (float) $request->qty[$index];

                if ($type === 'satuan') {
                    $product = Products::findOrFail($pid);
                    $stock = \App\Models\InventoryStock::where('product_id', $pid)->first();
                    $avgCost = $product?->avg_cost ?? 0;
                    $fixedCost = $product?->fixed_cost ?? 0;

                    // cari order item yang sudah ada
                    $orderItem = $order->orderItems()
                        ->where('product_id', $pid)
                        ->whereNull('product_bundle_id')
                        ->first();

                    if ($orderItem) {
                        // update
                        $orderItem->update([
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                            'deleted_at'           => null,
                        ]);

                        // update component-nya
                        $component = $orderItem->components()->first();
                        if ($component) {
                            $component->update([
                                'qty'              => $qty,
                                'avg_cost_at_sale' => $avgCost,
                                'fixed_cost_at_sale' => $fixedCost,
                                'total_cost'       => $avgCost * $qty,
                                'total_fixed_cost' => $fixedCost * $qty,
                            ]);
                        }
                    } else {
                        // buat baru kalau belum ada
                        $orderItem = OrderItem::create([
                            'order_id'             => $order->id,
                            'product_id'           => $product->id,
                            'product_bundle_id'    => null,
                            'product_name'         => $product->name,
                            'satuan'               => 'satuan',
                            'quantity'             => $qty,
                            'completed_quantity'   => 0,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        $orderItem->components()->create([
                            'product_id'       => $product->id,
                            'qty'              => $qty,
                            'avg_cost_at_sale' => $avgCost,
                            'fixed_cost_at_sale' => $fixedCost,
                            'total_cost'       => $avgCost * $qty,
                            'total_fixed_cost' => $fixedCost * $qty,
                        ]);
                    }

                    // kurangi stok
                    $product->decrement('stock_after_sales', $qty);
                    $processedItemIds[] = $orderItem->id;
                }

                // === PRODUK BUNDLE ===
                elseif ($type === 'bundle') {
                    $bundle = ProductBundle::with('items.product')->findOrFail($pid);

                    // cari order item bundle yang sudah ada
                    $orderItem = $order->orderItems()
                        ->where('product_bundle_id', $bundle->id)
                        ->first();

                    if ($orderItem) {
                        // update data bundle utama
                        $orderItem->update([
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                            'deleted_at'           => null,
                        ]);

                        // === UPDATE / BUAT COMPONENT BARU (seperti satuan) ===
                        foreach ($bundle->items as $bundleItem) {
                            $component = $bundleItem->product;
                            if (!$component) continue;

                            $stock = \App\Models\InventoryStock::where('product_id', $component->id)->first();
                            $avgCost = $component?->avg_cost ?? 0;
                            $fixedCost = $component?->fixed_cost ?? 0;
                            $totalQty = $qty;

                            // cari component yang sudah ada
                            $componentRow = $orderItem->components()
                                ->where('product_id', $component->id)
                                ->first();

                            if ($componentRow) {
                                $componentRow->update([
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'fixed_cost_at_sale' => $fixedCost,
                                    'total_cost'       => $avgCost * $totalQty,
                                    'total_fixed_cost' => $fixedCost * $totalQty,
                                ]);
                            } else {
                                $orderItem->components()->create([
                                    'product_id'       => $component->id,
                                    'qty'              => $totalQty,
                                    'avg_cost_at_sale' => $avgCost,
                                    'fixed_cost_at_sale' => $fixedCost,
                                    'total_cost'       => $avgCost * $totalQty,
                                    'total_fixed_cost' => $fixedCost * $totalQty,
                                ]);
                            }

                            $component->decrement('stock_after_sales', $totalQty);
                        }
                    } else {
                        // === BUAT BARU KALAU BUNDLE BELUM ADA ===
                        $orderItem = OrderItem::create([
                            'order_id'             => $order->id,
                            'product_id'           => null,
                            'product_bundle_id'    => $bundle->id,
                            'product_name'         => $bundle->name,
                            'satuan'               => 'bundle',
                            'quantity'             => $qty,
                            'completed_quantity'   => 0,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        // BUAT COMPONENT BARU
                        foreach ($bundle->items as $bundleItem) {
                            $component = $bundleItem->product;
                            if (!$component) continue;

                            $stock = \App\Models\InventoryStock::where('product_id', $component->id)->first();
                            $avgCost = $component?->avg_cost ?? 0;
                            $fixedCost = $component?->fixed_cost ?? 0;
                            $totalQty = $qty;

                            $orderItem->components()->create([
                                'product_id'       => $component->id,
                                'qty'              => $totalQty,
                                'avg_cost_at_sale' => $avgCost,
                                'fixed_cost_at_sale' => $fixedCost,
                                'total_cost'       => $avgCost * $totalQty,
                                'total_fixed_cost' => $fixedCost * $totalQty,
                            ]);

                            $component->decrement('stock_after_sales', $totalQty);
                        }
                    }

                    $processedItemIds[] = $orderItem->id;
                }
            }

            // === HAPUS ITEM YANG TIDAK LAGI ADA DI INPUT ===
            $order->orderItems()
                ->whereNotIn('id', $processedItemIds)
                ->delete();

            DB::commit();
            return redirect("/erp/sales/sale-orders")->with('success', 'Order berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->findOrFail($id);

            // Hard delete semua order items
            OrderItem::where('order_id', $order->id)->forceDelete();

            // Hard delete transaksi akun kalau ada
            if ($order->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $order->transaction_group_id)->forceDelete();
            }

            // Hard delete order
            $order->forceDelete();

            DB::commit();

            return redirect()->back()->with('success', 'Order berhasil dihapus permanen.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }

    public function getSaleOrderDetail($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return view('erp.pages.sales.sale-orders.detail-order', compact('order'));
    }

    public function markAsSaleList($id, Request $request)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $rules = [
            'order_number' => 'required',
            'order_date' => 'required|date_format:Y-m-d\TH:i',
            'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date' => 'nullable|date',
            // 'payment_status' => 'required|in:Paid,Unpaid,Partially Paid',
            'notes' => 'nullable|string',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
        ];

        if ($request->payment_status !== 'Unpaid') {
            $rules = array_merge($rules, [
                'paid_amount' => 'nullable|numeric|min:0',
                'cash_bank_account_id' => 'nullable|exists:accounts,id',
                'transaction_date' => 'nullable|date',
                'transaction_type' => 'nullable|exists:accounts,id',
            ]);
        }

        $request->validate($rules);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($id);

            $status = 'Sale List';

            $orderDate = Carbon::parse($request->order_date);

            if (str_starts_with($order->order_number, 'SO/')) {
                $newInvoiceNumber = InvoiceNumberService::generate('INV', $orderDate);
                $order->order_number = $newInvoiceNumber;
                $order->save(); // simpan langsung di sini
            }

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
                    $dueDate = null;
            }

            $paidAmount = $request->paid_amount ?? 0;
            $totalAmount = $order->grand_total;
            $remainingAmount = $totalAmount - $paidAmount;

            if ($paidAmount <= 0) {
                $paymentStatus = 'Unpaid';
            } elseif ($paidAmount < $totalAmount) {
                $paymentStatus = 'Partially Paid';
            } else {
                $paymentStatus = 'Paid';
            }

            $groupId = Str::uuid();
            $saleAccount = Account::findOrFail($request->transaction_type);

            AccountTransaction::create([
                'order_id' => $order->id,
                'order_number' => $request->order_number,
                'transaction_date' => $request->transaction_date,
                'account_id' => $saleAccount->id,
                'debit' => 0,
                'credit' => $totalAmount,
                'note' => $request->note ?? '',
                'particular' => '',
                'transaction_group_id' => $groupId,
                'verified' => 1,
            ]);

            $saleAccount->closing_balance += $totalAmount;
            $saleAccount->save();

            if ($paidAmount > 0) {
                $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

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
                    'verified' => 1,
                ]);

                $cashBankAccount->closing_balance += $request->paid_amount;
                $cashBankAccount->save();

                $order->transaction_group_id = $groupId;
                $order->payment_method = $saleAccount->type;
                $order->save();
            }

            $order->update([
                'status' => $status,
                'order_date' => $request->order_date,
                'due_date' => $dueDate,
                'paid_amount' => $request->paid_amount ?? 0,
                'remaining_amount' => $remainingAmount,
                'payment_status' => $paymentStatus,
                'transaction_type' => $request->transaction_type ?? null,
                // 'notes' => $request->notes,
                'payment_method' => 'Sale Account',
            ]);

            $warehouseId = $request->inventory_warehouse_id ?? 1;

            foreach ($order->orderItems as $item) {
                if ($item->product_id) {
                    // produk satuan
                    $product = Products::find($item->product_id);
                    if ($product) {
                        $warehouseId = $request->inventory_warehouse_id ?? 1;

                        $inventoryStock = InventoryStock::firstOrCreate(
                            [
                                'product_id' => $item->product_id,
                                'inventory_warehouse_id' => $warehouseId,
                            ],
                            [
                                'stock_after_sales' => 0,
                            ]
                        );

                        $inventoryStock->decrement('stock_after_sales', $item->quantity);
                    }
                } elseif ($item->product_bundle_id) {
                    // produk bundle
                    $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            $bundleProduct = $bundleItem->product;
                            if ($bundleProduct) {
                                $deductQty = $item->quantity;

                                $inventoryStock = InventoryStock::firstOrCreate(
                                    [
                                        'product_id' => $bundleProduct->id,
                                        'inventory_warehouse_id' => $warehouseId,
                                    ],
                                    [
                                        'stock_after_sales' => 0,
                                    ]
                                );

                                $inventoryStock->decrement('stock_after_sales', $deductQty);
                            }
                        }
                    }
                }
            }

            // ================== HANDLE MODE PRINTING ATAU POLOSAN ==================
            if ($order->mode === 'printing') {
                // ================== BUAT DESIGN DAN DESIGN ITEMS ==================
                $designNumber = $order->order_number;

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
                $deliveryNumber = $order->order_number;
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

                        // Kurangi stok produksi & stok inventory
                        $productionStock = \App\Models\ProductionStock::where('product_id', $orderItem->product_id)->first();
                        if ($productionStock) {
                            $productionStock->decrement('available_quantity', $orderItem->quantity);
                        }

                        $inventoryStock = \App\Models\InventoryStock::where('product_id', $orderItem->product_id)
                            ->where('inventory_warehouse_id', $warehouseId)
                            ->first();
                        if ($inventoryStock) {
                            $inventoryStock->decrement('stock_after_sales', $orderItem->quantity);
                        }
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

                            // Kurangi stok produksi & stok inventory
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

            // ================== CATAT FINANCIAL REPORT ==================
            try {
                $financialReport = FinancialReport::where('transaction_type', 'sale')
                    ->where('reference_id', $order->id)
                    ->where('reference_table', 'orders')
                    ->first();

                $totalRevenue = $order->grand_total;
                $totalCogs = 0;
                $totalFixedCogs = 0;

                // 🔹 Hitung total COGS dari produk dan bundle
                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {
                        // Produk satuan
                        $product = $orderItem->product;
                        $avgCost = $product->avg_cost ?? 0;
                        $fixedCost = $product->fixed_cost ?? 0;
                        $totalCogs += $avgCost * $orderItem->quantity;
                        $totalFixedCogs += $fixedCost * $orderItem->quantity;
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
                        $totalFixedCogs  += $bundleFixedCost * $orderItem->quantity;
                    }
                }

                $grossProfit = $totalRevenue - $totalCogs;
                $grossProfitAtFixedCost = $totalRevenue - $totalFixedCogs;

                if ($financialReport) {
                    // Update jika sudah ada record lama
                    $financialReport->update([
                        'date'         => $order->order_date,
                        'revenue'      => $totalRevenue,
                        'cogs'         => $totalCogs,
                        'cogs_fixed_cost'   => $totalFixedCogs,
                        'gross_profit' => $grossProfit,
                        'gross_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'expense'      => 0,
                        'net_profit'   => $grossProfit,
                        'net_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'notes'        => 'Auto-updated from Mark as Sale List',
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
                        'cogs_fixed_cost'  => $totalFixedCogs,
                        'gross_profit'     => $grossProfit,
                        'gross_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'expense'          => 0,
                        'net_profit'       => $grossProfit,
                        'net_profit_at_fixed_cost' => $grossProfitAtFixedCost,
                        'notes'            => 'Auto-generated from Mark as Sale List',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Gagal mencatat laporan keuangan untuk Order ID ' . $order->id . ': ' . $e->getMessage());
            }

            DB::commit();
            return redirect('/erp/sales/sale-list')->with('success', 'Sale Order marked as Sale List.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui status penjualan: ' . $e->getMessage());
        }
    }

    public function getInvoice($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $invoice = Invoice::with('termAndConditions')->first();
        return view('erp.pages.sales.invoice.index', compact('order', 'invoice'));
    }
}
