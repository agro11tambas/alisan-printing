<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Models\CustomerAddresses;
use App\Models\Customers;
use App\Models\EcommerceProduct;
use App\Models\EcommerceVariantOption;
use App\Models\EcommerceVariantCombination;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemComponent;
use App\Models\Products;
use App\Services\InvoiceNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EcommerceSaleOrderController extends Controller
{
    public function index(Request $request)
    {
        $account = $this->customerAccount($request);
        $customerIds = $this->accessibleCustomerIds($account);

        $orders = Order::with(['orderItems.product', 'customer', 'customerAddress'])
            ->where(function ($query) use ($account, $customerIds) {
                $query->where('customer_account_id', $account->id);

                if (!empty($customerIds)) {
                    $query->orWhereIn('customer_id', $customerIds);
                }
            })
            ->where('status', 'Sale Order')
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
        $account = $this->customerAccount($request);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'shipping' => ['nullable', 'array'],
            'shipping.business_name' => ['nullable', 'string', 'max:255'],
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
            'items.*.mode' => ['nullable', 'in:printing,polosan'],
        ]);

        $customer = $this->resolveCustomer($account, $validated['customer_id'] ?? null);
        $address = $this->resolveAddress(
            $customer,
            $validated['customer_address_id'] ?? null,
            $validated['shipping'] ?? []
        );

        $lineItems = $this->buildLineItems($validated['items']);
        $subTotal = collect($lineItems)->sum('subtotal');
        $discountTotal = 0;
        $grandTotal = $subTotal - $discountTotal;
        $paidAmount = (float) ($validated['paid_amount'] ?? 0);
        $remainingAmount = $grandTotal - $paidAmount;
        $orderDate = Carbon::parse($validated['order_date'] ?? now());
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
                'discount_active' => false,
            ]);

            foreach ($lineItems as $line) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['is_bundle'] ? null : $line['erp_item']->id,
                    'product_bundle_id' => $line['is_bundle'] ? $line['erp_item']->id : null,
                    'product_unit_conversion_id' => null,
                    'product_bundle_unit_conversion_id' => null,
                    'unit_name' => $line['unit_name'],
                    'unit_conversion_value' => 1,
                    'qty_base' => $line['quantity'],
                    'product_name' => $line['erp_item']->name,
                    'satuan' => 'satuan',
                    'quantity' => $line['quantity'],
                    'mode' => $line['mode'],
                    'completed_quantity' => 0,
                    'price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                    'discount_price' => $line['unit_price'],
                    'total_after_discount' => $line['subtotal'],
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

            return $order->load([
                'customer',
                'customerAccount',
                'customerAddress',
                'orderItems.product',
                'orderItems.components.product',
            ]);
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

        $order->load([
            'customer',
            'customerAccount',
            'customerAddress',
            'orderItems.product',
            'orderItems.components.product',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale order retrieved successfully.',
            'data' => $this->orderPayload($order),
        ]);
    }

    private function customerAccount(Request $request): CustomerAccount
    {
        $account = $request->user();

        if (!$account instanceof CustomerAccount || !$account->is_active) {
            abort(403, 'Customer account is not active.');
        }

        return $account;
    }

    private function resolveCustomer(CustomerAccount $account, ?int $customerId): Customers
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
            throw ValidationException::withMessages([
                'customer_id' => 'Customer tidak terhubung dengan akun ini.',
            ]);
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
            $ecommerceProduct = EcommerceProduct::with(['category', 'unit', 'variantGroups'])
                ->whereKey($item['ecommerce_product_id'])
                ->where('is_active', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->first();

            if (!$ecommerceProduct) {
                throw ValidationException::withMessages([
                    "items.$index.ecommerce_product_id" => 'Ecommerce product tidak aktif atau tidak ditemukan.',
                ]);
            }

            $quantity = (int) $item['quantity'];
            $this->validateQuantity($ecommerceProduct, $quantity, $index);

            if (!empty($item['ecommerce_variant_combination_id'])) {
                $combination = EcommerceVariantCombination::with(['productOption.product', 'lidOption.product'])
                    ->whereKey($item['ecommerce_variant_combination_id'])
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

                $bundle = ProductBundle::with(['items.product', 'baseUnit'])
                    ->whereHas('items', fn ($q) => $q->where('role', 'primary')->where('product_id', $primaryProductId))
                    ->whereHas('items', fn ($q) => $q->where('role', 'secondary')->where('product_id', $secondaryProductId))
                    ->first();

                if (!$bundle) {
                    throw ValidationException::withMessages([
                        "items.$index.ecommerce_variant_combination_id" => 'Product Bundle untuk kombinasi ini tidak ditemukan.',
                    ]);
                }

                $unitPrice = (float) (($bundle->sale_price ?? 0) > 0 ? $bundle->sale_price : $bundle->price);

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
                    'variant_option_id' => null,
                    'variant_group' => null,
                    'variant_alias' => null,
                    'quantity' => $quantity,
                    'mode' => $item['mode'] ?? 'printing',
                    'unit_name' => $ecommerceProduct->unit?->name ?? $bundle->baseUnit?->name ?? 'Pcs',
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                    'avg_cost' => 0,
                    'fixed_cost' => 0,
                ];

            } else {
                $optionIds = $this->variantOptionIds($item);

                if (empty($optionIds) && $ecommerceProduct->variantGroups->count() > 0) {
                    throw ValidationException::withMessages([
                        "items.$index.variant_option_ids" => 'Minimal satu variant option wajib dipilih.',
                    ]);
                }

                if (!empty($optionIds)) {
                    $options = EcommerceVariantOption::with(['group', 'product.baseUnit'])
                        ->whereIn('id', $optionIds)
                        ->where('is_active', true)
                        ->get();

                    if ($options->count() !== count($optionIds)) {
                        throw ValidationException::withMessages([
                            "items.$index.variant_option_ids" => 'Salah satu variant option tidak aktif atau tidak ditemukan.',
                        ]);
                    }

                    $this->validateVariantOptions($ecommerceProduct, $options, $index);

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

                        $unitPrice = $this->unitPrice($product, (float) $option->extra_price);

                        $lineItems[] = [
                            'is_bundle' => false,
                            'erp_item' => $product,
                            'bundle_items' => [],
                            'ecommerce_product_id' => $ecommerceProduct->id,
                            'ecommerce_product_title' => $ecommerceProduct->title,
                            'variant_option_id' => $option->id,
                            'variant_group' => $option->group?->name,
                            'variant_alias' => $option->alias,
                            'quantity' => $quantity,
                            'mode' => $item['mode'] ?? 'printing',
                            'unit_name' => $ecommerceProduct->unit?->name
                                ?? $product->baseUnit?->name
                                ?? 'Pcs',
                            'unit_price' => $unitPrice,
                            'subtotal' => $unitPrice * $quantity,
                            'avg_cost' => (float) ($product->avg_cost ?? 0),
                            'fixed_cost' => (float) ($product->fixed_cost ?? 0),
                        ];
                    }
                }
            }
        }

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

        if ($requiredGroupIds->diff($selectedGroupIds)->isNotEmpty() || $selectedGroupIds->diff($requiredGroupIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                "items.$index.variant_option_ids" => 'Semua variant group wajib dipilih.',
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
}
