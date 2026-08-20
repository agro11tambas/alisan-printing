<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\CustomerAddresses;
use App\Models\Customers;
use App\Models\Discount;
use App\Models\EcommerceProduct;
use App\Models\EcommerceVariantOption;
use App\Models\EcommerceVariantCombination;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemComponent;
use App\Models\Products;
use App\Services\EcommercePricingService;
use App\Services\InvoiceNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EcommerceSaleOrderController extends Controller
{
    /**
     * Status order yang masih ditampilkan di halaman pesanan customer.
     * Sale Order = belum diverifikasi, sisanya sudah jadi invoice dan
     * dilanjutkan ke produksi / pengiriman.
     */
    private const CUSTOMER_VISIBLE_STATUSES = [
        'Sale Order',
        'Sale List',
        'Waiting List',
        'Complete List',
        'Delivery',
        'Delivered',
    ];

    /**
     * Relasi yang dibutuhkan orderPayload(), termasuk assign waiting list dan
     * delivery supaya tahap pesanan tidak menembak query per baris.
     */
    private const ORDER_PAYLOAD_RELATIONS = [
        'customer',
        'customerAccount',
        'customerAddress',
        'orderItems.product',
        'orderProgress.items.assigns',
        'deliveryOrders.items',
        'deliveryOrders.shipments',
    ];

    public function __construct(private EcommercePricingService $pricingService)
    {
    }

    public function index(Request $request)
    {
        $account = $this->customerAccount($request);
        $customerIds = $this->accessibleCustomerIds($account);

        $orders = Order::with(self::ORDER_PAYLOAD_RELATIONS)
            ->where(function ($query) use ($account, $customerIds) {
                $query->where('customer_account_id', $account->id);

                if (!empty($customerIds)) {
                    $query->orWhereIn('customer_id', $customerIds);
                }
            })
            ->whereIn('status', self::CUSTOMER_VISIBLE_STATUSES)
            ->latest('order_date')
            ->paginate((int) $request->input('per_page', 10));

        $orders->getCollection()->transform(fn (Order $order) => $this->orderPayload($order));

        return response()->json([
            'success' => true,
            'message' => 'Sale orders retrieved successfully.',
            'data' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'shipping' => ['nullable', 'array'],
            'shipping.business_name' => ['nullable', 'string', 'max:255'],
            'shipping.recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping.whatsapp_number' => ['nullable', 'string', 'max:255'],
            'shipping.address' => ['nullable', 'string'],
            'shipping.google_maps' => ['nullable', 'string'],
            'order_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ecommerce_product_id' => ['required', 'integer', 'exists:ecommerce_products,id'],
            'items.*.ecommerce_variant_combination_id' => ['nullable', 'integer', 'exists:ecommerce_variant_combinations,id'],
            'items.*.variant_option_id' => ['nullable', 'integer', 'exists:ecommerce_variant_options,id'],
            'items.*.variant_option_ids' => ['nullable', 'array'],
            'items.*.variant_option_ids.*' => ['integer', 'distinct', 'exists:ecommerce_variant_options,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.mode' => [
                'required',
                Rule::exists('price_modes', 'slug')->where('is_active', true),
            ],
        ]);

        $account = $this->customerAccount($request, $validated['shipping'] ?? []);
        $customer = $this->resolveCustomer($account, $validated['customer_id'] ?? null, $validated['shipping'] ?? []);
        $address = $this->resolveAddress(
            $customer,
            $validated['customer_address_id'] ?? null,
            $validated['shipping'] ?? []
        );

        $lineItems = $this->applyDiscounts($this->buildLineItems($validated['items']));
        $subTotal = collect($lineItems)->sum('subtotal');
        $discountTotal = collect($lineItems)->sum('discount_amount');
        $grandTotal = $subTotal - $discountTotal;
        $paidAmount = (float) ($validated['paid_amount'] ?? 0);
        $remainingAmount = $grandTotal - $paidAmount;
        $orderDate = Carbon::parse($validated['order_date'] ?? now())->setTimezone(config('app.timezone'));
        $orderMode = collect($lineItems)->pluck('mode')->unique()->count() === 1
            ? $lineItems[0]['mode']
            : 'mixed';

        $order = DB::transaction(function () use (
            $account,
            $address,
            $customer,
            $discountTotal,
            $grandTotal,
            $lineItems,
            $orderDate,
            $orderMode,
            $paidAmount,
            $remainingAmount,
            $request,
            $subTotal,
            $validated
        ) {
            $order = Order::create([
                'user_id' => null,
                'customer_id' => $customer->id,
                'customer_account_id' => $account->id,
                'customer_address_id' => $address->id,
                'order_number' => InvoiceNumberService::generate('SO', $orderDate),
                'order_date' => $orderDate,
                'status' => 'Sale Order',
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_status' => $this->paymentStatus($paidAmount, $grandTotal),
                'paid_amount' => $paidAmount,
                'business_name' => $address->business_name,
                'shipping_address' => $address->address,
                'google_maps' => $address->google_maps,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $subTotal,
                'grand_total' => $grandTotal,
                'discount' => $discountTotal,
                'remaining_amount' => $remainingAmount,
                'mode' => $orderMode,
                'discount_active' => $discountTotal > 0,
            ]);

            foreach ($lineItems as $line) {
                $isBundle = $line['is_bundle'];
                $erpItem = $line['erp_item'];

                $conversionId = $line['unit_conversion_id'];

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $isBundle ? null : $erpItem->id,
                    'product_bundle_id' => $isBundle ? $erpItem->id : null,
                    'product_unit_conversion_id' => $isBundle ? null : $conversionId,
                    'product_bundle_unit_conversion_id' => $isBundle ? $conversionId : null,
                    'unit_name' => $line['unit_name'],
                    'unit_conversion_value' => $line['unit_conversion_value'],
                    'qty_base' => $line['quantity'] * $line['unit_conversion_value'],
                    'product_name' => $line['erp_item']->name,
                    'satuan' => $line['is_bundle'] ? 'bundle' : 'satuan',
                    'quantity' => $line['quantity'],
                    'mode' => $line['mode'],
                    'completed_quantity' => 0,
                    'price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                    'discount_price' => $line['unit_price_after_discount'],
                    'total_after_discount' => $line['total_after_discount'],
                ]);

                if ($line['is_bundle'] && !empty($line['bundle_items'])) {
                    foreach ($line['bundle_items'] as $bundleItem) {
                        OrderItemComponent::create([
                            'order_item_id' => $orderItem->id,
                            'product_id' => $bundleItem['product_id'],
                            'qty' => $bundleItem['quantity'],
                            'avg_cost_at_sale' => $bundleItem['avg_cost'],
                            'fixed_cost_at_sale' => $bundleItem['fixed_cost'],
                            'total_cost' => $bundleItem['total_cost'],
                            'total_fixed_cost' => $bundleItem['total_fixed_cost'],
                        ]);
                    }
                } else if (!$line['is_bundle']) {
                    OrderItemComponent::create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $line['erp_item']->id,
                        'qty' => $line['quantity'],
                        'avg_cost_at_sale' => $line['avg_cost'],
                        'fixed_cost_at_sale' => $line['fixed_cost'],
                        'total_cost' => $line['avg_cost'] * $line['quantity'],
                        'total_fixed_cost' => $line['fixed_cost'] * $line['quantity'],
                    ]);
                }
            }

            return $order->load(array_merge(
                self::ORDER_PAYLOAD_RELATIONS,
                ['orderItems.components.product']
            ));
        });

        return response()->json([
            'success' => true,
            'message' => 'Sale order created successfully.',
            'data' => $this->orderPayload($order),
        ], 201);
    }

    public function show(Request $request, Order $order)
    {
        $account = $this->customerAccount($request);

        if (!$this->accountCanAccessOrder($account, $order)) {
            abort(404);
        }

        $order->load(array_merge(
            self::ORDER_PAYLOAD_RELATIONS,
            ['orderItems.components.product']
        ));

        return response()->json([
            'success' => true,
            'message' => 'Sale order retrieved successfully.',
            'data' => $this->orderPayload($order),
        ]);
    }

    private function customerAccount(Request $request, array $shipping = []): CustomerAccount
    {
        $account = $request->user('sanctum') ?? $request->user();

        // GUEST CHECKOUT
        if (!$account) {
            $wa = $shipping['whatsapp_number'] ?? '081234567890';
            $name = $shipping['recipient_name'] ?? 'Guest';
            
            $wa = preg_replace('/\D/', '', $wa);

            $account = CustomerAccount::firstOrCreate(
                ['whatsapp_number' => $wa],
                [
                    'name' => $name,
                    'is_active' => true,
                    'password' => bcrypt('password123') // Default for guest
                ]
            );
        }

        if (!$account instanceof CustomerAccount || !$account->is_active) {
            abort(403, 'Customer account is not active.');
        }

        return $account;
    }

    private function resolveCustomer(CustomerAccount $account, ?int $customerId, array $shipping = []): Customers
    {
        $customer = null;

        if ($customerId) {
            $customer = $account->customers()
                ->where('customers.id', $customerId)
                ->first();

            if (!$customer && (int) $account->customer_id === $customerId) {
                $customer = Customers::find($customerId);
            }
        } else {
            $customer = $account->customers()->orderBy('customers.id')->first()
                ?? $account->customer;
        }

        if (!$customer) {
            $wa = $shipping['whatsapp_number'] ?? $account->whatsapp_number;
            $name = $shipping['recipient_name'] ?? $account->name;

            $customer = Customers::create([
                'name' => $name,
                'phone' => $wa,
                'email' => $account->email,
            ]);

            $account->update(['customer_id' => $customer->id]);
            if ($account->customers()->where('customers.id', $customer->id)->doesntExist()) {
                $account->customers()->attach($customer->id);
            }
        }

        return $customer;
    }

    private function resolveAddress(Customers $customer, ?int $addressId, array $shipping): CustomerAddresses
    {
        if ($addressId) {
            $address = $customer->addresses()
                ->whereKey($addressId)
                ->first();

            if (!$address) {
                throw ValidationException::withMessages([
                    'customer_address_id' => 'Alamat tidak terhubung dengan customer ini.',
                ]);
            }

            return $address;
        }

        if (!empty($shipping['address'])) {
            return $customer->addresses()->create([
                'business_name' => $shipping['business_name'] ?? null,
                'address' => $shipping['address'],
                'google_maps' => $shipping['google_maps'] ?? null,
            ]);
        }

        $address = $customer->addresses()->orderBy('id')->first();

        if (!$address) {
            throw ValidationException::withMessages([
                'shipping.address' => 'Alamat checkout wajib diisi.',
            ]);
        }

        return $address;
    }

    private function buildLineItems(array $items): array
    {
        $lineItems = [];

        foreach ($items as $index => $item) {
            $ecommerceProduct = EcommerceProduct::with(['categories', 'unit', 'variantGroups', 'priceModes'])
                ->whereKey($item['ecommerce_product_id'])
                ->where('is_active', true)
                ->whereHas('categories', fn ($query) => $query->where('is_active', true))
                ->first();

            if (!$ecommerceProduct) {
                throw ValidationException::withMessages([
                    "items.$index.ecommerce_product_id" => 'Ecommerce product tidak aktif atau tidak ditemukan.',
                ]);
            }

            $quantity = (int) $item['quantity'];
            $mode = (string) $item['mode'];
            $unitId = (int) $ecommerceProduct->unit_id;
            if (!$ecommerceProduct->priceModes->contains('slug', $mode)) {
                throw ValidationException::withMessages([
                    "items.$index.mode" => 'Mode tidak diaktifkan untuk Ecommerce Product ini.',
                ]);
            }
            $this->validateQuantity($ecommerceProduct, $quantity, $index);

            if (!empty($item['ecommerce_variant_combination_id'])) {
                $combination = EcommerceVariantCombination::with(['productOption.product.categories', 'lidOption.product.categories'])
                    ->whereKey($item['ecommerce_variant_combination_id'])
                    ->where('ecommerce_product_id', $ecommerceProduct->id)
                    ->where('is_active', true)
                    ->first();

                if (!$combination) {
                    throw ValidationException::withMessages([
                        "items.$index.ecommerce_variant_combination_id" => 'Kombinasi produk tidak ditemukan.',
                    ]);
                }

                $primaryProductId = $combination->productOption?->product_id;
                $secondaryProductId = $combination->lidOption?->product_id;

                if (!$primaryProductId || !$secondaryProductId) {
                    throw ValidationException::withMessages([
                        "items.$index.ecommerce_variant_combination_id" => 'Data kombinasi tidak lengkap.',
                    ]);
                }

                $bundle = $this->pricingService->bundleForPair(
                    (int) $primaryProductId,
                    (int) $secondaryProductId
                );

                if (!$bundle) {
                    throw ValidationException::withMessages([
                        "items.$index.ecommerce_variant_combination_id" => 'Product Bundle untuk kombinasi ini tidak ditemukan.',
                    ]);
                }

                $priceData = $this->pricingService->bundlePrice($bundle, $unitId, $mode);
                $conversion = $bundle->unitConversions->firstWhere('unit_id', $unitId);
                if (!$priceData || !$conversion) {
                    throw ValidationException::withMessages([
                        "items.$index.mode" => 'Mode tidak tersedia untuk kombinasi produk dan unit ini.',
                    ]);
                }
                $unitPrice = (float) $priceData['price'];

                $bundleItems = [];
                foreach ($bundle->items as $bItem) {
                    $bQty = $bItem->quantity * $quantity;
                    $bundleItems[] = [
                        'product_id' => $bItem->product_id,
                        'quantity' => $bQty,
                        'avg_cost' => (float) ($bItem->product?->avg_cost ?? 0),
                        'fixed_cost' => (float) ($bItem->product?->fixed_cost ?? 0),
                        'total_cost' => (float) ($bItem->product?->avg_cost ?? 0) * $bQty,
                        'total_fixed_cost' => (float) ($bItem->product?->fixed_cost ?? 0) * $bQty,
                    ];
                }

                $lineItems[] = [
                    'is_bundle' => true,
                    'erp_item' => $bundle,
                    'bundle_items' => $bundleItems,
                    'ecommerce_product_id' => $ecommerceProduct->id,
                    'ecommerce_product_title' => $ecommerceProduct->title,
                    'discount_product_id' => (int) $primaryProductId,
                    'discount_category_ids' => $combination->productOption->product->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'discount_ecommerce_category_ids' => $ecommerceProduct->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'variant_option_id' => null,
                    'variant_group' => null,
                    'variant_alias' => null,
                    'quantity' => $quantity,
                    'mode' => $mode,
                    'unit_name' => $ecommerceProduct->unit?->name ?? $bundle->baseUnit?->name ?? 'Pcs',
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                    'unit_conversion_id' => $conversion->id,
                    'unit_conversion_value' => (float) $conversion->conversion_value,
                    'avg_cost' => 0,
                    'fixed_cost' => (float) $priceData['fixed_cost'],
                ];

            } else {
                $optionIds = $this->variantOptionIds($item);

                if (empty($optionIds) && $ecommerceProduct->variantGroups->count() > 0) {
                    throw ValidationException::withMessages([
                        "items.$index.variant_option_ids" => 'Minimal satu variant option wajib dipilih.',
                    ]);
                }

                if (!empty($optionIds)) {
                    $options = EcommerceVariantOption::with([
                        'group',
                        'product.baseUnit',
                        'product.categories',
                        'product.unitConversions.prices.priceMode',
                    ])
                        ->whereIn('id', $optionIds)
                        ->where('is_active', true)
                        ->get();

                    if ($options->count() !== count($optionIds)) {
                        throw ValidationException::withMessages([
                            "items.$index.variant_option_ids" => 'Salah satu variant option tidak aktif atau tidak ditemukan.',
                        ]);
                    }

                    $this->validateVariantOptions($ecommerceProduct, $options, $index);
                    $primaryGroupId = $ecommerceProduct->variantGroups->first()?->id;
                    $primaryOption = $options->firstWhere('variant_group_id', $primaryGroupId);

                    if (!$primaryOption) {
                        throw ValidationException::withMessages([
                            "items.$index.variant_option_ids" => 'Variant produk utama wajib dipilih.',
                        ]);
                    }

                    $requiresLid = $ecommerceProduct->variantGroups->count() > 1
                        && !$primaryOption->allow_without_lid;

                    if ($requiresLid) {
                        throw ValidationException::withMessages([
                            "items.$index.ecommerce_variant_combination_id" => 'Variant produk ini wajib menggunakan tutup.',
                        ]);
                    }

                    foreach ($options->sortBy(fn ($option) => sprintf(
                        '%010d-%010d',
                        $option->group?->sort_order ?? 0,
                        $option->sort_order ?? 0
                    )) as $option) {
                        $product = $option->product;

                        if (!$product || $product->trashed()) {
                            throw ValidationException::withMessages([
                                "items.$index.variant_option_ids" => 'ERP product pada variant option tidak aktif.',
                            ]);
                        }

                        $priceData = $this->pricingService->productPrice(
                            $product,
                            $unitId,
                            $mode,
                            (float) $option->extra_price
                        );
                        $conversion = $product->unitConversions->firstWhere('unit_id', $unitId);
                        if (!$priceData || !$conversion) {
                            throw ValidationException::withMessages([
                                "items.$index.mode" => 'Mode tidak tersedia untuk salah satu produk pada unit ini.',
                            ]);
                        }
                        $unitPrice = (float) $priceData['price'];

                        $lineItems[] = [
                            'is_bundle' => false,
                            'erp_item' => $product,
                            'bundle_items' => [],
                            'ecommerce_product_id' => $ecommerceProduct->id,
                            'ecommerce_product_title' => $ecommerceProduct->title,
                            'discount_product_id' => (int) $product->id,
                            'discount_category_ids' => $product->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                            'discount_ecommerce_category_ids' => $ecommerceProduct->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
                            'variant_option_id' => $option->id,
                            'variant_group' => $option->group?->name,
                            'variant_alias' => $option->alias,
                            'quantity' => $quantity,
                            'mode' => $mode,
                            'unit_name' => $ecommerceProduct->unit?->name
                                ?? $product->baseUnit?->name
                                ?? 'Pcs',
                            'unit_price' => $unitPrice,
                            'subtotal' => $unitPrice * $quantity,
                            'unit_conversion_id' => $conversion->id,
                            'unit_conversion_value' => (float) $conversion->conversion_value,
                            'avg_cost' => (float) ($product->avg_cost ?? 0),
                            'fixed_cost' => (float) $priceData['fixed_cost'],
                        ];
                    }
                }
            }
        }

        return $lineItems;
    }

    private function applyDiscounts(array $lineItems): array
    {
        $today = now()->toDateString();
        $discounts = Discount::with(['products:id', 'categories:id', 'ecommerceCategories:id'])
            ->where('is_active', 1)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->get();

        $orderQuantity = collect($lineItems)->sum('quantity');
        $orderTotal = collect($lineItems)->sum('subtotal');

        foreach ($lineItems as &$line) {
            $bestDiscountPerUnit = 0.0;
            $productId = (int) $line['discount_product_id'];

            foreach ($discounts as $discount) {
                $eligible = $discount->minimum_based_on === 'Quantity of Items'
                    ? $orderQuantity >= (float) $discount->minimum_qty_or_amount
                    : $orderTotal >= (float) $discount->minimum_qty_or_amount;

                if (!$eligible) {
                    continue;
                }

                $appliesToProduct = $discount->apply_on === 'Product'
                    && $discount->products->contains('id', $productId);
                $appliesToErpCategory = $discount->apply_on === 'Category'
                    && $discount->categories->pluck('id')->intersect($line['discount_category_ids'])->isNotEmpty();
                $appliesToEcommerceCategory = $discount->apply_on_ecommerce === 'Category'
                    && $discount->ecommerceCategories->pluck('id')->intersect($line['discount_ecommerce_category_ids'])->isNotEmpty();

                if (!$appliesToProduct && !$appliesToErpCategory && !$appliesToEcommerceCategory) {
                    continue;
                }

                $discountPerUnit = $discount->type === 'Percentage'
                    ? (float) $line['unit_price'] * ((float) $discount->amount / 100)
                    : (float) $discount->amount;

                $bestDiscountPerUnit = max(
                    $bestDiscountPerUnit,
                    min((float) $line['unit_price'], max(0, $discountPerUnit))
                );
            }

            $line['discount_per_unit'] = $bestDiscountPerUnit;
            $line['discount_amount'] = $bestDiscountPerUnit * (int) $line['quantity'];
            $line['unit_price_after_discount'] = (float) $line['unit_price'] - $bestDiscountPerUnit;
            $line['total_after_discount'] = (float) $line['subtotal'] - $line['discount_amount'];
        }
        unset($line);

        return $lineItems;
    }
    private function validateQuantity(EcommerceProduct $product, int $quantity, int $index): void
    {
        if ($quantity < (int) $product->min_qty) {
            throw ValidationException::withMessages([
                "items.$index.quantity" => "Quantity minimal {$product->min_qty}.",
            ]);
        }

        if ($product->max_qty && $quantity > (int) $product->max_qty) {
            throw ValidationException::withMessages([
                "items.$index.quantity" => "Quantity maksimal {$product->max_qty}.",
            ]);
        }

        $multipleQty = max((int) $product->multiple_qty, 1);

        if ($quantity % $multipleQty !== 0) {
            throw ValidationException::withMessages([
                "items.$index.quantity" => "Quantity harus kelipatan {$multipleQty}.",
            ]);
        }
    }

    private function validateVariantOptions(EcommerceProduct $product, $options, int $index): void
    {
        $requiredGroupIds = $product->variantGroups->pluck('id')->sort()->values();
        $selectedGroupIds = $options->pluck('variant_group_id')->sort()->values();

        $hasWrongProduct = $options->contains(function ($option) use ($product) {
            return (int) $option->group?->ecommerce_product_id !== (int) $product->id;
        });

        if ($hasWrongProduct) {
            throw ValidationException::withMessages([
                "items.$index.variant_option_ids" => 'Variant option tidak sesuai dengan ecommerce product.',
            ]);
        }

        if ($selectedGroupIds->count() !== $selectedGroupIds->unique()->count()) {
            throw ValidationException::withMessages([
                "items.$index.variant_option_ids" => 'Pilih hanya satu option untuk setiap variant group.',
            ]);
        }

        if ($selectedGroupIds->diff($requiredGroupIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                "items.$index.variant_option_ids" => 'Variant group tidak valid.',
            ]);
        }
    }

    private function variantOptionIds(array $item): array
    {
        $ids = $item['variant_option_ids'] ?? [];

        if (!is_array($ids)) {
            $ids = [];
        }

        if (!empty($item['variant_option_id'])) {
            $ids[] = $item['variant_option_id'];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function unitPrice(Products $product, float $extraPrice): float
    {
        $basePrice = (float) (($product->sale_price ?? 0) > 0 ? $product->sale_price : $product->price);

        return max(0, $basePrice + $extraPrice);
    }

    private function paymentStatus(float $paidAmount, float $grandTotal): string
    {
        if ($paidAmount <= 0) {
            return 'Unpaid';
        }

        return $paidAmount < $grandTotal ? 'Partially Paid' : 'Paid';
    }

    private function accountCanAccessOrder(CustomerAccount $account, Order $order): bool
    {
        if ((int) $order->customer_account_id === (int) $account->id) {
            return true;
        }

        return in_array((int) $order->customer_id, $this->accessibleCustomerIds($account), true);
    }

    private function accessibleCustomerIds(CustomerAccount $account): array
    {
        $customerIds = $account->customers()->pluck('customers.id')->map(fn ($id) => (int) $id)->all();

        if ($account->customer_id) {
            $customerIds[] = (int) $account->customer_id;
        }

        return array_values(array_unique($customerIds));
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => optional($order->order_date)->toISOString(),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'total_amount' => (float) $order->total_amount,
            'discount' => (float) $order->discount,
            'grand_total' => (float) $order->grand_total,
            'paid_amount' => (float) $order->paid_amount,
            'remaining_amount' => (float) $order->remaining_amount,
            'mode' => $order->mode,
            'notes' => $order->notes,
            'fulfillment' => $this->orderFulfillment($order),
            'customer' => [
                'id' => $order->customer?->id,
                'name' => $order->customer?->name,
            ],
            'customer_account' => [
                'id' => $order->customerAccount?->id,
                'name' => $order->customerAccount?->name,
                'whatsapp_number' => $order->customerAccount?->whatsapp_number,
            ],
            'shipping' => [
                'address_id' => $order->customer_address_id,
                'business_name' => $order->business_name,
                'address' => $order->shipping_address,
                'google_maps' => $order->google_maps,
            ],
            'items' => $order->orderItems->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_name' => $item->unit_name,
                'quantity' => (int) $item->quantity,
                'mode' => $item->mode,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
                'total_after_discount' => (float) $item->total_after_discount,
            ])->values(),
        ];
    }

    /**
     * Tahap pesanan versi customer, dipakai untuk tab di halaman keranjang:
     *
     * - waiting_verification : masih SO, admin belum mengubahnya jadi invoice.
     * - processing           : sudah jadi invoice — assign waiting list sudah
     *                          dibuat atau barang sedang diantar.
     * - completed            : semua item pesanan sudah selesai diantar.
     */
    private function orderFulfillment(Order $order): array
    {
        $isVerified = $order->status !== 'Sale Order';

        $hasProductionAssign = $order->orderProgress
            ->flatMap
            ->items
            ->flatMap
            ->assigns
            ->isNotEmpty();

        $shipments = $order->deliveryOrders->flatMap->shipments;
        $isFullyDelivered = $isVerified && $this->orderIsFullyDelivered($order, $shipments);

        return [
            'stage' => match (true) {
                !$isVerified => 'waiting_verification',
                $isFullyDelivered => 'completed',
                default => 'processing',
            },
            'is_verified' => $isVerified,
            'has_production_assign' => $hasProductionAssign,
            'is_on_delivery' => !$isFullyDelivered
                && $shipments->contains(fn ($shipment) => $shipment->status !== 'Finished'),
            'is_fully_delivered' => $isFullyDelivered,
        ];
    }

    /**
     * Selesai hanya kalau benar-benar semuanya sudah diantar: tiap baris
     * delivery order terkirim penuh, semua surat jalan sudah diverifikasi, dan
     * tidak ada item pesanan yang belum pernah masuk delivery order.
     */
    private function orderIsFullyDelivered(Order $order, Collection $shipments): bool
    {
        if ($shipments->isEmpty() || $shipments->contains(fn ($shipment) => $shipment->status !== 'Finished')) {
            return false;
        }

        $deliveryItems = $order->deliveryOrders->flatMap->items;

        if ($deliveryItems->isEmpty()) {
            return false;
        }

        $everyRowDelivered = $deliveryItems->every(function ($deliveryItem) {
            $target = (float) $deliveryItem->progress_qty;

            return $target > 0 && (float) $deliveryItem->shipped_qty >= $target;
        });

        if (!$everyRowDelivered) {
            return false;
        }

        $deliveredOrderItemIds = $deliveryItems->pluck('order_item_id')->filter()->unique();

        return $order->orderItems->every(
            fn (OrderItem $orderItem) => $deliveredOrderItemIds->contains($orderItem->id)
        );
    }
}
