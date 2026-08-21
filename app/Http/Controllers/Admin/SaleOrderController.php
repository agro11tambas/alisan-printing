<?php

namespace App\Http\Controllers\Admin;


use App\Services\ErpCatalogPayload;
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
use App\Models\PriceMode;
use App\Services\InvoiceNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SaleOrderController extends Controller
{
    private function renderOrderItemModes(Order $order): string
    {
        $modes = $order->orderItems
            ->pluck('mode')
            ->filter()
            ->map(fn ($mode) => strtolower(trim($mode)))
            ->unique()
            ->values();

        if ($modes->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }

        return '<div class="d-flex flex-column align-items-center gap-1">' .
            $modes->map(function ($mode) {
                $badgeClass = match ($mode) {
                    'printing' => 'bg-soft-success text-success',
                    'polosan' => 'bg-soft-danger text-danger',
                    'sablon' => 'bg-soft-primary text-primary',
                    default => 'bg-soft-dark text-dark',
                };

                return '<span class="badge ' . $badgeClass . '">' . e(ucfirst($mode)) . '</span>';
            })->implode('') .
            '</div>';
    }

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
        $user = Auth::user();

        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $orders = Order::with([
            'customer',
            'customerAddress',
            'customerAccount',
            'orderItems.product',
            'orderItems.productBundle.items.product',
        ])
            ->where('status', 'sale order')
            ->orderBy('order_date', 'desc');

        if (in_array($user->role, ['Sales'])) {
            $orders->where('user_id', $user->id);
        }

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
                // $keyword = '%' . $request->search_keyword . '%';
                $keyword = '%' . $request->search_keyword . '%';

                $orders->where(function ($q) use ($keyword) {
                    // Cari berdasarkan nama customer
                    $q->whereHas('customer', function ($query) use ($keyword) {
                        $query->where('name', 'like', $keyword);
                    });

                    // Cari berdasarkan business_name
                    $q->orWhereHas('customerAddress', function ($query) use ($keyword) {
                        $query->where('business_name', 'like', $keyword);
                    });
                });
            } else {
                $orders->where('order_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        // 🔹 Ambil data sesuai offset dan limit
        [$data, $hasMore] = $this->lazyLoadPage($orders, $start, $length);

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

                $modeBadge = $this->renderOrderItemModes($order);

                // 🔹 Produk (termasuk bundle & soft deleted)
                $items = $order->orderItems
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

                $businessName = $order->customerAddress->business_name ?? null;
                $customer = $order->customer->name ?? null;
                $customerAccount = $order->customerAccount->name ?? null;
                $customerAccountNumber = $order->customerAccount->whatsapp_number ?? null;

                return [
                    'id' => $order->id,
                    'order_number' => $orderNumber,
                    'order_date' => $date,
                    'customer' => '
                        <div style="white-space: normal; word-break: break-word; max-width:180px;">

                            <div class="d-flex align-items-center fw-semibold">                            
                                ' . $customer . '                                
                            </div>

                        <div>
                            <small class="text-muted">' . $customerAccount . ' - ' . $customerAccountNumber . '</small>
                        </div>
                        <small class="text-muted">' . $businessName . '</small>

                        </div>
                    ',
                    'customer_mobile' => '
                        <div style="white-space: normal; word-break: break-word; max-width:180px;">

                            <div class="d-flex fw-semibold">                               
                                <div>
                                    <small class="text-muted">' . $customer . '</small>
                                    <small class="text-muted">' . $customerAccount . ' - ' . $customerAccountNumber . '</small>
                                    <small class="text-muted">' . $businessName . '</small>
                                </div>
                            </div>                        
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
                    'user' => e($order->user->name ?? '-'),
                    'action' => view('erp.pages.sales.sale-orders.partials.action-button', compact('order'))->render(),
                    'action_mobile' => view('erp.pages.sales.sale-orders.partials.action-button-mobile', compact('order'))->render(),
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    public function create()
    {
        // Blob katalog yang ditanam ke HTML halaman ini disimpan sebagai JSON
        // jadi, supaya tiap page load tidak mengulang query katalog, hidrasi
        // model, pemetaan array, lalu json_encode.
        $catalogPayload = app(ErpCatalogPayload::class);

        $productsJson = $catalogPayload->json('sale-orders:create:products', function () {
            $products = Products::query()
                ->select([
                    'id',
                    'name',
                    'sku',
                    'price',
                    'sale_price',
                    'base_unit_id',
                    'sale_unit_id',
                ])
                ->with([
                    'discounts',
                    'categories:id',
                    'unitConversions:id,product_id,unit_id,conversion_value,sale_price',
                    'unitConversions.unit:id,name',
                    'unitConversions.prices.priceMode',
                ])
                ->orderBy('name', 'asc')
                ->get();
                return $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'base_unit_id' => $product->base_unit_id,
                    'sale_unit_id' => $product->sale_unit_id,
                    'discounts' => $product->discounts->toArray(),
                    'categories' => $product->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'discounts' => $category->discounts->map(function ($discount) use ($category) {
                                return array_merge($discount->toArray(), [
                                    'category_id' => $category->id,
                                ]);
                            })->toArray(),
                        ];
                    })->toArray(),
                    'units' => $product->unitConversions->map(function ($conversion) {
                        return [
                            'id' => $conversion->id,
                            'unit_id' => $conversion->unit_id,
                            'unit_name' => optional($conversion->unit)->name,
                            'conversion_value' => $conversion->conversion_value,
                            'sale_price' => $conversion->sale_price,
                            'dynamic_prices' => $conversion->relationLoaded('prices')
                                ? $conversion->prices->mapWithKeys(fn ($price) => [$price->priceMode->slug => (float) $price->sale_price])->toArray()
                                : [],
                        ];
                    })->values()->toArray(),
                ];
            })->toArray();
        });

        // The form only needs bundle membership, not the full pricing/discount graph.
        $productBundlesJson = $catalogPayload->json('sale-orders:create:bundles', function () {
            $productBundles = ProductBundle::query()
                ->select(['id', 'base_unit_id', 'price'])
                ->with([
                    'primaryItem:id,bundle_id,product_id,role',
                    'secondaryItems:id,bundle_id,product_id,role',
                    'secondaryItems.product:id,name,sku',
                    'unitConversions:id,product_bundle_id,unit_id,conversion_value,sale_price',
                    'unitConversions.unit:id,name',
                    'unitConversions.prices.priceMode',
                ])
                ->get();
                return $productBundles->map(function ($bundle) {
                return [
                    'id' => $bundle->id,
                    'base_unit_id' => $bundle->base_unit_id,
                    'price' => $bundle->price,
                    'units' => $bundle->unitConversions->map(function ($conversion) {
                        return [
                            'id' => $conversion->id,
                            'unit_id' => $conversion->unit_id,
                            'unit_name' => optional($conversion->unit)->name,
                            'conversion_value' => $conversion->conversion_value,
                            'sale_price' => $conversion->sale_price,
                            'dynamic_prices' => $conversion->relationLoaded('prices')
                                ? $conversion->prices->mapWithKeys(fn ($price) => [$price->priceMode->slug => (float) $price->sale_price])->toArray()
                                : [],
                        ];
                    })->values()->toArray(),
                    'primary_item' => $bundle->primaryItem ? [
                        'product_id' => $bundle->primaryItem->product_id,
                    ] : null,
                    'secondary_items' => $bundle->secondaryItems->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'sku' => $item->product->sku,
                            ] : null,
                        ];
                    })->values()->toArray(),
                ];
            })->toArray();
            // $customers = Customers::with('addresses')->get();
        });
        $user = Auth::user();

        $customers = Customers::query()
            ->select(['id', 'name', 'user_id'])
            ->with([
                'addresses:id,customer_id,business_name,address,google_maps',
                'accounts:id,name,whatsapp_number',
            ])
            ->when($user->role === 'Sales', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();
        $priceModes = PriceMode::active()->ordered()->get();
        $modeDiscounts = Discount::modeDiscountsPayload();
        return view('erp.pages.sales.sale-orders.create-order', compact(
            'customers',
            'productsJson',
            'productBundlesJson',
            'priceModes',
            'modeDiscounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_date'            => 'required|date_format:Y-m-d\TH:i',
            'customer_id'          => 'required|exists:customers,id',
            'customer_account_id' => 'required|exists:customer_accounts,id',
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
            'mode'   => 'required|array',
            'mode.*' => ['required', Rule::exists('price_modes', 'slug')->where('is_active', true)],
            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',

            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',

            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
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
                'user_id'            => Auth::id(),
                'customer_id'      => $request->customer_id,
                'customer_account_id' => $request->customer_account_id,
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
                'mode'              => 'mixed',
                'discount_active' => (int) $request->input('discount_active_hidden', 1),
            ]);

            // ================== BUAT ORDER ITEMS ==================
            foreach ($request->product as $index => $productValue) {
                if (str_contains($productValue, '_')) {
                    [$typeFromValue, $realProductId] = explode('_', $productValue);

                    $type = strtolower($typeFromValue);
                    $productInputId = $realProductId;
                } else {
                    $type = strtolower($request->product_type[$index]);
                    $productInputId = $productValue;
                }

                $type = strtolower($request->product_type[$index]);
                $qty  = (float) $request->qty[$index];
                $itemMode = $request->mode[$index] ?? 'printing';

                if ($type === 'bundle') {
                    $unitConversionId = $request->input("product_bundle_unit_conversion_id.$index")
                        ?? $request->input("product_unit_id.$index");
                } else {
                    $unitConversionId = $request->input("product_unit_id.$index");
                }

                if ($unitConversionId === '' || $unitConversionId === 'null' || !is_numeric($unitConversionId)) {
                    $unitConversionId = null;
                }

                $unitConversionValue = (float) $request->input("unit_conversion_value.$index", 1);
                $unitName = $request->input("unit_name.$index", 'Pcs');

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $qtyBase = $qty * $unitConversionValue;

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
                        'product_unit_conversion_id' => $unitConversionId,
                        'product_bundle_unit_conversion_id' => null,
                        'unit_name'                  => $unitName,
                        'unit_conversion_value'      => $unitConversionValue,
                        'qty_base'                   => $qtyBase,
                        'product_name'         => $product->name,
                        'satuan'               => 'satuan',
                        'quantity'             => $qty,
                        'mode'                 => $itemMode,
                        'completed_quantity'   => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    // Simpan juga ke order_item_components (biar struktur seragam)
                    OrderItemComponent::create([
                        'order_item_id'       => $orderItem->id,
                        'product_id'          => $product->id,
                        'qty'                 => $qtyBase,
                        'avg_cost_at_sale'    => $avgCost,
                        'fixed_cost_at_sale'  => $fixedCost,
                        'total_cost'          => $avgCost * $qtyBase,
                        'total_fixed_cost'    => $fixedCost * $qtyBase,
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
                        'product_unit_conversion_id' => null,
                        'product_bundle_unit_conversion_id' => $unitConversionId,
                        'unit_name'                  => $unitName,
                        'unit_conversion_value'      => $unitConversionValue,
                        'qty_base'                   => $qtyBase,
                        'product_name'         => $bundleProductNames ?: $bundle->name,
                        'satuan'               => 'bundle',
                        'quantity'             => $qty,
                        'mode'                 => $itemMode,
                        'completed_quantity'   => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    foreach ($bundle->items as $bundleItem) {
                        $component = $bundleItem->product;

                        if (!$component) {
                            continue;
                        }

                        $componentStock = \App\Models\InventoryStock::where('product_id', $component->id)->first();

                        $componentAvgCost = $componentStock?->avg_cost ?? 0;
                        $componentFixedCost = $component->fixed_cost ?? 0;

                        $componentQty = $qtyBase * ($bundleItem->quantity ?? 1);

                        OrderItemComponent::create([
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $component->id,
                            'qty'                 => $componentQty,
                            'avg_cost_at_sale'    => $componentAvgCost,
                            'fixed_cost_at_sale'  => $componentFixedCost,
                            'total_cost'          => $componentAvgCost * $componentQty,
                            'total_fixed_cost'    => $componentFixedCost * $componentQty,
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
        $order = Order::with([
            'orderItems.product.unitConversions.unit',
            'orderItems.productBundle.unitConversions.unit',
            'customer.addresses',
            'customer.accounts',
            'customerAddress',
        ])->findOrFail($id);

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

        // Blob katalog di-cache sebagai JSON jadi lewat ErpCatalogPayload.
        $catalogPayload = app(ErpCatalogPayload::class);

        // $customers = Customers::with('addresses')->orderBy('name', 'asc')->get();

        $user = Auth::user();

        $customers = Customers::with(['addresses', 'accounts'])
            ->when($user->role === 'Sales', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('name', 'asc')
            ->get();

        // 🔹 JSON untuk produk tunggal
        $productsJson = $catalogPayload->json('sale-orders:edit:products', function () {
            $products = Products::with([
                'categories',
                'discounts',
                'categories.discounts',
                'unitConversions.unit',
                'unitConversions.prices.priceMode',
            ])
                ->orderBy('name', 'asc')
                ->get();
                return $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku'  => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'base_unit_id' => $product->base_unit_id,
                    'sale_unit_id' => $product->sale_unit_id,
                    'discounts' => $product->discounts->toArray(),
                    'categories' => $product->categories->map(function ($cat) {
                        return [
                            'id' => $cat->id,
                            'discounts' => $cat->discounts->map(function ($d) use ($cat) {
                                return array_merge($d->toArray(), ['category_id' => $cat->id]);
                            })->toArray()
                        ];
                    })->toArray(),
                    'units' => $product->unitConversions->map(function ($conversion) {
                        return [
                            'id' => $conversion->id,
                            'unit_id' => $conversion->unit_id,
                            'unit_name' => optional($conversion->unit)->name,
                            'conversion_value' => $conversion->conversion_value,
                            'sale_price' => $conversion->sale_price,
                            'dynamic_prices' => $conversion->relationLoaded('prices')
                                ? $conversion->prices->mapWithKeys(fn ($price) => [$price->priceMode->slug => (float) $price->sale_price])->toArray()
                                : [],
                        ];
                    })->values()->toArray(),
                ];
            })->toArray();
        });

        // 🔹 JSON untuk produk bundle (gabungkan nama produk)
        $productBundlesJson = $catalogPayload->json('sale-orders:edit:bundles', function () {
            $productBundles = ProductBundle::with([
                'items.product.categories.discounts',
                'items.product.discounts',

                'primaryItem.product',
                'secondaryItems.product',

                'unitConversions.unit',
                'unitConversions.prices.priceMode',
            ])->orderBy('name', 'asc')->get();

            // Kalau belum ada relasi diskon di bundle, beri array kosong
            $productBundles->map(function ($bundle) {
                $bundle->discounts = [];
                return $bundle;
            });
                return $productBundles->map(function ($bundle) {
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
                    'base_unit_id' => $bundle->base_unit_id,
                    'categories' => $bundleCategories,
                    'units' => $bundle->unitConversions->map(function ($conversion) {
                        return [
                            'id' => $conversion->id,
                            'unit_id' => $conversion->unit_id,
                            'unit_name' => optional($conversion->unit)->name,
                            'conversion_value' => $conversion->conversion_value,
                            'sale_price' => $conversion->sale_price,
                            'dynamic_prices' => $conversion->relationLoaded('prices')
                                ? $conversion->prices->mapWithKeys(fn ($price) => [$price->priceMode->slug => (float) $price->sale_price])->toArray()
                                : [],
                        ];
                    })->values()->toArray(),
                    'primary_item' => $bundle->primaryItem ? [
                        'product_id' => $bundle->primaryItem->product_id,
                        'product' => $bundle->primaryItem->product,
                    ] : null,

                    'secondary_items' => $bundle->secondaryItems->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product' => $item->product,
                        ];
                    })->values()->toArray(),
                ];
            })->toArray();
        });

        $priceModes = PriceMode::active()->ordered()->get();
        $modeDiscounts = Discount::modeDiscountsPayload();

        return view('erp.pages.sales.sale-orders.edit-order', compact(
            'order',
            'customers',
            'productsJson',
            'productBundlesJson',
            'dueDateOption',
            'customDueDate',
            'priceModes',
            'modeDiscounts'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'order_date'              => 'required|date_format:Y-m-d\TH:i',
            'customer_id' => 'required|exists:customers,id',
            'customer_account_id' => 'required|exists:customer_accounts,id',
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
            'mode'   => 'required|array',
            'mode.*' => ['required', Rule::exists('price_modes', 'slug')->where('is_active', true)],
            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',

            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',

            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
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
                'customer_account_id' => $request->customer_account_id,
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
            ]);

            // === UPDATE / INSERT ORDER ITEMS BARU ===
            $processedItemIds = [];

            foreach ($request->product as $index => $productInputId) {
                [$type, $pid] = explode('_', $productInputId);
                $type = strtolower($type);
                $qty  = (float) $request->qty[$index];
                $itemMode = $request->mode[$index] ?? 'printing';

                $unitConversionId = $request->product_unit_id[$index] ?? null;

                if (!is_numeric($unitConversionId)) {
                    $unitConversionId = null;
                }

                $unitConversionValue = (float) ($request->unit_conversion_value[$index] ?? 1);
                $unitName = $request->unit_name[$index] ?? 'Pcs';

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $qtyBase = $qty * $unitConversionValue;

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
                            'product_unit_conversion_id' => $unitConversionId,
                            'product_bundle_unit_conversion_id' => null,
                            'unit_name'                  => $unitName,
                            'unit_conversion_value'      => $unitConversionValue,
                            'qty_base'                   => $qtyBase,

                            'mode'                 => $itemMode,
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
                                'qty'                 => $qtyBase,
                                'avg_cost_at_sale'    => $avgCost,
                                'fixed_cost_at_sale'  => $fixedCost,
                                'total_cost'          => $avgCost * $qtyBase,
                                'total_fixed_cost'    => $fixedCost * $qtyBase,
                            ]);
                        }
                    } else {
                        // buat baru kalau belum ada
                        $orderItem = OrderItem::create([
                            'order_id'             => $order->id,
                            'product_id'           => $product->id,
                            'product_bundle_id'    => null,

                            'product_unit_conversion_id' => $unitConversionId,
                            'product_bundle_unit_conversion_id' => null,
                            'unit_name'                  => $unitName,
                            'unit_conversion_value'      => $unitConversionValue,
                            'qty_base'                   => $qtyBase,

                            'product_name'         => $product->name,
                            'satuan'               => 'satuan',
                            'quantity'             => $qty,
                            'mode'                 => $itemMode,
                            'completed_quantity'   => 0,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        $orderItem->components()->create([
                            'product_id'          => $product->id,
                            'qty'                 => $qtyBase,
                            'avg_cost_at_sale'    => $avgCost,
                            'fixed_cost_at_sale'  => $fixedCost,
                            'total_cost'          => $avgCost * $qtyBase,
                            'total_fixed_cost'    => $fixedCost * $qtyBase,
                        ]);
                    }

                    // kurangi stok
                    $product->decrement('stock_after_sales', $qtyBase);
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
                            'product_unit_conversion_id' => null,
                            'product_bundle_unit_conversion_id' => $unitConversionId,
                            'unit_name'                  => $unitName,
                            'unit_conversion_value'      => $unitConversionValue,
                            'qty_base'                   => $qtyBase,
                            'mode'                 => $itemMode,
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
                            $totalQty = $qtyBase * ($bundleItem->quantity ?? 1);

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
                            'product_unit_conversion_id' => null,
                            'product_bundle_unit_conversion_id' => $unitConversionId,
                            'unit_name'                  => $unitName,
                            'unit_conversion_value'      => $unitConversionValue,
                            'qty_base'                   => $qtyBase,

                            'product_name'         => $bundle->name,
                            'satuan'               => 'bundle',
                            'quantity'             => $qty,
                            'mode'                 => $itemMode,
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
                            $totalQty = $qtyBase * ($bundleItem->quantity ?? 1);

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

    public function delete($id, Request $request)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->findOrFail($id);
            $orderNumber = $order->order_number;

            // Hard delete semua order items
            OrderItem::where('order_id', $order->id)->forceDelete();

            // Hard delete transaksi akun kalau ada
            if ($order->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $order->transaction_group_id)->forceDelete();
            }

            // Hard delete order
            $order->forceDelete();

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'id' => $id,
                    'message' => "Order {$orderNumber} berhasil dihapus.",
                ]);
            }

            return redirect()->back()->with('success', 'Order berhasil dihapus permanen.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus order: ' . $e->getMessage(),
                ], 500);
            }

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
            'deposit_used' => str_replace('.', '', $request->input('deposit_used', 0)),
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
            'deposit_used' => 'nullable|numeric|min:0',
            'use_write_off_only' => 'nullable|boolean',
        ];

        if ($request->payment_status !== 'Unpaid') {
            $rules = array_merge($rules, [
                'paid_amount' => 'nullable|numeric|min:0',
                'cash_bank_account_id' => [
                    'nullable',
                    Rule::requiredIf(fn () => (float) $request->input('paid_amount', 0) > 0),
                    'exists:accounts,id',
                ],
                'transaction_date' => 'nullable|date',
                'transaction_type' => 'nullable|exists:accounts,id',
            ]);
        }

        $request->validate($rules, [
            'cash_bank_account_id.required' => 'Cash/Bank Account wajib dipilih.',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with([
                'orderItems.product',
                'orderItems.productBundle.items.product',
                'customer',
            ])->findOrFail($id);

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

            $paidAmount = (float) ($request->paid_amount ?? 0);
            $depositUsed = (float) ($request->deposit_used ?? 0);
            $totalAmount = (float) $order->grand_total;
            $previousPaidAmount = (float) $order->paid_amount;
            $outstandingAmount = max(0, $totalAmount - $previousPaidAmount);
            $useWriteOff = $request->boolean('use_write_off_only');

            if (($paidAmount + $depositUsed) > $outstandingAmount) {
                throw new \RuntimeException('Total pembayaran dan deposit tidak boleh melebihi remaining.');
            }

            if ($depositUsed > (float) ($order->customer?->customer_deposit ?? 0)) {
                throw new \RuntimeException('Customer deposit tidak mencukupi.');
            }


            if ($paidAmount > 0 && !$request->filled('cash_bank_account_id')) {
                throw new \RuntimeException('Pilih cash atau bank account untuk pembayaran.');
            }

            $newPaidAmount = $previousPaidAmount + $paidAmount + $depositUsed;
            $remainingAmount = max(0, $totalAmount - $newPaidAmount);
            $writeOffAmount = $useWriteOff ? $remainingAmount : 0;

            if ($useWriteOff) {
                $remainingAmount = 0;
                $paymentStatus = 'Paid';
            } elseif ($newPaidAmount <= 0) {
                $paymentStatus = 'Unpaid';
            } elseif ($newPaidAmount < $totalAmount) {
                $paymentStatus = 'Partially Paid';
            } else {
                $paymentStatus = 'Paid';
            }

            $groupId = Str::uuid();
            $saleAccount = Account::findOrFail($request->transaction_type);

            AccountTransaction::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
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

            if ($depositUsed > 0) {
                $customerDepositAccount = Account::where('type', 'Customer Deposit')->firstOrFail();

                $order->customer->decrement('customer_deposit', $depositUsed);

                AccountTransaction::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'order_number' => $order->order_number,
                    'transaction_date' => $request->transaction_date,
                    'account_id' => $customerDepositAccount->id,
                    'debit' => $depositUsed,
                    'credit' => 0,
                    'note' => 'Deposit used for payment',
                    'particular' => 'Use Deposit',
                    'transaction_group_id' => $groupId,
                    'verified' => 1,
                ]);

                $customerDepositAccount->decrement('closing_balance', $depositUsed);
            }

            if ($writeOffAmount > 0) {
                $expenseAccount = Account::firstOrCreate(
                    ['type' => 'Write Off'],
                    [
                        'name' => 'Expense',
                        'opening_balance' => 0,
                        'closing_balance' => 0,
                    ]
                );

                AccountTransaction::create([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'transaction_date' => $request->transaction_date,
                    'account_id' => $expenseAccount->id,
                    'debit' => $writeOffAmount,
                    'credit' => 0,
                    'note' => 'Write off remaining balance',
                    'particular' => 'Write Off - '.$order->order_number,
                    'transaction_group_id' => $groupId,
                    'verified' => 1,
                ]);

                $expenseAccount->increment('closing_balance', $writeOffAmount);
            }

            $order->update([
                'status' => $status,
                'order_date' => $request->order_date,
                'due_date' => $dueDate,
                'paid_amount' => $newPaidAmount,
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

            foreach ($order->orderItems as $orderItem) {
                if (!$orderItem->mode) {
                    $orderItem->mode = $order->mode ?? 'printing';
                    $orderItem->save();
                }
            }

            $order->load([
                'orderItems.product',
                'orderItems.productBundle.items.product',
            ]);

            // ================== HANDLE MODE PER ORDER ITEM ==================
            $orderItems = $order->orderItems;

            $hasPrinting = $orderItems->contains(fn($item) => $item->mode === 'printing');
            $hasPolosan  = $orderItems->contains(fn($item) => $item->mode === 'polosan');

            $design = null;
            $polosanDesignItems = [];

            // Kalau ada item printing atau polosan, tetap buat Design sebagai penghubung.
            // Printing: pending.
            // Full polosan: langsung verified.
            if ($hasPrinting || $hasPolosan) {
                $design = Design::create([
                    'order_id'            => $order->id,
                    'design_number'       => $order->order_number,
                    'date'                => now()->format('Y-m-d'),
                    'status'              => $hasPrinting ? 'Pending' : 'Verified',
                    'notes'               => null,
                    'verification_status' => $hasPrinting ? 'pending' : 'approved',
                    'verified_by'         => $hasPrinting ? null : Auth::id(),
                    'verified_at'         => $hasPrinting ? null : now(),
                ]);

                foreach ($orderItems as $orderItem) {
                    $qtyInput = $orderItem->quantity;

                    $unitData = [
                        'product_unit_conversion_id' => $orderItem->satuan === 'satuan'
                            ? $orderItem->product_unit_conversion_id
                            : $orderItem->product_bundle_unit_conversion_id,

                        'unit_name' => $orderItem->unit_name,

                        'unit_conversion_value' => $orderItem->unit_conversion_value,
                    ];

                    if ($orderItem->satuan === 'satuan') {
                        if (!$orderItem->product_id) {
                            continue;
                        }

                        DesignItem::create(array_merge([
                            'design_id'           => $design->id,
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $orderItem->product_id,

                            // INI QTY INPUT, BUKAN QTY_BASE
                            'quantity'            => $qtyInput,

                            'completed_quantity'  => 0,
                            'design_file'         => null,
                            'preview_image'       => null,
                            'verification_status' => 'pending',
                        ], $unitData));

                        continue;
                    }

                    if ($orderItem->satuan === 'bundle') {
                        if (!$orderItem->productBundle) {
                            continue;
                        }

                        foreach ($orderItem->productBundle->items as $bundleItem) {
                            $bundleProduct = $bundleItem->product;

                            if (!$bundleProduct) {
                                continue;
                            }

                            // INI TETAP PAKAI QTY INPUT, BUKAN QTY_BASE
                            $componentQty = $qtyInput * ($bundleItem->quantity ?? 1);

                            DesignItem::create(array_merge([
                                'design_id'           => $design->id,
                                'order_item_id'       => $orderItem->id,
                                'product_id'          => $bundleProduct->id,

                                'quantity'            => $componentQty,

                                'completed_quantity'  => 0,
                                'design_file'         => null,
                                'preview_image'       => null,
                                'verification_status' => 'pending',
                            ], $unitData));
                        }
                    }
                }
            }

            // ================== ITEM POLOSAN LANGSUNG KE ORDER PROGRESS + DELIVERY ==================
            if ($hasPolosan && count($polosanDesignItems) > 0) {
                $orderProgress = OrderProgress::create([
                    'order_id'       => $order->id,
                    'design_id'      => $design->id,
                    'date'           => now()->format('Y-m-d'),
                    'status'         => 'Completed',
                    'notes'          => null,
                    'invoice_number' => $order->order_number,
                ]);

                $progressItems = [];

                foreach ($polosanDesignItems as $designItem) {
                    $progressItems[] = OrderProgressItem::create([
                        'order_progress_id'  => $orderProgress->id,
                        'design_item_id'     => $designItem->id,
                        'order_item_id'      => $designItem->order_item_id,
                        'product_id'         => $designItem->product_id,
                        'quantity'           => $designItem->quantity,
                        'completed_quantity' => $designItem->quantity,
                    ]);
                }

                $deliveryOrder = DeliveryOrder::create([
                    'order_id'         => $order->id,
                    'design_id'        => $design->id,
                    'delivery_number'  => $order->order_number,
                    'delivery_date'    => now()->format('Y-m-d'),
                    'note'             => $order->notes ?? '',
                    'status'           => 'Ongoing',
                    'customer'         => $order->customer?->name ?? '-',
                    'shipping_address' => $order->shipping_address,
                    'google_map_link'  => $order->google_maps,
                    'created_by'       => Auth::id(),
                ]);

                foreach ($progressItems as $progressItem) {
                    DeliveryOrderItem::create([
                        'delivery_order_id'       => $deliveryOrder->id,
                        'order_progress_id'       => $orderProgress->id,
                        'order_item_id'           => $progressItem->order_item_id,
                        'order_progress_item_id'  => $progressItem->id,
                        'design_item_id'          => $progressItem->design_item_id,
                        'product_id'              => $progressItem->product_id,
                        'status'                  => 'Completed',
                        'progress_qty'            => $progressItem->quantity,
                        'ready_qty'               => $progressItem->quantity,
                        'shipped_qty'             => 0,
                        'note'                    => null,
                    ]);
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
