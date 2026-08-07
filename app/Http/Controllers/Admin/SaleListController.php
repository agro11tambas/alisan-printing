<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SaleListExport;
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
use App\Models\DefectProduct;
use App\Models\DeliveryList;
use App\Models\DeliveryListItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\FinancialReport;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\Invoice;
use App\Models\OrderEditHistory;
use App\Models\OrderItemComponent;
use App\Models\OrderProgress;
use App\Models\OrderProgressAssign;
use App\Models\OrderProgressAssignBatch;
use App\Models\OrderProgressHistory;
use App\Models\PriceMode;
use App\Models\OrderProgressItem;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\ProductionStock;
use App\Models\PurchaseItem;
use App\Models\SaleReturn;
use App\Services\InvoiceNumberService;
use App\Services\ProductCostService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SaleListController extends Controller
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
                    'polosan' => 'bg-soft-primary text-primary',
                    default => 'bg-soft-dark text-dark',
                };

                return '<span class="badge ' . $badgeClass . '">' . e(ucfirst($mode)) . '</span>';
            })->implode('') .
            '</div>';
    }

    private function monthlyOrderGroupKeyForOrder(Order $order): string
    {
        $businessName = $order->customerAddress?->business_name;
        $month = Carbon::parse($order->order_date)->format('Y-m');

        return implode('|', [
            $order->customer_id,
            $month,
            $businessName ?: '*',
        ]);
    }

    private function buildMonthlyOrderSequences(Collection $pageOrders): Collection
    {
        if ($pageOrders->isEmpty()) {
            return collect();
        }

        $groups = $pageOrders
            ->mapWithKeys(fn(Order $order) => [
                $this->monthlyOrderGroupKeyForOrder($order) => [
                    'customer_id' => $order->customer_id,
                    'business_name' => $order->customerAddress?->business_name,
                    'month' => Carbon::parse($order->order_date)->format('Y-m'),
                ],
            ]);

        $dates = $pageOrders->map(fn(Order $order) => Carbon::parse($order->order_date));
        $rangeStart = $dates->min()->copy()->startOfMonth();
        $rangeEnd = $dates->max()->copy()->endOfMonth();

        $candidates = Order::query()
            ->with('customerAddress:id,business_name')
            ->where('status', 'sale list')
            ->whereIn('customer_id', $groups->pluck('customer_id')->unique())
            ->whereBetween('order_date', [$rangeStart, $rangeEnd])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get(['id', 'customer_id', 'customer_address_id', 'order_date']);

        return $groups->map(function (array $group) use ($candidates) {
            $matches = $candidates->filter(function (Order $candidate) use ($group) {
                if ((int) $candidate->customer_id !== (int) $group['customer_id']) {
                    return false;
                }

                if (Carbon::parse($candidate->order_date)->format('Y-m') !== $group['month']) {
                    return false;
                }

                return !$group['business_name']
                    || $candidate->customerAddress?->business_name === $group['business_name'];
            })->values();

            $total = $matches->count();

            return $matches->mapWithKeys(fn(Order $candidate, int $index) => [
                $candidate->id => [
                    'position' => $index + 1,
                    'total' => $total,
                ],
            ]);
        });
    }

    public function getSaleList()
    {
        $order_number = Order::first();
        $transactionTypes = Account::where('name', 'Order')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $defaultAccount = Account::where('is_default', true)->first();

        return view('erp.pages.sales.sale-list.sale-list', compact('order_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'defaultAccount'));
    }

    /**
     * Filter tanggal, payment status, sorting due date, dan pencarian keyword.
     * Dipakai bersama oleh listing dan export Excel supaya hasilnya identik.
     */
    private function applySaleListFilters($orders, Request $request)
    {
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
            } else if ($request->payment_status === 'Unpaid') {
                $orders->whereIn('payment_status', ['Unpaid', 'Partially Paid']);
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

        return $orders;
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        $orders = Order::query()
            ->where('status', 'sale list')
            ->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc');

        if (in_array($user->role, ['Sales'])) {
            $orders->where('user_id', $user->id);
        }

        if ($request->filled('show_edited') && $request->show_edited == 1) {
            $orders->where('status_edited', 1);
        }

        $this->applySaleListFilters($orders, $request);

        $filename = 'sale-list-' . Carbon::now()->format('Ymd-His') . '.xlsx';

        return (new SaleListExport($orders))->download($filename);
    }

    public function dataSaleList(Request $request)
    {
        $user = Auth::user();
        $length = (int) $request->input('length', 50);
        $start = (int) $request->input('start', 0);

        $orders = Order::with([
            'customer',
            'customerAccount',
            'user',
            'customerAddress',
            'saleReturns',
            // Dipakai accessor is_fully_returned dan has_delivery_list di
            // partial action-button. Tanpa ini keduanya query per baris.
            'saleReturns.items:id,sale_return_id,quantity',
            'deliveryOrders.shipments:id,delivery_order_id,status',
            'orderItems.product',
            'orderItems.productBundle.items.product',
            'deliveryOrders.items.deliveryListItems.shipment',
            'deliveryOrders.items.deliveryListItems.deliveryOrderItem',
        ])
            ->where('status', 'sale list')
            ->orderBy('order_date', 'desc');

        if (in_array($user->role, ['Sales'])) {
            $orders->where('user_id', $user->id);
        }

        if ($request->filled('show_edited') && $request->show_edited == 1) {
            $orders->where('status_edited', 1);
        }

        $this->applySaleListFilters($orders, $request);

        // 🔹 Satu query saja, tanpa count() terpisah
        [$data, $hasMore] = $this->lazyLoadPage($orders, $start, $length);
        $monthlyOrderSequences = $this->buildMonthlyOrderSequences($data);

        return response()->json([
            'data' => $data->map(function ($order) use ($monthlyOrderSequences) {
                $orderCreatedAt = Carbon::parse($order->order_date)->format('d M y H:i');
                $date = Carbon::parse($order->order_date)->format('j M y H:i');
                $dueDate = $order->due_date ? Carbon::parse($order->due_date)->format('j M y') : '-';

                $editedBadge = $order->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                $returnBadge = $order->saleReturns->isNotEmpty()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Sale Return</span></div>'
                    : '';

                $orderNumber = $returnBadge . '
                <div>
                    <div>' . e($order->order_number) . $editedBadge . '</div>
                    <small class="text-muted">' . $orderCreatedAt . '</small>,
                    <small class="text-danger">Due: ' . $dueDate . '</small>
                </div>';

                $status = strtolower($order->payment_status);
                $badge = match ($status) {
                    'paid' => 'bg-soft-success text-success',
                    'unpaid' => 'bg-soft-dark text-dark',
                    'overpaid' => 'bg-soft-primary text-primary',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };

                $verifiedIcon = '';
                if ($order->verified) {
                    $verifiedIcon = ' <i class="fa fa-check-circle text-success ms-1" title="Verified"></i>';
                }

                $isOverdue = false;

                if ($order->due_date) {
                    $due = Carbon::parse($order->due_date)->endOfDay();
                    $today = Carbon::now();

                    // overdue = due_date lewat dan belum Paid/Overpaid
                    if ($today->gt($due) && !in_array($order->payment_status, ['Paid', 'Overpaid'])) {
                        $isOverdue = true;
                    }
                }

                $paymentStatus = '
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1">
                            <div class="badge ' . $badge . '">' . ucfirst($status) . '</div>'
                    . $verifiedIcon . '
                        </div>';

                if ($isOverdue) {
                    $paymentStatus .= '
                    <div>
                        <span class="badge bg-soft-danger text-danger">Overdue</span>
                    </div>';
                }

                $paymentStatus .= '</div>';

                $statusBadge = '<div class="badge bg-soft-dark text-dark">' . ucfirst($order->status) . '</div>';

                $modeBadge = $this->renderOrderItemModes($order);


                $items = $order->orderItems
                    ->map(function ($item) use ($order) {
                        $displayQty = $item->quantity;
                        $unitConversionValue = max((float) ($item->unit_conversion_value ?? 1), 1);
                        $requiredQtyBase = (float) ($item->qty_base ?: ($item->quantity * $unitConversionValue));

                        $deliveryData = $order->deliveryOrders
                            ->flatMap(fn($deliveryOrder) => $deliveryOrder->items)
                            ->where('order_item_id', $item->id);

                        $deliveryListItems = $deliveryData
                            ->flatMap(fn($deliveryOrderItem) => $deliveryOrderItem->deliveryListItems);

                        // ── SATUAN ──
                        if ($item->product) {
                            $progressQty   = $deliveryData->sum('progress_qty');
                            $readyQty      = $deliveryData->sum('ready_qty');
                            $shippedQty    = $deliveryData->sum('shipped_qty');
                            $deliveredQty  = $deliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')->sum('shipped_quantity');
                            $onDeliveryQty = $deliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')->sum('shipped_quantity');

                            return [[
                                'name'              => e($item->product->name) . ' <span class="badge bg-soft-success text-success">Satuan</span>',
                                'sku'               => e($item->product->sku),
                                'mode'              => $item->mode ?? '-',
                                'unit_name'         => $item->unit_name ?? '-',
                                'qty' => number_format($displayQty, 0, ',', '.') . ' ' . $item->unit_name,
                                'price'             => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                                'progress_qty'      => number_format($progressQty, 0, ',', '.'),
                                'ready_qty'         => number_format($readyQty, 0, ',', '.'),
                                'shipped_qty'       => number_format($shippedQty, 0, ',', '.'),
                                'delivered'         => number_format($deliveredQty, 0, ',', '.'),
                                'on_delivery'       => number_format($onDeliveryQty, 0, ',', '.'),
                                'raw_progress_qty'  => $progressQty,
                                'raw_delivered_qty' => $deliveredQty,
                                'raw_qty'           => $requiredQtyBase,
                                'is_bundle_header'  => false,
                            ]];
                        }

                        // ── BUNDLE ──
                        if ($item->productBundle) {
                            $bundleNames = $item->productBundle->items
                                ->map(fn($b) => $b->product->name ?? '-')
                                ->implode(' + ');

                            $bundleChildren = $item->productBundle->items->map(function ($bundleItem) use ($item, $deliveryData, $deliveryListItems) {
                                $productId = $bundleItem->product->id ?? null;

                                $productDeliveryData      = $deliveryData->filter(fn($d) => $d->product_id == $productId);
                                $productDeliveryListItems = $deliveryListItems->filter(fn($d) => $d->deliveryOrderItem && $d->deliveryOrderItem->product_id == $productId);

                                $readyQty      = $productDeliveryData->sum('ready_qty');
                                $deliveredQty  = $productDeliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')->sum('shipped_quantity');
                                $onDeliveryQty = $productDeliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')->sum('shipped_quantity');

                                return [
                                    'ready_qty'   => number_format($readyQty, 0, ',', '.'),
                                    'delivered'   => number_format($deliveredQty, 0, ',', '.'),
                                    'on_delivery' => number_format($onDeliveryQty, 0, ',', '.'),
                                ];
                            })->values()->toArray();

                            $totalDelivered = collect($bundleChildren)->sum(function ($child) {
                                return (int) str_replace('.', '', $child['delivered']);
                            });
                            $requiredBundleQty = $item->productBundle->items->sum(
                                fn($bundleItem) => $requiredQtyBase * (float) ($bundleItem->quantity ?? 1)
                            );

                            return [[
                                'name'              => e($bundleNames) . ' <span class="badge bg-soft-primary text-primary">Bundle</span>',
                                'sku'               => e($item->productBundle->sku ?? '-'),
                                'mode'              => $item->mode ?? '-',
                                'unit_name'         => $item->unit_name ?? '-',
                                'qty' => number_format($displayQty, 0, ',', '.') . ' ' . $item->unit_name,
                                'price'             => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                                'progress_qty'      => '-',
                                'ready_qty'         => '-',
                                'shipped_qty'       => '-',
                                'delivered'         => '-',
                                'on_delivery'       => '-',
                                'raw_progress_qty'  => 0,
                                'raw_delivered_qty' => $totalDelivered,
                                'raw_qty'           => $requiredBundleQty,
                                'is_bundle_header'  => true,
                                'bundle_children'   => $bundleChildren,
                            ]];
                        }

                        // ── FALLBACK ──
                        return [[
                            'name'              => '-',
                            'sku'               => '-',
                            'qty'               => '0',
                            'price'             => '0',
                            'progress_qty'      => '0',
                            'ready_qty'         => '0',
                            'shipped_qty'       => '0',
                            'delivered'         => '0',
                            'on_delivery'       => '0',
                            'raw_progress_qty'  => 0,
                            'raw_delivered_qty' => 0,
                            'raw_qty'           => 0,
                            'is_bundle_header'  => false,
                        ]];
                    })
                    ->flatten(1)
                    ->values();



                $isCompleted = $items->isNotEmpty() && $items->every(function ($i) {
                    $requiredQty = (float) ($i['raw_qty'] ?? 0);

                    return $requiredQty > 0
                        && (float) $i['raw_delivered_qty'] === $requiredQty;
                });

                // $businessName = e($order->customerAddress->business_name ?? '-');

                $completeIcon = $isCompleted
                    ? ' <i class="fa fa-check-circle text-success ms-1"></i>'
                    : '';

                $businessName = $order->customerAddress->business_name ?? null;
                $customer = $order->customer->name ?? null;
                $customerAccount = $order->customerAccount->name ?? null;
                $customerAccountNumber = $order->order_whatsapp_number;

                $sequence = $monthlyOrderSequences
                    ->get($this->monthlyOrderGroupKeyForOrder($order), collect())
                    ->get($order->id);

                $sequenceLabel = $sequence
                    ? "({$sequence['position']}/{$sequence['total']})"
                    : '';

                return [
                    'id' => $order->id,
                    'order_number' => $orderNumber,
                    'order_date_raw' => Carbon::parse($order->order_date)->format('Y-m-d H:i:s'),
                    'order_date' => $date,

                    'customer' => '
                        <div style="white-space: normal; word-break: break-word; max-width:230px;">

                            <div class="d-flex align-items-center fw-semibold">
                                ' . ($completeIcon ? '<i class="fa fa-check-circle text-success me-1"></i>' : '') . '

                                ' . $customer . '

                                <span class="ms-1 text-primary fw-bold">
                                    ' . $sequenceLabel . '
                                </span>
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
                                ' . ($completeIcon ? '<i class="fa fa-check-circle text-success me-1"></i>' : '') . '

                                <div>
                                    <small class="text-muted">' . $customer . '</small>
                                    <small class="text-muted">' . $customerAccount . ' - ' . $customerAccountNumber . '</small>
                                    <small class="text-muted">' . $businessName . '</small>
                                </div>

                                <div style="width:50px;">
                                    <small class="ms-1 text-primary fw-bold">
                                        ' . $sequenceLabel . '
                                    </small>
                                </div>
                            </div>                        
                        </div>
                    ',
                    'total_amount' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'discount' => '<span class="text-warning">Rp ' . number_format($order->discount, 0, ',', '.') . '</span>',
                    'grand_total' => '<span class="text-primary fw-semibold">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>',
                    'paid_amount' => '
                        <div class="text-success fw-semibold">Rp ' . number_format($order->paid_amount, 0, ',', '.') . '</div>'
                        . ($order->remaining_amount > 0
                            ? '<small class="text-danger fw-semibold">Remaining: Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</small>'
                            : ''
                        ),
                    'remaining_amount' => '<span class="text-danger">Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</span>',
                    'payment_status' => $paymentStatus,
                    'status' => $statusBadge,
                    'payment_method' => e($order->payment_method ?? '-'),
                    'products' => $items,
                    'notes' => '
                        <div style="white-space: normal; word-break: break-word; max-width: 220px;">
                            ' . e($order->notes ?? '-') . '
                        </div>
                    ',
                    'whatsapp' => '
                        <a href="https://wa.me/' . (
                        function ($phone) {
                            $num = preg_replace('/\D/', '', $phone ?? '');
                            if (strpos($num, '0') === 0) $num = '62' . substr($num, 1);
                            if (strpos($num, '62') !== 0) {
                                if (strpos($num, '8') === 0) $num = '62' . $num;
                            }
                            return $num;
                        }
                    )($order->order_whatsapp_number) . '"
                            target="_blank"
                            class="btn btn-success btn-sm"
                            style="padding:6px 10px;">
                            Chat
                        </a>
                    ',

                    'created_at' => $orderCreatedAt,
                    'mode' => $modeBadge,
                    'user' => e($order->user?->name ?? '-'),
                    'action' => view('erp.pages.sales.sale-list.partials.action-button', compact('order'))->render(),
                    'action_mobile' => view('erp.pages.sales.sale-list.partials.action-button-mobile', compact('order'))->render(),
                ];
            })->values(),
            'has_more' => $hasMore,
        ]);
    }

    public function dataSaleListEdited(Request $request)
    {
        $user = Auth::user();
        $length = (int) $request->input('length', 50);
        $start = (int) $request->input('start', 0);

        $orders = Order::with([
            'customer',
            'customerAccount',
            'user',
            'customerAddress',
            'saleReturns',
            // Dipakai accessor is_fully_returned dan has_delivery_list di
            // partial action-button. Tanpa ini keduanya query per baris.
            'saleReturns.items:id,sale_return_id,quantity',
            'deliveryOrders.shipments:id,delivery_order_id,status',
            'orderItems.product',
            'orderItems.productBundle.items.product',
            'deliveryOrders.items.deliveryListItems.shipment',
            'deliveryOrders.items.deliveryListItems.deliveryOrderItem',
        ])
            ->where('status', 'sale list')
            ->where('status_edited', 1)
            ->orderBy('order_date', 'desc');

        if (in_array($user->role, ['Sales'])) {
            $orders->where('user_id', $user->id);
        }

        $this->applySaleListFilters($orders, $request);

        // 🔹 Satu query saja, tanpa count() terpisah
        [$data, $hasMore] = $this->lazyLoadPage($orders, $start, $length);
        $monthlyOrderSequences = $this->buildMonthlyOrderSequences($data);

        return response()->json([
            'data' => $data->map(function ($order) use ($monthlyOrderSequences) {
                $orderCreatedAt = Carbon::parse($order->order_date)->format('d M y H:i');
                $date = Carbon::parse($order->order_date)->format('j M y H:i');
                $dueDate = $order->due_date ? Carbon::parse($order->due_date)->format('j M y') : '-';

                $editedBadge = $order->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                $returnBadge = $order->saleReturns->isNotEmpty()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Sale Return</span></div>'
                    : '';

                $orderNumber = $returnBadge . '
                <div>
                    <div>' . e($order->order_number) . $editedBadge . '</div>
                    <small class="text-muted">' . $orderCreatedAt . '</small>,
                    <small class="text-danger">Due: ' . $dueDate . '</small>
                </div>';

                $status = strtolower($order->payment_status);
                $badge = match ($status) {
                    'paid' => 'bg-soft-success text-success',
                    'unpaid' => 'bg-soft-dark text-dark',
                    'overpaid' => 'bg-soft-primary text-primary',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };

                $verifiedIcon = '';
                if ($order->verified) {
                    $verifiedIcon = ' <i class="fa fa-check-circle text-success ms-1" title="Verified"></i>';
                }

                $isOverdue = false;

                if ($order->due_date) {
                    $due = Carbon::parse($order->due_date)->endOfDay();
                    $today = Carbon::now();

                    // overdue = due_date lewat dan belum Paid/Overpaid
                    if ($today->gt($due) && !in_array($order->payment_status, ['Paid', 'Overpaid'])) {
                        $isOverdue = true;
                    }
                }

                $paymentStatus = '
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1">
                            <div class="badge ' . $badge . '">' . ucfirst($status) . '</div>'
                    . $verifiedIcon . '
                        </div>';

                if ($isOverdue) {
                    $paymentStatus .= '
                    <div>
                        <span class="badge bg-soft-danger text-danger">Overdue</span>
                    </div>';
                }

                $paymentStatus .= '</div>';


                $statusBadge = '<div class="badge bg-soft-dark text-dark">' . ucfirst($order->status) . '</div>';

                $modeBadge = $this->renderOrderItemModes($order);


                $items = $order->orderItems
                    ->map(function ($item) use ($order) {
                        $unitConversionValue = max((float) ($item->unit_conversion_value ?? 1), 1);
                        $requiredQtyBase = (float) ($item->qty_base ?: ($item->quantity * $unitConversionValue));
                        $displayQty = $requiredQtyBase;

                        $deliveryData = $order->deliveryOrders
                            ->flatMap(fn($deliveryOrder) => $deliveryOrder->items)
                            ->where('order_item_id', $item->id);

                        $deliveryListItems = $deliveryData
                            ->flatMap(fn($deliveryOrderItem) => $deliveryOrderItem->deliveryListItems);

                        // ── SATUAN ──
                        if ($item->product) {
                            $progressQty   = $deliveryData->sum('progress_qty');
                            $readyQty      = $deliveryData->sum('ready_qty');
                            $shippedQty    = $deliveryData->sum('shipped_qty');
                            $deliveredQty  = $deliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')->sum('shipped_quantity');
                            $onDeliveryQty = $deliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')->sum('shipped_quantity');

                            return [[
                                'name'              => e($item->product->name) . ' <span class="badge bg-soft-success text-success">Satuan</span>',
                                'sku'               => e($item->product->sku),
                                'qty' => number_format($displayQty, 0, ',', '.'),
                                'price'             => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                                'progress_qty'      => number_format($progressQty, 0, ',', '.'),
                                'ready_qty'         => number_format($readyQty, 0, ',', '.'),
                                'shipped_qty'       => number_format($shippedQty, 0, ',', '.'),
                                'delivered'         => number_format($deliveredQty, 0, ',', '.'),
                                'on_delivery'       => number_format($onDeliveryQty, 0, ',', '.'),
                                'raw_progress_qty'  => $progressQty,
                                'raw_delivered_qty' => $deliveredQty,
                                'raw_qty'           => $requiredQtyBase,
                                'is_bundle_header'  => false,
                            ]];
                        }

                        // ── BUNDLE ──
                        if ($item->productBundle) {
                            $bundleNames = $item->productBundle->items
                                ->map(fn($b) => $b->product->name ?? '-')
                                ->implode(' + ');

                            $bundleChildren = $item->productBundle->items->map(function ($bundleItem) use ($item, $deliveryData, $deliveryListItems) {
                                $productId = $bundleItem->product->id ?? null;

                                $productDeliveryData      = $deliveryData->filter(fn($d) => $d->product_id == $productId);
                                $productDeliveryListItems = $deliveryListItems->filter(fn($d) => $d->deliveryOrderItem && $d->deliveryOrderItem->product_id == $productId);

                                $readyQty      = $productDeliveryData->sum('ready_qty');
                                $deliveredQty  = $productDeliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status === 'Finished')->sum('shipped_quantity');
                                $onDeliveryQty = $productDeliveryListItems->filter(fn($i) => $i->shipment && $i->shipment->status !== 'Finished')->sum('shipped_quantity');

                                return [
                                    'ready_qty'   => number_format($readyQty, 0, ',', '.'),
                                    'delivered'   => number_format($deliveredQty, 0, ',', '.'),
                                    'on_delivery' => number_format($onDeliveryQty, 0, ',', '.'),
                                ];
                            })->values()->toArray();

                            $totalDelivered = collect($bundleChildren)->sum(function ($child) {
                                return (int) str_replace('.', '', $child['delivered']);
                            });
                            $requiredBundleQty = $item->productBundle->items->sum(
                                fn($bundleItem) => $requiredQtyBase * (float) ($bundleItem->quantity ?? 1)
                            );

                            return [[
                                'name'              => e($bundleNames) . ' <span class="badge bg-soft-primary text-primary">Bundle</span>',
                                'sku'               => e($item->productBundle->sku ?? '-'),
                                'qty' => number_format($displayQty, 0, ',', '.'),
                                'price'             => number_format($item->discount_price ?? $item->price ?? 0, 0, ',', '.'),
                                'progress_qty'      => '-',
                                'ready_qty'         => '-',
                                'shipped_qty'       => '-',
                                'delivered'         => '-',
                                'on_delivery'       => '-',
                                'raw_progress_qty'  => 0,
                                'raw_delivered_qty' => $totalDelivered,
                                'raw_qty'           => $requiredBundleQty,
                                'is_bundle_header'  => true,
                                'bundle_children'   => $bundleChildren,
                            ]];
                        }

                        // ── FALLBACK ──
                        return [[
                            'name'              => '-',
                            'sku'               => '-',
                            'qty'               => '0',
                            'price'             => '0',
                            'progress_qty'      => '0',
                            'ready_qty'         => '0',
                            'shipped_qty'       => '0',
                            'delivered'         => '0',
                            'on_delivery'       => '0',
                            'raw_progress_qty'  => 0,
                            'raw_delivered_qty' => 0,
                            'raw_qty'           => 0,
                            'is_bundle_header'  => false,
                        ]];
                    })
                    ->flatten(1)
                    ->values();

                $isCompleted = $items->isNotEmpty() && $items->every(function ($i) {
                    $requiredQty = (float) ($i['raw_qty'] ?? 0);

                    return $requiredQty > 0
                        && (float) $i['raw_delivered_qty'] === $requiredQty;
                });

                $businessName = e($order->customerAddress->business_name ?? '-');

                $completeIcon = $isCompleted
                    ? ' <i class="fa fa-check-circle text-success ms-1"></i>'
                    : '';

                $businessName = $order->customerAddress->business_name ?? null;
                $customer = $order->customer->name ?? null;
                $customerAccount = $order->customerAccount->name ?? null;
                $customerAccountNumber = $order->order_whatsapp_number;

                $sequence = $monthlyOrderSequences
                    ->get($this->monthlyOrderGroupKeyForOrder($order), collect())
                    ->get($order->id);

                $sequenceLabel = $sequence
                    ? "({$sequence['position']}/{$sequence['total']})"
                    : '';

                return [
                    'id' => $order->id,
                    'order_number' => $orderNumber,
                    'order_date_raw' => Carbon::parse($order->order_date)->format('Y-m-d H:i:s'),
                    'order_date' => $date,

                    'customer' => '
                        <div style="white-space: normal; word-break: break-word; max-width:230px;">

                            <div class="d-flex align-items-center fw-semibold">
                                ' . ($completeIcon ? '<i class="fa fa-check-circle text-success me-1"></i>' : '') . '

                                ' . $customer . '

                                <span class="ms-1 text-primary fw-bold">
                                    ' . $sequenceLabel . '
                                </span>
                            </div>

                        <small class="text-muted">' . $customerAccount . ' - ' . $customerAccountNumber . '</small>
                        <small class="text-muted">' . $businessName . '</small>

                        </div>
                    ',
                    'customer_mobile' => '
                        <div style="white-space: normal; word-break: break-word; max-width:180px;">

                            <div class="d-flex fw-semibold">
                                ' . ($completeIcon ? '<i class="fa fa-check-circle text-success me-1"></i>' : '') . '

                                <div>
                                    <small class="text-muted">' . $customer . '</small>
                                    <small class="text-muted">' . $customerAccount . ' - ' . $customerAccountNumber . '</small>
                                    <small class="text-muted">' . $businessName . '</small>
                                </div>

                                <div style="width:50px;">
                                    <small class="ms-1 text-primary fw-bold">
                                        ' . $sequenceLabel . '
                                    </small>
                                </div>
                            </div>                        
                        </div>
                    ',
                    'total_amount' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'discount' => '<span class="text-warning">Rp ' . number_format($order->discount, 0, ',', '.') . '</span>',
                    'grand_total' => '<span class="text-primary fw-semibold">Rp ' . number_format($order->grand_total, 0, ',', '.') . '</span>',
                    'paid_amount' => '
                        <div class="text-success fw-semibold">Rp ' . number_format($order->paid_amount, 0, ',', '.') . '</div>'
                        . ($order->remaining_amount > 0
                            ? '<small class="text-danger fw-semibold">Remaining: Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</small>'
                            : ''
                        ),
                    'remaining_amount' => '<span class="text-danger">Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</span>',
                    'payment_status' => $paymentStatus,
                    'status' => $statusBadge,
                    'payment_method' => e($order->payment_method ?? '-'),
                    'products' => $items,
                    'notes' => '
                        <div style="white-space: normal; word-break: break-word; max-width: 220px;">
                            ' . e($order->notes ?? '-') . '
                        </div>
                    ',
                    'whatsapp' => '
                        <a href="https://wa.me/' . (
                        function ($phone) {
                            $num = preg_replace('/\D/', '', $phone ?? '');
                            if (strpos($num, '0') === 0) $num = '62' . substr($num, 1);
                            if (strpos($num, '62') !== 0) {
                                if (strpos($num, '8') === 0) $num = '62' . $num;
                            }
                            return $num;
                        }
                    )($order->order_whatsapp_number) . '"
                            target="_blank"
                            class="btn btn-success btn-sm"
                            style="padding:6px 10px;">
                            Chat
                        </a>
                    ',

                    'created_at' => $orderCreatedAt,
                    'mode' => $modeBadge,
                    'user' => e($order->user?->name ?? '-'),
                    'action' => view('erp.pages.sales.sale-list.partials.action-button', compact('order'))->render(),
                    'action_mobile' => view('erp.pages.sales.sale-list.partials.action-button-mobile', compact('order'))->render(),
                ];
            })->values(),
            'has_more' => $hasMore,
        ]);
    }

    public function dataDeletedSaleList(Request $request)
    {
        $length = (int) $request->input('length', 50);
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

        // 🔹 Satu query saja, tanpa count() terpisah
        [$data, $hasMore] = $this->lazyLoadPage($orders, $start, $length);

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
                        'deliveryListItems.deliveryOrder'
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
            'has_more' => $hasMore,
        ]);
    }


    public function create()
    {
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
                'unitConversions.prices:id,product_unit_conversion_id,price_mode_id,fixed_cost,margin,sale_price',
                'unitConversions.prices.priceMode:id,name,slug',
            ])
            ->orderBy('name', 'asc')
            ->get();

        $productsJson = $products->map(function ($product) {
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
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];
        })->toArray();
        // The form only needs bundle membership, not the full pricing/discount graph.
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

        $productBundlesJson = $productBundles->map(function ($bundle) {
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
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
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
        $priceModes = PriceMode::active()->orderBy('sort_order')->orderBy('name')->get();
        $modeDiscounts = Discount::modeDiscountsPayload();
        return view('erp.pages.sales.sale-list.create-order', compact(
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
            'order_date'           => 'required|date_format:Y-m-d\TH:i',
            'due_date_option'      => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'      => 'nullable|date',
            'customer_id'          => 'required|exists:customers,id',
            'customer_account_id'  => 'required|exists:customer_accounts,id',
            'customer_address_id'  => 'required|exists:customer_addresses,id',
            'notes'                => 'nullable|string',
            'product_type'         => 'required|array',
            'product_type.*'       => 'in:satuan,bundle',
            'product'              => 'required|array',
            'product.*'            => 'required',
            'qty'                  => 'required|array',
            'qty.*'                => 'numeric|min:1',
            'mode'   => 'required|array',
            'mode.*' => ['required', Rule::exists('price_modes', 'slug')->where('is_active', true)],
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
                    $dueDate = null;
            }

            $orderNumber = InvoiceNumberService::generate('INV', $orderDate);

            $addressModel = CustomerAddresses::find($request->customer_address_id);


            $order = Order::create([
                'user_id'            => Auth::id(),
                'customer_id'        => $request->customer_id,
                'customer_account_id' => $request->customer_account_id,
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
                'mode' => 'mixed',
                'total_amount'     => $request->sub_total,
                'grand_total'      => $request->total_amount,
                'discount'         => $request->total_discount,
                'remaining_amount' => $remainingAmount,
                'discount_active' => (int) $request->input('discount_active_hidden', 1),
            ]);


            foreach ($request->product as $index => $productInputId) {
                $type = strtolower($request->product_type[$index]);
                $qty  = (float) $request->qty[$index];

                $itemMode = $request->mode[$index];

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
                    $product = Products::findOrFail($productInputId);
                    $inventoryStock = InventoryStock::where('product_id', $product->id)->first();
                    $avgCost = $product?->avg_cost ?? 0;
                    $fixedCostAtSale = $product?->fixed_cost ?? 0;

                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => $product->id,
                        'product_bundle_id'    => null,
                        'product_unit_conversion_id' => $unitConversionId,
                        'product_bundle_unit_conversion_id' => null,
                        'unit_name'                  => $unitName,
                        'unit_conversion_value'      => $unitConversionValue,
                        'qty_base'                   => $qtyBase,
                        'status'               => $paymentMethod,
                        'product_name'         => $product->name,
                        'satuan'               => 'satuan',
                        'mode'                 => $itemMode,
                        'quantity'             => $qty,
                        'completed_quantity'   => 0,
                        'stock_out'            => 0,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);


                    OrderItemComponent::create([
                        'order_item_id'    => $orderItem->id,
                        'product_id'       => $product->id,
                        'qty'                 => $qtyBase,
                        'avg_cost_at_sale' => $avgCost,
                        'fixed_cost_at_sale' => $fixedCostAtSale,
                        'total_cost'          => $avgCost * $qtyBase,
                        'total_fixed_cost'    => $fixedCostAtSale * $qtyBase,
                    ]);


                    $inventoryStock = InventoryStock::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'inventory_warehouse_id' => $warehouseId,
                        ],
                        []
                    );

                    $inventoryStock->decrement('stock_after_sales', $qty);

                    // if ($itemMode === 'polosan') {
                    //     $inventoryStock->decrement('inventory_stock', $qty);
                    // }
                } elseif ($type === 'bundle') {
                    $bundle = ProductBundle::with('items.product')->findOrFail($productInputId);


                    $bundleProductNames = $bundle->items->map(function ($item) {
                        return $item->product->name ?? '-';
                    })->implode(' + ');

                    $orderItem = OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => null,
                        'product_bundle_id'    => $bundle->id,
                        'product_unit_conversion_id' => null,
                        'product_bundle_unit_conversion_id' => $unitConversionId,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,
                        'status'               => $paymentMethod,
                        'product_name'         => $bundleProductNames,
                        'satuan'               => 'bundle',
                        'mode'                 => $itemMode,
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

                        if (!$component) {
                            continue;
                        }

                        $avgCost = $component?->avg_cost ?? 0;
                        $fixedCostAtSale = $component?->fixed_cost ?? 0;

                        $totalQty = $qty * ($bundleItem->quantity ?? 1);

                        OrderItemComponent::create([
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $component->id,
                            'qty'                 => $totalQty,
                            'avg_cost_at_sale'    => $avgCost,
                            'fixed_cost_at_sale'  => $fixedCostAtSale,
                            'total_cost'          => $avgCost * $totalQty,
                            'total_fixed_cost'    => $fixedCostAtSale * $totalQty,
                        ]);

                        $componentInventoryStock = InventoryStock::updateOrCreate(
                            [
                                'product_id' => $component->id,
                                'inventory_warehouse_id' => $warehouseId,
                            ],
                            []
                        );

                        $componentInventoryStock->decrement('stock_after_sales', $totalQty);

                        // if ($itemMode === 'polosan') {
                        //     $componentInventoryStock->decrement('inventory_stock', $totalQty);
                        // }
                    }
                }
            }

            $orderItems = $order->orderItems()
                ->with(['productBundle.items.product'])
                ->get();

            $design = Design::create([
                'order_id'            => $order->id,
                'design_number'       => $orderNumber,
                'date'                => now()->format('Y-m-d'),
                'status'              => 'Pending',
                'notes'               => null,
                'verification_status' => 'pending',
                'verified_by'         => null,
                'verified_at'         => null,
            ]);

            foreach ($orderItems as $orderItem) {
                $qtyInput = $orderItem->quantity;

                if ($orderItem->satuan === 'satuan') {
                    if (!$orderItem->product_id) {
                        continue;
                    }

                    DesignItem::create([
                        'design_id'           => $design->id,
                        'order_item_id'       => $orderItem->id,
                        'product_id'          => $orderItem->product_id,
                        'quantity'            => $qtyInput,
                        'completed_quantity'  => 0,

                        'product_unit_conversion_id' => $orderItem->product_unit_conversion_id,
                        'unit_name' => $orderItem->unit_name,
                        'unit_conversion_value' => $orderItem->unit_conversion_value,

                        'design_file'         => null,
                        'preview_image'       => null,
                        'verification_status' => 'pending',
                    ]);

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

                        $componentQty = $qtyInput * ($bundleItem->quantity ?? 1);

                        DesignItem::create([
                            'design_id'           => $design->id,
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $bundleProduct->id,
                            'quantity'            => $componentQty,
                            'completed_quantity'  => 0,
                            'product_unit_conversion_id' => $orderItem->product_unit_conversion_id,
                            'unit_name' => $orderItem->unit_name,
                            'unit_conversion_value' => $orderItem->unit_conversion_value,

                            'design_file'         => null,
                            'preview_image'       => null,
                            'verification_status' => 'pending',
                        ]);
                    }
                }
            }

            $groupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type);

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
                'verified' => 1,
            ]);

            $saleAccount->closing_balance += $request->total_amount;
            $saleAccount->save();


            try {
                $totalRevenue = $request->total_amount;
                $totalCogs = 0;
                $totalFixedCost = 0;


                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->product_id && !$orderItem->product_bundle_id) {

                        $product = $orderItem->product;
                        $avgCost = $product->avg_cost ?? 0;
                        $fixedCost = $product?->fixed_cost ?? 0;
                        // $totalCogs += $avgCost * $orderItem->quantity;
                        // $totalFixedCost += $fixedCost * $orderItem->quantity;
                        $costQty = $orderItem->qty_base ?? $orderItem->quantity;

                        $totalCogs += $avgCost * $costQty;
                        $totalFixedCost += $fixedCost * $costQty;
                    } elseif ($orderItem->product_bundle_id) {

                        $bundle = $orderItem->productBundle;

                        $bundleAvgCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;

                            return ($product->avg_cost ?? 0) * ($bundleItem->quantity ?? 1);
                        });

                        $bundleFixedCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;

                            return ($product->fixed_cost ?? 0) * ($bundleItem->quantity ?? 1);
                        });

                        $costQty = $orderItem->qty_base ?? $orderItem->quantity;

                        $totalCogs       += $bundleAvgCost * $costQty;
                        $totalFixedCost  += $bundleFixedCost * $costQty;
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
        $order = Order::with([
            'orderItems.product.unitConversions.unit',
            'orderItems.productBundle.unitConversions.unit',
            'customer.addresses',
            'orderItems.product.unitConversions.prices.priceMode',
            'orderItems.productBundle.unitConversions.prices.priceMode',
            'customer.accounts',
            'customerAddress',
        ])->findOrFail($id);

        $products = Products::with([
            'categories',
            'discounts',
            'categories.discounts',
            'unitConversions.unit',
            'unitConversions.prices.priceMode',
        ])
            ->orderBy('name', 'asc')
            ->get();

        $productBundles = ProductBundle::with([
            'items.product.categories.discounts',
            'items.product.discounts',

            'primaryItem.product',
            'secondaryItems.product',

            'unitConversions.unit',
            'unitConversions.prices.priceMode',
        ])->orderBy('name', 'asc')->get();

        $productsJson = $products->map(function ($product) {
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
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
                    ];
                })->values()->toArray(),
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

            $bundleName = $bundle->items->map(function ($item) {
                return $item->product->name ?? '-';
            })->implode(' + ');

            return [
                'id' => $bundle->id,
                'name' => $bundleName ?: $bundle->name,
                'sku'  => $bundle->sku,
                'price' => $bundle->price,
                'base_unit_id' => $bundle->base_unit_id,
                'discounts' => $bundleDiscounts,
                'categories' => $bundleCategories,

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

                'units' => $bundle->unitConversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'unit_id' => $conversion->unit_id,
                        'unit_name' => optional($conversion->unit)->name,
                        'conversion_value' => $conversion->conversion_value,
                        'sale_price' => $conversion->sale_price,
                        'prices' => $conversion->prices->map(fn ($price) => [
                            'price_mode_id' => $price->price_mode_id,
                            'mode' => $price->priceMode?->slug,
                            'mode_name' => $price->priceMode?->name,
                            'fixed_cost' => $price->fixed_cost,
                            'margin' => $price->margin,
                            'sale_price' => $price->sale_price,
                        ])->values()->toArray(),
                    ];
                })->values()->toArray(),
            ];
        })->toArray();

        $user = Auth::user();

        $customers = Customers::with(['addresses', 'accounts'])
            ->when($user->role === 'Sales', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        $discount = Discount::first();
        $transactionTypes = Account::where('name', 'Order')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $priceModes = PriceMode::orderBy('sort_order')->orderBy('name')->get();
        $modeDiscounts = Discount::modeDiscountsPayload();

        return view('erp.pages.sales.sale-list.edit-order', compact(
            'order',
            'products',
            'productBundles',
            'customers',
            'discount',
            'cashAccounts',
            'bankAccounts',
            'transactionTypes',
            'productsJson',
            'productBundlesJson',
            'priceModes',
            'modeDiscounts'
        ));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'order_date'              => 'required|date_format:Y-m-d\TH:i',
            'due_date_option'         => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'         => 'nullable|date',
            'customer_id' => 'required|exists:customers,id',
            'customer_account_id' => 'required|exists:customer_accounts,id',
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
            'mode'   => 'required|array',
            'mode.*' => ['required', 'exists:price_modes,slug'],

            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',

            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',

            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
            'order_item_id'   => 'nullable|array',
            'order_item_id.*' => 'nullable|exists:order_items,id',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems')->findOrFail($id);

            $order->refresh();

            // A verified design alone must not lock Sale List edits. Structural
            // changes are blocked later only when assign/history already exists.

            $mapItems = function ($items) {
                return $items->values()->mapWithKeys(function ($item, $index) {
                    $key = 'item_' . ($item->id ?? $index);

                    return [$key => [
                        'id'              => $item->id,
                        'product'         => $item->product_name,
                        'satuan'          => $item->satuan,
                        'mode'            => $item->mode,
                        'product_id'      => $item->product_id,
                        'bundle_id'       => $item->product_bundle_id,
                        'quantity'        => (int) $item->quantity,
                        'qty_base'        => (float) ($item->qty_base ?? $item->quantity),
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
                'customer_account_id',
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
            // ======================================================
            // 🔥 BYPASS TOTAL: Hanya update order_date atau customer
            // ======================================================
            $oldUnitKeys = $order->orderItems
                ->map(function ($item) {
                    if ($item->satuan === 'satuan') {
                        return $item->product_unit_conversion_id === null
                            ? null
                            : 'satuan_' . $item->product_unit_conversion_id;
                    }

                    return $item->product_bundle_unit_conversion_id === null
                        ? null
                        : 'bundle_' . $item->product_bundle_unit_conversion_id;
                })
                ->values()
                ->toArray();

            $newUnitKeys = collect($request->product_unit_id ?? [])
                ->map(function ($v, $i) use ($request) {
                    if (!is_numeric($v)) {
                        return null;
                    }

                    $type = $request->product_type[$i] ?? null;

                    return $type . '_' . $v;
                })
                ->values()
                ->toArray();

            $onlyHeaderChanged = (
                $request->order_date !== $order->order_date
                || $request->customer_id != $order->customer_id
                || $request->customer_account_id != $order->customer_account_id
                || $request->customer_address_id != $order->customer_address_id
            )
                && (
                    // Tidak ada perubahan product
                    json_encode($request->product) == json_encode(
                        $order->orderItems->map(function ($item) {
                            return $item->satuan . '_' . ($item->satuan === 'satuan' ? $item->product_id : $item->product_bundle_id);
                        })->values()->toArray()
                    )
                    // Tidak ada perubahan qty
                    && json_encode($request->qty) == json_encode($order->orderItems->pluck('quantity')->values()->toArray())
                    // Tidak ada perubahan harga
                    && json_encode($request->price_before_discount) == json_encode($order->orderItems->pluck('price')->values()->toArray())
                    // Mode tidak berubah
                    && json_encode($request->mode) == json_encode(
                        $order->orderItems->pluck('mode')->values()->toArray()
                    ) && json_encode($newUnitKeys) == json_encode($oldUnitKeys)
                );

            if ($onlyHeaderChanged) {

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
                        $dueDate = null;
                }

                // ===== Update customer address =====
                $addressModel = \App\Models\CustomerAddresses::find($request->customer_address_id);

                // ===== Update header order Saja =====
                $order->update([
                    'order_date'          => $request->order_date,
                    'due_date'            => $dueDate,
                    'customer_id'         => $request->customer_id,
                    'customer_account_id'  => $request->customer_account_id,
                    'customer_address_id' => $request->customer_address_id,
                    'business_name'       => $addressModel?->business_name,
                    'shipping_address'    => $addressModel?->address,
                    'google_maps'         => $addressModel?->google_maps,
                    'notes'               => $request->notes,
                ]);

                // 🔥 CATAT HISTORY + FLAG EDITED
                OrderEditHistory::create([
                    'order_id'  => $order->id,
                    'edited_by' => Auth::id(),
                    'changes'   => [
                        'type'   => 'header_only',
                        'fields' => [
                            'order_date'          => ['old' => $oldOrderData['order_date'] ?? null,          'new' => $request->order_date],
                            'customer_id'         => ['old' => $oldOrderData['customer_id'] ?? null,         'new' => $request->customer_id],
                            'customer_account_id' => [
                                'old' => $oldOrderData['customer_account_id'] ?? null,
                                'new' => $request->customer_account_id
                            ],
                            'customer_address_id' => ['old' => $oldOrderData['customer_address_id'] ?? null, 'new' => $request->customer_address_id],
                        ],
                    ],
                    'text'      => $request->edit_note,
                    'edited_at' => now(),
                ]);

                $order->update(['status_edited' => true]);

                DB::commit();

                return redirect("/erp/sales/sale-list/")
                    ->with('success', 'Order berhasil diupdate (header saja).');
            }

            // ===============================
            // 🔥 DETECT PRICE ONLY CHANGES
            // ===============================
            $isPriceOnlyUpdate = true;

            // 1) CEK perubahan mode
            // if ($request->mode !== $order->mode) {
            //     $isPriceOnlyUpdate = false;
            // }

            $oldModes = $order->orderItems->pluck('mode')->values()->toArray();
            $newModes = $request->mode;

            if (json_encode($newModes) !== json_encode($oldModes)) {
                $isPriceOnlyUpdate = false;
            }

            $oldUnits = $order->orderItems
                ->map(function ($item) {
                    if ($item->satuan === 'satuan') {
                        return $item->product_unit_conversion_id === null
                            ? null
                            : 'satuan_' . $item->product_unit_conversion_id;
                    }

                    return $item->product_bundle_unit_conversion_id === null
                        ? null
                        : 'bundle_' . $item->product_bundle_unit_conversion_id;
                })
                ->values()
                ->toArray();

            $newUnits = collect($request->product_unit_id ?? [])
                ->map(function ($v, $i) use ($request) {
                    if (!is_numeric($v)) {
                        return null;
                    }

                    $type = $request->product_type[$i] ?? null;

                    return $type . '_' . $v;
                })
                ->values()
                ->toArray();

            if (json_encode($newUnits) !== json_encode($oldUnits)) {
                $isPriceOnlyUpdate = false;
            }

            // 2) CEK perubahan product & qty BERDASARKAN order_item_id
            if (count($order->orderItems) !== count($request->product)) {
                $isPriceOnlyUpdate = false;
            } else {
                foreach ($request->product as $i => $newKey) {
                    $orderItemId = $request->order_item_id[$i] ?? null;

                    if (!$orderItemId) {
                        $isPriceOnlyUpdate = false;
                        break;
                    }

                    $oldItem = $order->orderItems->firstWhere('id', (int) $orderItemId);

                    if (!$oldItem) {
                        $isPriceOnlyUpdate = false;
                        break;
                    }

                    $oldKey = $oldItem->satuan === 'satuan'
                        ? 'satuan_' . $oldItem->product_id
                        : 'bundle_' . $oldItem->product_bundle_id;

                    $newQty = (int) str_replace('.', '', $request->qty[$i]);

                    if ($oldKey !== $newKey || $newQty !== (int) $oldItem->quantity) {
                        $isPriceOnlyUpdate = false;
                        break;
                    }
                }
            }

            // 3) Jika benar PRICE ONLY → bypass semua blocking rules
            if ($isPriceOnlyUpdate) {

                // ===== Hitung due_date sama seperti blok bawah =====
                $orderDate = Carbon::parse($request->order_date);
                $dueDate   = null;

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

                $addressModel = \App\Models\CustomerAddresses::find($request->customer_address_id);

                // ===== Update harga item =====
                foreach ($order->orderItems as $index => $item) {
                    $item->update([
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);
                }

                // ===== HITUNG ULANG PAYMENT =====
                $newTotal   = $request->total_amount;
                $paidAmount = $order->paid_amount;
                $remaining  = $newTotal - $paidAmount;

                if ($paidAmount <= 0) {
                    $paymentStatus = 'Unpaid';
                } elseif ($paidAmount < $newTotal) {
                    $paymentStatus = 'Partially Paid';
                } else {
                    $paymentStatus = 'Paid';
                }

                // ===== Update HEADER juga (order_date, customer, address) =====
                $order->update([
                    'order_date'          => $request->order_date,
                    'due_date'            => $dueDate,
                    'customer_id'         => $request->customer_id,
                    'customer_account_id'  => $request->customer_account_id,
                    'customer_address_id' => $request->customer_address_id,
                    'business_name'       => $addressModel?->business_name,
                    'shipping_address'    => $addressModel?->address,
                    'google_maps'         => $addressModel?->google_maps,
                    'notes'               => $request->notes,

                    'total_amount'       => $request->sub_total,
                    'discount'           => $request->total_discount,
                    'grand_total'        => $newTotal,
                    'remaining_amount'   => $remaining,
                    'payment_status'     => $paymentStatus,
                    'discount_active'    => (int) $request->input('discount_active_hidden', 1),
                ]);

                // 🔥 CATAT HISTORY + FLAG EDITED JUGA DI SINI
                OrderEditHistory::create([
                    'order_id'  => $order->id,
                    'edited_by' => Auth::id(),
                    'changes'   => [
                        'type' => 'price_only',
                    ],
                    'text'      => $request->edit_note,
                    'edited_at' => now(),
                ]);

                $order->update(['status_edited' => true]);

                DB::commit();
                return redirect("/erp/sales/sale-list/")->with('success', 'Order berhasil diupdate (price only + header).');
            }

            // ================== 🔥 CEK PERUBAHAN PRODUK (Bukan Penambahan) ==================
            $isProductChanged = false;

            foreach ($request->product as $i => $newProductValue) {
                $orderItemId = $request->order_item_id[$i] ?? null;

                if (!$orderItemId) {
                    continue; // item baru, bukan perubahan produk lama
                }

                $oldItem = $order->orderItems->firstWhere('id', (int) $orderItemId);

                if (!$oldItem) {
                    continue;
                }

                $oldProductValue = $oldItem->satuan === 'satuan'
                    ? 'satuan_' . $oldItem->product_id
                    : 'bundle_' . $oldItem->product_bundle_id;

                if ($oldProductValue !== $newProductValue) {
                    $isProductChanged = true;

                    Log::debug("🔄 Product changed on order_item_id {$orderItemId}: {$oldProductValue} → {$newProductValue}");
                    break;
                }
            }

            if ($isProductChanged) {

                $hasProgressHistory = \App\Models\OrderProgressHistory::whereHas('progressItem', function ($q) use ($order) {
                    $q->whereHas('progress', function ($p) use ($order) {
                        $p->where('order_id', $order->id);
                    });
                })->exists();

                if ($hasProgressHistory) {
                    DB::rollBack();
                    return back()->with('error', 'Tidak dapat mengubah produk yang sudah ada karena sudah memiliki progress history produksi. Anda hanya bisa menambah produk baru.');
                }

                $hasAssign = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                    $q->where('order_id', $order->id);
                })->exists();

                if ($hasAssign) {
                    DB::rollBack();
                    return back()->with('error', 'Tidak dapat mengubah produk yang sudah ada karena sudah memiliki progress assign produksi. Anda hanya bisa menambah produk baru.');
                }

                $hasFinishedDelivery = $order->deliveryOrders()
                    ->with('shipments')
                    ->get()
                    ->flatMap->shipments
                    ->contains(fn($shipment) => $shipment->status === 'Finished');

                if ($hasFinishedDelivery) {
                    DB::rollBack();
                    return back()->with('error', 'Tidak dapat mengubah produk yang sudah ada karena sudah ada Delivery List yang selesai. Anda hanya bisa menambah produk baru.');
                }
            }

            if (SaleReturn::where('sale_order_id', $order->id)->exists()) {
                DB::rollBack(); // rollback supaya transaksi clear
                return back()->with('error', 'Tidak bisa mengupdate order ini karena sudah ada Sale Return.');
            }

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
                'customer_account_id'  => $request->customer_account_id,
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
            $existingItems = $order->orderItems->keyBy('id');
            $submittedItemIds = [];

            // ✅ CEK jika design sudah diverifikasi - PERBAIKAN QUERY
            $designVerified = \App\Models\Design::where('order_id', $order->id)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(status) = ?', ['verified'])
                        ->orWhereRaw('LOWER(verification_status) = ?', ['approved']);
                })
                ->exists();

            Log::debug("🔎 DEBUG Design Check", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'designVerified' => $designVerified ? 'TRUE' : 'FALSE',
                'design_count' => \App\Models\Design::where('order_id', $order->id)->count(),
                'design_status' => \App\Models\Design::where('order_id', $order->id)->pluck('status', 'verification_status')->toArray(),
            ]);

            // ===========================================================
            // 🧩 SNAPSHOT DESIGN ITEMS LAMA (LETakkan DI SINI, SEBELUM UPDATE ITEM)
            // ===========================================================
            $snapshotOldDesignProductQty = \App\Models\DesignItem::whereHas('design', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })
                ->get()
                ->groupBy('product_id')
                ->map(fn($items) => $items->sum(function ($item) {
                    $conversion = (float) ($item->unit_conversion_value ?? 1);
                    return (float) $item->quantity * ($conversion > 0 ? $conversion : 1);
                }));

            $newKeys = [];
            $polosanToPrintingOrderItemIds = [];

            $getDesignItemUnitData = function ($orderItem) {
                return [
                    'product_unit_conversion_id' => $orderItem->satuan === 'satuan'
                        ? $orderItem->product_unit_conversion_id
                        : $orderItem->product_bundle_unit_conversion_id,

                    'unit_name' => $orderItem->unit_name,

                    'unit_conversion_value' => $orderItem->unit_conversion_value,
                ];
            };

            $getDesignItemQuantity = function ($component, $orderItem) {
                $conversion = (float) ($orderItem->unit_conversion_value ?? 1);

                if ($conversion <= 0) {
                    $conversion = 1;
                }

                return (float) $component->qty / $conversion;
            };

            foreach ($request->product as $index => $productValue) {
                // productValue bisa "satuan_5" atau "bundle_9"
                [$type, $productId] = explode('_', $productValue);

                $qty = (int) $request->qty[$index];
                $itemMode = $request->mode[$index] ?? 'printing';

                $unitConversionId = $request->input("product_unit_id.$index");

                if ($unitConversionId === '' || $unitConversionId === 'null' || !is_numeric($unitConversionId)) {
                    $unitConversionId = null;
                }

                $unitConversionValue = (float) $request->input("unit_conversion_value.$index", 0);
                $unitName = $request->input("unit_name.$index");

                // fallback ambil dari database kalau form/JS tidak kirim unit
                if ($unitConversionId) {
                    if ($type === 'satuan') {
                        $unit = \App\Models\ProductUnitConversion::find($unitConversionId);

                        if ($unit) {
                            $unitConversionValue = (float) ($unitConversionValue ?: $unit->conversion_value);
                            $unitName = $unitName ?: ($unit->unit_name ?? $unit->unit?->name ?? 'Pcs');
                        }
                    }

                    if ($type === 'bundle') {
                        $unit = \App\Models\ProductBundleUnitConversion::find($unitConversionId);

                        if ($unit) {
                            $unitConversionValue = (float) ($unitConversionValue ?: $unit->conversion_value);
                            $unitName = $unitName ?: ($unit->unit_name ?? $unit->unit?->name ?? 'Pcs');
                        }
                    }
                }

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                if (!$unitName) {
                    $unitName = 'Pcs';
                }

                $qtyBase = $qty * $unitConversionValue;

                $key = "{$type}_{$productId}";
                $newKeys[] = $key;

                $orderItemId = $request->order_item_id[$index] ?? null;

                $orderItem = $orderItemId
                    ? $existingItems->get((int) $orderItemId)
                    : null;

                // 🔎 CEK COMPLETED QUANTITY — pastikan quantity baru tidak lebih kecil dari completed_quantity
                // 🔎 CEK PROGRESS (COMPLETED + ACTIVE ASSIGN)
                if ($type === 'satuan') {
                    $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $orderItem?->id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($progressItem) {
                        // Hitung total active assign dari tabel order_progress_assigns
                        $activeAssign = DB::table('order_progress_assigns')
                            ->where('order_progress_item_id', $progressItem->id)
                            ->selectRaw('COALESCE(SUM(assigned_quantity - (completed_quantity + defect_quantity + reject_quantity)), 0) as active_assign')
                            ->value('active_assign');

                        $requiredMinQty = $progressItem->completed_quantity + $activeAssign;

                        // if ($qtyBase < $requiredMinQty) {
                        //     DB::rollBack();
                        //     return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity (" . number_format($qtyBase) . ") tidak boleh lebih kecil dari total progress (" . number_format($requiredMinQty) . ") (Completed + Assigning).");
                        // }
                    }
                } elseif ($type === 'bundle') {
                    $bundle = \App\Models\ProductBundle::with('items')->find($productId);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $orderItem?->id)
                                ->where('product_id', $bundleItem->product_id)
                                ->first();

                            if ($progressItem) {
                                $activeAssign = DB::table('order_progress_assigns')
                                    ->where('order_progress_item_id', $progressItem->id)
                                    ->selectRaw('COALESCE(SUM(assigned_quantity - (completed_quantity + defect_quantity + reject_quantity)), 0) as active_assign')
                                    ->value('active_assign');

                                $requiredMinQty = $progressItem->completed_quantity + $activeAssign;

                                $componentQty = $qtyBase * ($bundleItem->quantity ?? 1);

                                // if ($componentQty < $requiredMinQty) {
                                //     DB::rollBack();
                                //     return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity untuk produk bundle ID {$bundleItem->product_id} (" . number_format($qtyBase) . ") tidak boleh lebih kecil dari total progress (" . number_format($requiredMinQty) . ") (Completed + Assigning).");
                                // }
                            }
                        }
                    }
                }

                $warehouseId = $request->inventory_warehouse_id ?? 1;

                // 🔎 CEK PROGRESS HISTORY — pastikan qty baru tidak < total change_quantity
                if ($type === 'satuan') {
                    $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $orderItem?->id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($progressItem) {
                        $totalChanged = \App\Models\OrderProgressHistory::where('order_progress_item_id', $progressItem->id)
                            ->sum('change_quantity');

                        // if ($qtyBase < $totalChanged) {
                        //     DB::rollBack();
                        //     return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity (" . number_format($qtyBase) . ") tidak boleh lebih kecil dari total progress (" . number_format($totalChanged) . ").");
                        // }
                    }
                } elseif ($type === 'bundle') {
                    $bundle = \App\Models\ProductBundle::with('items')->find($productId);
                    if ($bundle) {
                        foreach ($bundle->items as $bundleItem) {
                            $progressItem = \App\Models\OrderProgressItem::where('order_item_id', $orderItem?->id)
                                ->where('product_id', $bundleItem->product_id)
                                ->first();

                            if ($progressItem) {
                                $totalChanged = \App\Models\OrderProgressHistory::where('order_progress_item_id', $progressItem->id)
                                    ->sum('change_quantity');

                                $componentQty = $qtyBase * ($bundleItem->quantity ?? 1);

                                // if ($componentQty < $totalChanged) {
                                //     DB::rollBack();
                                //     return back()->with('error', "Gagal mengupdate order {$order->order_number}: Quantity untuk produk bundle ID {$bundleItem->product_id} (" . number_format($qtyBase) . ") tidak boleh lebih kecil dari total progress (" . number_format($totalChanged) . ").");
                                // }
                            }
                        }
                    }
                }

                if ($orderItem) {
                    $submittedItemIds[] = (int) $orderItem->id;
                    $oldMode   = $orderItem->mode;
                    // $diffQty   = $qty - $orderItem->quantity;

                    $oldProductIdForComponent = $orderItem->product_id;
                    $oldBundleIdForComponent = $orderItem->product_bundle_id;

                    $oldQty = (float) $orderItem->quantity;
                    $oldQtyBase = (float) ($orderItem->qty_base ?? $orderItem->quantity);
                    $newQtyBase = $qtyBase;

                    $diffQty = $qty - $oldQty;
                    $diffQtyBase = $newQtyBase - $oldQtyBase;

                    $oldProductIdForComponent = $orderItem->product_id;
                    $oldBundleIdForComponent = $orderItem->product_bundle_id;

                    // 🔥 CEK jika produk berubah di mode polosan
                    if ($itemMode === 'polosan' && $type === 'satuan') {
                        $oldProductId = $orderItem->product_id;
                        if ($oldProductId != $productId) {
                            // Kembalikan stok produk lama
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $oldProductId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->increment('available_quantity', $oldQtyBase);

                            // Kurangi stok produk baru
                            $productionStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['available_quantity' => 0]
                            );
                            $productionStock->decrement('available_quantity', $qtyBase);
                        }
                    }

                    // Pending production stock is reconciled once from the complete
                    // before/after snapshots below. Keep the legacy inline branch
                    // disabled to prevent double increments on product/qty changes.
                    if (false && $designVerified) {
                        Log::debug("🧩 Design verified for order {$order->order_number}");

                        // Tetapkan warehouse produksi FIX
                        \App\Models\Design::where('order_id', $order->id)->update([
                            // 'status' => 'Pending',
                            // 'verification_status' => 'pending',
                            'status_edited' => true,
                        ]);

                        $productionWarehouseId = 2;

                        if ($type === 'satuan') {

                            $oldProductId = $orderItem->product_id;
                            $newProductId = (int) $productId;

                            // Jika produk BERUBAH (A → B)
                            if ($oldProductId != $newProductId) {
                                Log::debug("🔄 Product changed from {$oldProductId} → {$newProductId}");

                                // 🔻 Kurangi pending_waiting_list produk lama
                                $oldStock = \App\Models\ProductionStock::where([
                                    'product_id' => $oldProductId,
                                    'production_warehouse_id' => $productionWarehouseId
                                ])->lockForUpdate()->first();

                                if ($oldStock) {
                                    $dec = min($oldStock->pending_waiting_list, $oldQtyBase);
                                    if ($dec > 0) {
                                        $oldStock->decrement('pending_waiting_list', $dec);
                                        Log::debug("✅ DECREMENT {$dec} from old product {$oldProductId}");
                                    }
                                }

                                // 🔺 Tambahkan pending_waiting_list ke produk baru
                                $newStock = \App\Models\ProductionStock::firstOrCreate(
                                    [
                                        'product_id' => $newProductId,
                                        'production_warehouse_id' => $productionWarehouseId
                                    ],
                                    [
                                        'pending_waiting_list' => 0,
                                        'available_quantity' => 0,
                                        'finished_product_stock' => 0,
                                        'canceled_product_stock' => 0
                                    ]
                                );

                                $newStock->increment('pending_waiting_list', $qtyBase);
                                Log::debug("✅ INCREMENT {$qty} to new product {$newProductId}");
                            }

                            // Jika produk sama tapi QTY BERUBAH
                            elseif ($diffQtyBase !== 0) {

                                $stock = \App\Models\ProductionStock::firstOrCreate(
                                    [
                                        'product_id' => $oldProductId,
                                        'production_warehouse_id' => $productionWarehouseId
                                    ],
                                    ['pending_waiting_list' => 0]
                                );

                                if ($diffQtyBase > 0) {
                                    $stock->increment('pending_waiting_list', $diffQtyBase);
                                } else {
                                    $dec = min($stock->pending_waiting_list, abs($diffQtyBase));
                                    $stock->decrement('pending_waiting_list', $dec);
                                }
                            }
                        }

                        // ===================== BUNDLE =======================
                        elseif ($type === 'bundle') {

                            $oldBundleId = $oldBundleIdForComponent;
                            $newBundleId = (int) $productId;

                            // Jika BUNDLE berubah
                            if ($oldBundleId != $newBundleId) {
                                Log::debug("🔄 Bundle changed from {$oldBundleId} → {$newBundleId}");

                                // 🔻 Kurangi semua product bundle lama
                                $oldBundle = \App\Models\ProductBundle::with('items')->find($oldBundleId);

                                if ($oldBundle) {
                                    foreach ($oldBundle->items as $bi) {
                                        if (!$bi->product_id) {
                                            continue;
                                        }

                                        $oldComponentQty = $oldQtyBase * ($bi->quantity ?? 1);

                                        $oldStock = \App\Models\ProductionStock::where([
                                            'product_id' => $bi->product_id,
                                            'production_warehouse_id' => $productionWarehouseId,
                                        ])->lockForUpdate()->first();

                                        if ($oldStock) {
                                            $dec = min($oldStock->pending_waiting_list, $oldComponentQty);

                                            if ($dec > 0) {
                                                $oldStock->decrement('pending_waiting_list', $dec);
                                                Log::debug("✅ Bundle old: DECREMENT {$dec} from product {$bi->product_id}");
                                            }
                                        }
                                    }
                                }

                                // 🔺 Tambahkan semua product bundle baru
                                $newBundle = \App\Models\ProductBundle::with('items')->find($newBundleId);

                                if ($newBundle) {
                                    foreach ($newBundle->items as $bi) {
                                        if (!$bi->product_id) {
                                            continue;
                                        }

                                        $newComponentQty = $qtyBase * ($bi->quantity ?? 1);

                                        $newStock = \App\Models\ProductionStock::firstOrCreate(
                                            [
                                                'product_id' => $bi->product_id,
                                                'production_warehouse_id' => $productionWarehouseId,
                                            ],
                                            [
                                                'pending_waiting_list' => 0,
                                                'available_quantity' => 0,
                                                'finished_product_stock' => 0,
                                                'canceled_product_stock' => 0,
                                            ]
                                        );

                                        $newStock->increment('pending_waiting_list', $newComponentQty);
                                        Log::debug("✅ Bundle new: INCREMENT {$newComponentQty} to product {$bi->product_id}");
                                    }
                                }
                            }

                            // Jika bundle sama tapi qty/unit berubah
                            elseif ($diffQtyBase !== 0) {
                                $bundle = \App\Models\ProductBundle::with('items')->find($newBundleId);

                                if ($bundle) {
                                    foreach ($bundle->items as $bi) {
                                        if (!$bi->product_id) {
                                            continue;
                                        }

                                        $diffComponentQty = $diffQtyBase * ($bi->quantity ?? 1);

                                        $stock = \App\Models\ProductionStock::firstOrCreate(
                                            [
                                                'product_id' => $bi->product_id,
                                                'production_warehouse_id' => $productionWarehouseId,
                                            ],
                                            [
                                                'pending_waiting_list' => 0,
                                                'available_quantity' => 0,
                                                'finished_product_stock' => 0,
                                                'canceled_product_stock' => 0,
                                            ]
                                        );

                                        if ($diffComponentQty > 0) {
                                            $stock->increment('pending_waiting_list', $diffComponentQty);
                                        } elseif ($diffComponentQty < 0) {
                                            $dec = min($stock->pending_waiting_list, abs($diffComponentQty));
                                            $stock->decrement('pending_waiting_list', $dec);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // UPDATE ITEM DASAR (termasuk product_id / bundle_id)
                    $orderItem->update([
                        'product_id'           => $type === 'satuan' ? $productId : null,
                        'product_bundle_id'    => $type === 'bundle' ? $productId : null,

                        'product_unit_conversion_id' => $type === 'satuan' ? $unitConversionId : null,
                        'product_bundle_unit_conversion_id' => $type === 'bundle' ? $unitConversionId : null,

                        'unit_name'             => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base'              => $qtyBase,

                        'mode'                 => $itemMode,
                        'quantity'             => $qty,
                        'price'                => $request->price_before_discount[$index],
                        'subtotal'             => $request->total_before_discount[$index],
                        'discount_price'       => $request->price_after_discount[$index],
                        'total_after_discount' => $request->total_after_discount[$index],
                    ]);

                    if ($oldMode === 'polosan' && $itemMode === 'printing') {
                        $polosanToPrintingOrderItemIds[] = $orderItem->id;

                        \App\Models\DeliveryOrderItem::where('order_item_id', $orderItem->id)
                            ->delete();

                        \App\Models\OrderProgressItem::where('order_item_id', $orderItem->id)
                            ->delete();

                        $unitData = $getDesignItemUnitData($orderItem);

                        \App\Models\DesignItem::where('order_item_id', $orderItem->id)
                            ->update(array_merge([
                                'verification_status' => 'pending',
                                'completed_quantity'  => 0,
                            ], $unitData));
                    }

                    // === HANDLE COMPONENTS ===
                    $isProductChanged = false;

                    if ($type === 'satuan') {
                        $isProductChanged = ($oldProductIdForComponent != $productId);
                    } elseif ($type === 'bundle') {
                        $isProductChanged = ($oldBundleIdForComponent != $productId);
                    }

                    if ($isProductChanged) {
                        Log::debug("🔄 UPDATE COMPONENT PRODUCT ID", [
                            'order_item_id' => $orderItem->id,
                            'old_product_id' => $oldProductIdForComponent,
                            'new_product_id' => $productId,
                        ]);

                        if ($type === 'satuan') {
                            $newProduct = \App\Models\Products::find($productId);

                            $orderItem->components()->update([
                                'product_id'         => $newProduct->id,
                                'qty'                => $qtyBase,
                                'avg_cost_at_sale'   => $newProduct->avg_cost ?? 0,
                                'fixed_cost_at_sale' => $newProduct->fixed_cost ?? 0,
                                'total_cost'         => ($newProduct->avg_cost ?? 0) * $qtyBase,
                                'total_fixed_cost'   => ($newProduct->fixed_cost ?? 0) * $qtyBase,
                            ]);

                            Log::debug("✅ COMPONENT UPDATED to product {$newProduct->name}");
                        } elseif ($type === 'bundle') {
                            $bundle = \App\Models\ProductBundle::with('items.product')->find($productId);

                            $orderItem->components()->delete();

                            foreach ($bundle->items as $bundleItem) {
                                $product = $bundleItem->product;

                                if (!$product) {
                                    continue;
                                }

                                $componentQty = $qtyBase * ($bundleItem->quantity ?? 1);

                                $orderItem->components()->create([
                                    'product_id'         => $product->id,
                                    'qty'                => $componentQty,
                                    'avg_cost_at_sale'   => $product->avg_cost ?? 0,
                                    'fixed_cost_at_sale' => $product->fixed_cost ?? 0,
                                    'total_cost'         => ($product->avg_cost ?? 0) * $componentQty,
                                    'total_fixed_cost'   => ($product->fixed_cost ?? 0) * $componentQty,
                                ]);
                            }
                        }
                    } else {
                        // Produk sama, update qty saja
                        // foreach ($orderItem->components as $component) {
                        //     $avgCost = $component->avg_cost_at_sale;
                        //     $fixedCost = $component->fixed_cost_at_sale;

                        //     $componentQty = $type === 'satuan' ? $qtyBase : $qty;

                        //     $component->update([
                        //         'qty'              => $componentQty,
                        //         'total_cost'       => $avgCost * $componentQty,
                        //         'total_fixed_cost' => $fixedCost * $componentQty,
                        //     ]);
                        // }
                        if ($type === 'satuan') {
                            foreach ($orderItem->components as $component) {
                                $avgCost = $component->avg_cost_at_sale;
                                $fixedCost = $component->fixed_cost_at_sale;

                                $component->update([
                                    'qty'              => $qtyBase,
                                    'total_cost'       => $avgCost * $qtyBase,
                                    'total_fixed_cost' => $fixedCost * $qtyBase,
                                ]);
                            }
                        }

                        if ($type === 'bundle') {
                            $bundle = ProductBundle::with('items.product')->find($productId);

                            if ($bundle) {
                                foreach ($orderItem->components as $component) {
                                    $bundleItem = $bundle->items->firstWhere('product_id', $component->product_id);

                                    $componentQty = $qtyBase * ($bundleItem?->quantity ?? 1);

                                    $avgCost = $component->avg_cost_at_sale;
                                    $fixedCost = $component->fixed_cost_at_sale;

                                    $component->update([
                                        'qty'              => $componentQty,
                                        'total_cost'       => $avgCost * $componentQty,
                                        'total_fixed_cost' => $fixedCost * $componentQty,
                                    ]);
                                }
                            }
                        }
                    }

                    // 🧠 force refresh setelah update
                    $orderItem->refresh();

                    Log::debug("🎯 FINAL COMPONENT COUNT", [
                        'order_item_id' => $orderItem->id,
                        'count' => $orderItem->components()->count(),
                    ]);

                    // 🔥 BARU: Update inventory stock - handle perubahan produk DAN quantity
                    if ($type === 'satuan') {
                        $oldProductId = $oldProductIdForComponent;
                        $newProductId = (int) $productId;

                        if ($oldProductId != $newProductId) {
                            InventoryStock::firstOrCreate(
                                ['product_id' => $oldProductId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            )->increment('stock_after_sales', $oldQty);

                            InventoryStock::firstOrCreate(
                                ['product_id' => $newProductId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            )->decrement('stock_after_sales', $qty);
                        } elseif ($diffQty !== 0) {
                            $inventoryStock = InventoryStock::firstOrCreate(
                                ['product_id' => $newProductId, 'inventory_warehouse_id' => $warehouseId],
                                ['stock_after_sales' => 0]
                            );

                            if ($diffQty > 0) {
                                $inventoryStock->decrement('stock_after_sales', $diffQty);
                            } else {
                                $inventoryStock->increment('stock_after_sales', abs($diffQty));
                            }
                        }
                    } elseif ($type === 'bundle') {
                        $oldBundleId = $oldBundleIdForComponent;

                        if ($oldBundleId != $productId) {
                            $oldBundle = ProductBundle::with('items.product')->find($oldBundleId);

                            if ($oldBundle) {
                                foreach ($oldBundle->items as $oldBundleItem) {
                                    $restoreQty = $oldQty * ($oldBundleItem->quantity ?? 1);

                                    $oldInventoryStock = InventoryStock::firstOrCreate(
                                        [
                                            'product_id' => $oldBundleItem->product_id,
                                            'inventory_warehouse_id' => $warehouseId,
                                        ],
                                        ['stock_after_sales' => 0]
                                    );

                                    $oldInventoryStock->increment('stock_after_sales', $restoreQty);

                                    if ($oldMode === 'polosan') {
                                        $oldInventoryStock->increment('inventory_stock', $oldQtyBase * ($oldBundleItem->quantity ?? 1));
                                    }
                                }
                            }

                            $newBundle = ProductBundle::with('items.product')->find($productId);

                            if ($newBundle) {
                                foreach ($newBundle->items as $newBundleItem) {
                                    $takeQty = $qty * ($newBundleItem->quantity ?? 1);

                                    $newInventoryStock = InventoryStock::firstOrCreate(
                                        [
                                            'product_id' => $newBundleItem->product_id,
                                            'inventory_warehouse_id' => $warehouseId,
                                        ],
                                        ['stock_after_sales' => 0]
                                    );

                                    $newInventoryStock->decrement('stock_after_sales', $takeQty);

                                    if ($itemMode === 'polosan') {
                                        $newInventoryStock->decrement('inventory_stock', $qtyBase * ($newBundleItem->quantity ?? 1));
                                    }
                                }
                            }
                        } elseif ($diffQty !== 0 || $oldMode !== $itemMode) {
                            $bundle = ProductBundle::with('items.product')->find($productId);

                            if ($bundle) {
                                foreach ($bundle->items as $bundleItem) {
                                    $diffComponentQty = $diffQty * ($bundleItem->quantity ?? 1);

                                    $inventoryStock = InventoryStock::firstOrCreate(
                                        [
                                            'product_id' => $bundleItem->product_id,
                                            'inventory_warehouse_id' => $warehouseId,
                                        ],
                                        ['stock_after_sales' => 0]
                                    );

                                    if ($diffComponentQty > 0) {
                                        $inventoryStock->decrement('stock_after_sales', $diffComponentQty);
                                    } elseif ($diffComponentQty < 0) {
                                        $inventoryStock->increment('stock_after_sales', abs($diffComponentQty));
                                    }

                                    if ($oldMode === 'printing' && $itemMode === 'polosan') {
                                        $inventoryStock->decrement('inventory_stock', $qtyBase * ($bundleItem->quantity ?? 1));
                                    }

                                    if ($oldMode === 'polosan' && $itemMode === 'printing') {
                                        $inventoryStock->increment('inventory_stock', $oldQtyBase * ($bundleItem->quantity ?? 1));
                                    }

                                    if ($oldMode === 'polosan' && $itemMode === 'polosan') {
                                        $diffInventoryQty = $diffQtyBase * ($bundleItem->quantity ?? 1);

                                        if ($diffInventoryQty > 0) {
                                            $inventoryStock->decrement('inventory_stock', $diffInventoryQty);
                                        } elseif ($diffInventoryQty < 0) {
                                            $inventoryStock->increment('inventory_stock', abs($diffInventoryQty));
                                        }
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
                            'mode'                 => $itemMode,
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        $submittedItemIds[] = (int) $orderItem->id;

                        // ✅ Buat komponen untuk produk satuan
                        $orderItem->components()->create([
                            'product_id'         => $product->id,
                            'qty'                => $qtyBase,
                            'avg_cost_at_sale'   => $product->avg_cost ?? 0,
                            'fixed_cost_at_sale' => $product->fixed_cost ?? 0,
                            'total_cost'         => ($product->avg_cost ?? 0) * $qtyBase,
                            'total_fixed_cost'   => ($product->fixed_cost ?? 0) * $qtyBase,
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

                        if ($itemMode === 'polosan') {
                            $inventoryStock->decrement('inventory_stock', $qty);
                        }
                    } elseif ($type === 'bundle') {
                        $bundle = ProductBundle::with('items.product')->findOrFail($productId);

                        // Buat order item untuk bundle
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
                            'mode'                 => $itemMode,
                            'quantity'             => $qty,
                            'price'                => $request->price_before_discount[$index],
                            'subtotal'             => $request->total_before_discount[$index],
                            'discount_price'       => $request->price_after_discount[$index],
                            'total_after_discount' => $request->total_after_discount[$index],
                        ]);

                        $submittedItemIds[] = (int) $orderItem->id;

                        foreach ($bundle->items as $bundleItem) {
                            $componentProduct = $bundleItem->product;

                            if (!$componentProduct) {
                                continue;
                            }

                            $componentQty = $qtyBase * ($bundleItem->quantity ?? 1);

                            $componentStockQty = $qty * ($bundleItem->quantity ?? 1);

                            $orderItem->components()->create([
                                'product_id'         => $componentProduct->id,
                                'qty'                => $componentQty,
                                'avg_cost_at_sale'   => $componentProduct->avg_cost ?? 0,
                                'fixed_cost_at_sale' => $componentProduct->fixed_cost ?? 0,
                                'total_cost'         => ($componentProduct->avg_cost ?? 0) * $componentQty,
                                'total_fixed_cost'   => ($componentProduct->fixed_cost ?? 0) * $componentQty,
                            ]);

                            $inventoryStock = InventoryStock::firstOrCreate(
                                [
                                    'product_id'             => $bundleItem->product_id,
                                    'inventory_warehouse_id' => $warehouseId,
                                ],
                                ['stock_after_sales' => 0]
                            );

                            $inventoryStock->decrement('stock_after_sales', $componentStockQty);

                            if ($itemMode === 'polosan') {
                                $inventoryStock->decrement('inventory_stock', $componentStockQty);
                            }
                        }
                    }
                }
            }

            foreach ($existingItems as $item) {
                if (!in_array((int) $item->id, $submittedItemIds, true)) {

                    if ($item->satuan === 'satuan') {
                        // $restoreQty = $item->qty_base ?? $item->quantity;
                        $restoreQty = $item->quantity;

                        $inventoryStock = InventoryStock::firstOrCreate(
                            [
                                'product_id' => $item->product_id,
                                'inventory_warehouse_id' => $warehouseId,
                            ],
                            ['stock_after_sales' => 0]
                        );

                        $inventoryStock->increment('stock_after_sales', $restoreQty);

                        if ($item->usesPolosanFlow()) {
                            $inventoryStock->increment('inventory_stock', $restoreQty);
                        }
                    } elseif ($item->satuan === 'bundle') {
                        $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);

                        if ($bundle) {
                            foreach ($bundle->items as $bundleItem) {
                                // $restoreQty = ($item->qty_base ?? $item->quantity) * ($bundleItem->quantity ?? 1);
                                $restoreQty = $item->quantity * ($bundleItem->quantity ?? 1);

                                $inventoryStock = InventoryStock::firstOrCreate(
                                    [
                                        'product_id' => $bundleItem->product_id,
                                        'inventory_warehouse_id' => $warehouseId,
                                    ],
                                    ['stock_after_sales' => 0]
                                );

                                $inventoryStock->increment('stock_after_sales', $restoreQty);

                                if ($item->usesPolosanFlow()) {
                                    $inventoryStock->increment('inventory_stock', $restoreQty);
                                }
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
                if ((string)$oldVal !== (string)$newVal) {
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

            // ===========================================================
            // ✅ FIX: HANDLE PENDING_WAITING_LIST UNTUK PRODUK BERUBAH
            // ===========================================================
            $designVerifiedFinal = \App\Models\Design::where('order_id', $order->id)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(status) = ?', ['verified'])
                        ->orWhereRaw('LOWER(verification_status) = ?', ['approved']);
                })
                ->exists();

            if ($designVerifiedFinal) {
                Log::debug("🧩 FINAL VERIFIED HANDLER FIX for Order {$order->order_number}");

                $warehouseId = 2;

                $order->load('orderItems.productBundle.items');
                $newProductQty = collect();

                foreach ($order->orderItems as $oi) {
                    if ($oi->satuan === 'satuan') {
                        $newQty = $oi->qty_base ?? $oi->quantity;

                        $newProductQty[$oi->product_id] = ($newProductQty[$oi->product_id] ?? 0) + $newQty;
                    } elseif ($oi->satuan === 'bundle' && $oi->productBundle) {
                        foreach ($oi->productBundle->items as $bi) {
                            if ($bi->product_id) {
                                $bundleQty = $oi->qty_base ?? $oi->quantity;
                                $componentQty = $bundleQty * ($bi->quantity ?? 1);

                                $newProductQty[$bi->product_id] = ($newProductQty[$bi->product_id] ?? 0) + $componentQty;
                            }
                        }
                    }
                }

                // 🚀 Gabungkan semua product_id dari lama dan baru
                $allProductIds = $snapshotOldDesignProductQty->keys()
                    ->merge($newProductQty->keys())
                    ->unique();

                foreach ($allProductIds as $productId) {
                    $oldQty = $snapshotOldDesignProductQty[$productId] ?? 0;
                    $newQty = $newProductQty[$productId] ?? 0;
                    $diff = $newQty - $oldQty;

                    $ps = \App\Models\ProductionStock::firstOrCreate(
                        ['product_id' => $productId, 'production_warehouse_id' => $warehouseId],
                        ['pending_waiting_list' => 0, 'available_quantity' => 0, 'finished_product_stock' => 0, 'canceled_product_stock' => 0]
                    );

                    if ($diff > 0) {
                        $ps->increment('pending_waiting_list', $diff);
                        Log::debug("⬆️ Added {$diff} pending_waiting_list to product {$productId}");
                    } elseif ($diff < 0) {
                        $dec = min(abs($diff), $ps->pending_waiting_list);
                        $ps->decrement('pending_waiting_list', $dec);
                        Log::debug("⬇️ Reduced {$dec} pending_waiting_list from product {$productId}");
                    }
                }

                Log::debug("✅ Pending waiting list fully synced for verified design order {$order->order_number}");
            }

            // ==============================
            // 🔥 Tandai DESIGN sebagai edited
            // ==============================
            if ($designVerifiedFinal) {
                \App\Models\Design::where('order_id', $order->id)
                    ->update(['status_edited' => true]);
            }

            // ================== SYNC MODE PER ORDER ITEM ==================
            $order->load([
                'orderItems.components',
                'orderItems.productBundle.items.product',
                'customer',
            ]);

            $printingOrderItems = $order->orderItems->filter->usesProductionFlow();
            $polosanOrderItems  = $order->orderItems->filter->usesPolosanFlow();


            // =======================================================
            // ITEM PRINTING -> MASUK DESIGN PENDING
            // =======================================================
            if ($printingOrderItems->isNotEmpty()) {
                $design = $order->designs()->with('items')->first();

                if ($design) {
                    $design->update([
                        'date'  => now()->format('Y-m-d'),
                        'notes' => $request->notes ?? $design->notes,
                    ]);
                } else {
                    $design = Design::create([
                        'order_id'            => $order->id,
                        'design_number'       => $order->order_number,
                        'date'                => now()->format('Y-m-d'),
                        'status'              => 'Pending',
                        'notes'               => $request->notes ?? null,
                        'verification_status' => 'pending',
                    ]);
                }

                $design->load('items');

                $existingDesignItems = $design->items
                    ->filter(fn($item) => $item->orderItem?->usesProductionFlow())
                    ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                $components = \App\Models\OrderItemComponent::whereIn(
                    'order_item_id',
                    $printingOrderItems->pluck('id')
                )
                    ->with('orderItem')
                    ->get();

                $newDesignKeys = [];

                foreach ($components as $component) {
                    $orderItem = $component->orderItem;
                    if (!$orderItem || !$orderItem->usesProductionFlow()) {
                        continue;
                    }

                    $productId = $component->product_id;
                    $qty       = $component->qty; // ini tetap qty_base untuk progress/stock
                    $designQty = $getDesignItemQuantity($component, $orderItem);
                    $key       = "{$component->order_item_id}_{$productId}";

                    $newDesignKeys[] = $key;

                    $unitData = $getDesignItemUnitData($orderItem);

                    if ($existingDesignItems->has($key)) {
                        $existingDesignItems[$key]->update(array_merge([
                            'quantity'            => $designQty,
                            'verification_status' => 'pending',
                        ], $unitData));
                    } else {
                        DesignItem::create(array_merge([
                            'design_id'           => $design->id,
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $productId,
                            'quantity'            => $designQty,
                            'completed_quantity'  => 0,
                            'design_file'         => null,
                            'preview_image'       => null,
                            'verification_status' => 'pending',
                        ], $unitData));
                    }
                }

                foreach ($existingDesignItems as $key => $designItem) {
                    if (!in_array($key, $newDesignKeys)) {
                        $designItem->delete();
                    }
                }

                // Kalau sudah ada OrderProgress, sync item printing ke progress
                $orderProgress = $order->orderProgress()->with('items')->first();

                if ($orderProgress) {
                    $orderProgress->update([
                        'status' => 'Pending',
                    ]);

                    $existingProgressItems = $orderProgress->items
                        ->filter(fn($item) => $item->orderItem?->usesProductionFlow())
                        ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                    $newProgressKeys = [];

                    foreach ($components as $component) {
                        $orderItem = $component->orderItem;
                        if (!$orderItem || !$orderItem->usesProductionFlow()) {
                            continue;
                        }

                        if (in_array($orderItem->id, $polosanToPrintingOrderItemIds)) {
                            continue;
                        }

                        $productId = $component->product_id;
                        $qty       = $component->qty;
                        $key       = "{$component->order_item_id}_{$productId}";

                        $newProgressKeys[] = $key;

                        $designItem = DesignItem::where('design_id', $design->id)
                            ->where('order_item_id', $orderItem->id)
                            ->where('product_id', $productId)
                            ->first();

                        if ($existingProgressItems->has($key)) {
                            $existingProgressItems[$key]->update([
                                'design_item_id' => $designItem?->id,
                                'quantity'       => $qty,
                            ]);
                        } else {
                            OrderProgressItem::create([
                                'order_progress_id'  => $orderProgress->id,
                                'design_item_id'     => $designItem?->id,
                                'order_item_id'      => $orderItem->id,
                                'product_id'         => $productId,
                                'quantity'           => $qty,
                                'completed_quantity' => 0,
                            ]);
                        }
                    }

                    foreach ($existingProgressItems as $key => $progressItem) {
                        if (!in_array($key, $newProgressKeys)) {
                            $progressItem->delete();
                        }
                    }
                }

                // Kalau sudah ada DeliveryOrder dari proses printing, sync DO item printing
                $orderProgress = $order->orderProgress()->with('items')->first();

                if ($orderProgress) {
                    $deliveryOrder = $order->deliveryOrders()->with('items')->first();

                    if ($deliveryOrder) {
                        $deliveryOrder->update([
                            'delivery_date'    => now()->format('Y-m-d'),
                            'note'             => $request->notes ?? $deliveryOrder->note,
                            'customer'         => $order->customer?->name ?? $deliveryOrder->customer,
                            'shipping_address' => $order->shipping_address ?? $deliveryOrder->shipping_address,
                            'google_map_link'  => $order->google_maps ?? $deliveryOrder->google_map_link,
                        ]);

                        $existingDoItems = $deliveryOrder->items
                            ->filter(fn($item) => $item->orderItem?->usesProductionFlow())
                            ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                        $newDoKeys = [];

                        foreach ($components as $component) {
                            $orderItem = $component->orderItem;
                            if (!$orderItem || !$orderItem->usesProductionFlow()) {
                                continue;
                            }

                            if (in_array($orderItem->id, $polosanToPrintingOrderItemIds)) {
                                continue;
                            }

                            $productId = $component->product_id;
                            $qty       = $component->qty;
                            $key       = "{$component->order_item_id}_{$productId}";

                            $newDoKeys[] = $key;

                            $progressItem = OrderProgressItem::where('order_progress_id', $orderProgress->id)
                                ->where('order_item_id', $orderItem->id)
                                ->where('product_id', $productId)
                                ->first();

                            if ($existingDoItems->has($key)) {
                                $existingDoItems[$key]->update([
                                    'order_progress_id'      => $orderProgress->id,
                                    'order_progress_item_id' => $progressItem?->id,
                                    'design_item_id'         => $progressItem?->design_item_id,
                                    'status'                 => 'Pending',
                                    'progress_qty'           => $qty,
                                    'ready_qty'              => 0,
                                    'note'                   => $request->notes ?? $existingDoItems[$key]->note,
                                ]);
                            } else {
                                DeliveryOrderItem::create([
                                    'delivery_order_id'      => $deliveryOrder->id,
                                    'order_progress_id'      => $orderProgress->id,
                                    'order_item_id'          => $orderItem->id,
                                    'order_progress_item_id' => $progressItem?->id,
                                    'design_item_id'         => $progressItem?->design_item_id,
                                    'product_id'             => $productId,
                                    'status'                 => 'Pending',
                                    'progress_qty'           => $qty,
                                    'ready_qty'              => 0,
                                    'shipped_qty'            => 0,
                                    'note'                   => $request->notes ?? null,
                                ]);
                            }
                        }

                        foreach ($existingDoItems as $key => $doItem) {
                            if (!in_array($key, $newDoKeys)) {
                                $doItem->delete();
                            }
                        }
                    }
                }
            }


            // =======================================================
            // ITEM POLOSAN -> LANGSUNG APPROVED + COMPLETED + DELIVERY
            // =======================================================
            if ($polosanOrderItems->isNotEmpty()) {
                $design = $order->designs()->with('items')->first();

                if ($design) {
                    $design->update([
                        'date'  => now()->format('Y-m-d'),
                        'notes' => $request->notes ?? $design->notes,
                    ]);
                } else {
                    $design = Design::create([
                        'order_id'            => $order->id,
                        'design_number'       => $order->order_number,
                        'date'                => now()->format('Y-m-d'),
                        'status'              => 'Verified',
                        'notes'               => $request->notes ?? null,
                        'verification_status' => 'approved',
                        'verified_by'         => Auth::id(),
                        'verified_at'         => now(),
                    ]);
                }

                $design->load('items');

                $existingDesignItems = $design->items
                    ->filter(fn($item) => $item->orderItem?->usesPolosanFlow())
                    ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                $components = \App\Models\OrderItemComponent::whereIn(
                    'order_item_id',
                    $polosanOrderItems->pluck('id')
                )
                    ->with('orderItem')
                    ->get();

                $newDesignKeys = [];

                foreach ($components as $component) {
                    $orderItem = $component->orderItem;
                    if (!$orderItem || !$orderItem->usesPolosanFlow()) {
                        continue;
                    }

                    if (in_array($orderItem->id, $polosanToPrintingOrderItemIds)) {
                        continue;
                    }

                    $productId = $component->product_id;
                    $qty       = $component->qty; // ini qty_base
                    $designQty = $getDesignItemQuantity($component, $orderItem);
                    $key       = "{$component->order_item_id}_{$productId}";

                    $newDesignKeys[] = $key;

                    $unitData = $getDesignItemUnitData($orderItem);

                    if ($existingDesignItems->has($key)) {
                        $designItem = $existingDesignItems[$key];

                        $designItem->update(array_merge([
                            'quantity'            => $designQty,
                            'verification_status' => 'approved',
                        ], $unitData));
                    } else {
                        DesignItem::create(array_merge([
                            'design_id'           => $design->id,
                            'order_item_id'       => $orderItem->id,
                            'product_id'          => $productId,
                            'quantity'            => $designQty,
                            'completed_quantity'  => 0,
                            'design_file'         => null,
                            'preview_image'       => null,
                            'verification_status' => 'approved',
                        ], $unitData));
                    }
                }

                foreach ($existingDesignItems as $key => $designItem) {
                    if (!in_array($key, $newDesignKeys)) {
                        $designItem->delete();
                    }
                }

                $orderProgress = $order->orderProgress()->with('items')->first();

                if ($orderProgress) {
                    $orderProgress->update([
                        'date'  => now()->format('Y-m-d'),
                        'notes' => $request->notes ?? $orderProgress->notes,
                    ]);
                } else {
                    $orderProgress = OrderProgress::create([
                        'order_id'       => $order->id,
                        'design_id'      => $design->id,
                        'date'           => now()->format('Y-m-d'),
                        'status'         => 'Completed',
                        'notes'          => null,
                        'invoice_number' => $order->order_number,
                    ]);
                }

                $orderProgress->load('items');

                $existingProgressItems = $orderProgress->items
                    ->filter(fn($item) => $item->orderItem?->usesPolosanFlow())
                    ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                $newProgressKeys = [];

                foreach ($components as $component) {
                    $orderItem = $component->orderItem;
                    if (!$orderItem || !$orderItem->usesPolosanFlow()) {
                        continue;
                    }

                    if (in_array($orderItem->id, $polosanToPrintingOrderItemIds)) {
                        continue;
                    }

                    $productId = $component->product_id;
                    $qty       = $component->qty;
                    $key       = "{$component->order_item_id}_{$productId}";

                    $newProgressKeys[] = $key;

                    $designItem = DesignItem::where('design_id', $design->id)
                        ->where('order_item_id', $orderItem->id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($existingProgressItems->has($key)) {
                        $existingProgressItems[$key]->update([
                            'design_item_id'     => $designItem?->id,
                            'quantity'           => $qty,
                            'completed_quantity' => $qty,
                        ]);

                        $progressItem = $existingProgressItems[$key];
                    } else {
                        $progressItem = OrderProgressItem::create([
                            'order_progress_id'  => $orderProgress->id,
                            'design_item_id'     => $designItem?->id,
                            'order_item_id'      => $orderItem->id,
                            'product_id'         => $productId,
                            'quantity'           => $qty,
                            'completed_quantity' => $qty,
                        ]);
                    }
                }

                foreach ($existingProgressItems as $key => $progressItem) {
                    if (!in_array($key, $newProgressKeys)) {
                        $progressItem->delete();
                    }
                }

                // $deliveryOrder = $order->deliveryOrders()->with('items')->first();

                // if ($deliveryOrder) {
                //     $deliveryOrder->update([
                //         'delivery_date'    => now()->format('Y-m-d'),
                //         'note'             => $request->notes ?? $deliveryOrder->note,
                //         'status'           => 'Ongoing',
                //         'customer'         => $order->customer?->name ?? $deliveryOrder->customer,
                //         'shipping_address' => $order->shipping_address ?? $deliveryOrder->shipping_address,
                //         'google_map_link'  => $order->google_maps ?? $deliveryOrder->google_map_link,
                //     ]);
                // } else {
                //     $deliveryOrder = DeliveryOrder::create([
                //         'order_id'         => $order->id,
                //         'design_id'        => $design->id,
                //         'delivery_number'  => $order->order_number,
                //         'delivery_date'    => now()->format('Y-m-d'),
                //         'note'             => $request->notes ?? null,
                //         'status'           => 'Ongoing',
                //         'customer'         => $order->customer?->name ?? '-',
                //         'shipping_address' => $order->shipping_address,
                //         'google_map_link'  => $order->google_maps,
                //         'created_by'       => Auth::id(),
                //     ]);
                // }

                // $deliveryOrder->load('items');
                // $orderProgress->load('items');

                // $existingDoItems = $deliveryOrder->items
                //     ->filter(fn($item) => $item->orderItem?->mode === 'polosan')
                //     ->keyBy(fn($item) => $item->order_item_id . '_' . $item->product_id);

                // $newDoKeys = [];

                // foreach ($orderProgress->items as $progressItem) {
                //     if ($progressItem->orderItem?->mode !== 'polosan') {
                //         continue;
                //     }

                //     $key = $progressItem->order_item_id . '_' . $progressItem->product_id;
                //     $newDoKeys[] = $key;

                //     if ($existingDoItems->has($key)) {
                //         $existingDoItems[$key]->update([
                //             'order_progress_id'      => $orderProgress->id,
                //             'order_progress_item_id' => $progressItem->id,
                //             'design_item_id'         => $progressItem->design_item_id,
                //             'status'                 => 'Completed',
                //             'progress_qty'           => $progressItem->quantity,
                //             'ready_qty'              => $progressItem->quantity,
                //             'shipped_qty'            => $existingDoItems[$key]->shipped_qty ?? 0,
                //             'note'                   => null,
                //         ]);
                //     } else {
                //         DeliveryOrderItem::create([
                //             'delivery_order_id'      => $deliveryOrder->id,
                //             'order_progress_id'      => $orderProgress->id,
                //             'order_item_id'          => $progressItem->order_item_id,
                //             'order_progress_item_id' => $progressItem->id,
                //             'design_item_id'         => $progressItem->design_item_id,
                //             'product_id'             => $progressItem->product_id,
                //             'status'                 => 'Completed',
                //             'progress_qty'           => $progressItem->quantity,
                //             'ready_qty'              => $progressItem->quantity,
                //             'shipped_qty'            => 0,
                //             'note'                   => null,
                //         ]);
                //     }
                // }

                // foreach ($existingDoItems as $key => $doItem) {
                //     if (!in_array($key, $newDoKeys)) {
                //         $doItem->delete();
                //     }
                // }
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
                        // $totalCogs += $avgCost * $orderItem->quantity;
                        // $totalFixedCost += $fixedCost * $orderItem->quantity;
                        $costQty = $orderItem->qty_base ?? $orderItem->quantity;

                        $totalCogs += $avgCost * $costQty;
                        $totalFixedCost += $fixedCost * $costQty;
                    } elseif ($orderItem->product_bundle_id) {
                        // Produk bundle
                        $bundle = $orderItem->productBundle;

                        $bundleAvgCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;

                            return ($product->avg_cost ?? 0) * ($bundleItem->quantity ?? 1);
                        });

                        $bundleFixedCost = $bundle->items->sum(function ($bundleItem) {
                            $product = $bundleItem->product;

                            return ($product->fixed_cost ?? 0) * ($bundleItem->quantity ?? 1);
                        });

                        $costQty = $orderItem->qty_base ?? $orderItem->quantity;

                        $totalCogs += $bundleAvgCost * $costQty;
                        $totalFixedCost += $bundleFixedCost * $costQty;
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


            // if ($order->mode === 'polosan') {
            //     foreach ($order->orderItems as $item) {
            //         if ($item->satuan === 'satuan' && $item->product_id) {
            //             $productionStock = ProductionStock::firstOrCreate(
            //                 ['product_id' => $item->product_id],
            //                 ['available_quantity' => 0]
            //             );
            //             $productionStock->increment('available_quantity', $item->quantity);
            //         } elseif ($item->satuan === 'bundle' && $item->product_bundle_id) {
            //             $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
            //             if ($bundle) {
            //                 foreach ($bundle->items as $bundleItem) {
            //                     if (!$bundleItem->product_id) continue;
            //                     $productionStock = ProductionStock::firstOrCreate(
            //                         ['product_id' => $bundleItem->product_id],
            //                         ['available_quantity' => 0]
            //                     );
            //                     $productionStock->increment('available_quantity', $item->quantity);
            //                 }
            //             }
            //         }
            //     }
            // }

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
            // 🔁 Handle transaksi keuangan (BANK, SALE, DEPOSIT)
            // ======================================================
            $transactions = AccountTransaction::where('order_id', $order->id)->get();

            $customerDepositAccount = Account::where('type', 'Customer Deposit')->first();
            $customer = $order->customer;

            // Hitung total transaksi deposit yang harus DIKEMBALIKAN
            $totalDepositUsed = 0;

            foreach ($transactions as $trx) {

                $account = Account::find($trx->account_id);
                if (!$account) continue;

                // ======================================================
                // 🔹 Jika transaksi adalah Customer Deposit → ROLLBACK
                // ======================================================
                if ($account->type === 'Customer Deposit') {

                    // Kembalikan saldo deposit customer
                    $refundAmount = $trx->credit ?? 0; // deposit_used dicatat sebagai CREDIT
                    $totalDepositUsed += $refundAmount;

                    // Hapus transaksi deposit
                    $trx->delete();

                    continue;
                }

                // ======================================================
                // 🔹 SALE ACCOUNT (dibatalkan)
                // ======================================================
                if ($account->type === 'Sale Account') {
                    // rollback SALDO SALE ACCOUNT
                    $account->closing_balance += ($trx->debit ?? 0);
                    $account->closing_balance -= ($trx->credit ?? 0);
                    $account->save();

                    $trx->delete();
                    continue;
                }

                // ======================================================
                // 🔹 BANK / CASH → TIDAK DIHAPUS, hanya ditandai
                // ======================================================
                $trx->order_id = null;
                $trx->note = trim(($trx->note ?? '') . ' [Order deleted]');
                $trx->save();
            }

            // ======================================================
            // 🔁 ROLLBACK CUSTOMER DEPOSIT
            // ======================================================
            if ($totalDepositUsed > 0) {

                // Tambahkan kembali ke customer
                $customer->customer_deposit += $totalDepositUsed;
                $customer->save();

                // Kurangi closing balance akun deposit
                if ($customerDepositAccount) {
                    $customerDepositAccount->closing_balance += $totalDepositUsed;
                    $customerDepositAccount->save();
                }
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
            $order = Order::with([
                'orderItems.components.product'
            ])->findOrFail($id);

            $hasSaleReturn = SaleReturn::where('sale_order_id', $order->id)->exists();
            if ($hasSaleReturn) {
                DB::rollBack();
                $msg = 'Tidak dapat menghapus order ini karena sudah memiliki sale return.';
                return $this->deleteResponse($request, false, $msg);
            }

            $inventoryWarehouseId  = $request->inventory_warehouse_id  ?? 1;
            $productionWarehouseId = $request->production_warehouse_id ?? 2;

            $hasProgressHistory = OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->exists();

            /**
             * 1️⃣ Hitung quantity balik ke 0 dan implementasikan ke semua stok
             */
            // 🧱 Tambah sebelum foreach
            $processedProducts = []; // penanda produk yang sudah rollback (hindari double)
            foreach ($order->orderItems as $item) {

                /**
                 * 🧩 HANDLE PRODUK SATUAN
                 * ------------------------------------------------------
                 * Produk satuan dilewati di kode kamu sebelumnya,
                 * jadi stok inventory gak pernah balik.
                 */
                // 🧩 HANDLE PRODUK SATUAN
                // ------------------------------------------------------
                if ($item->satuan === 'satuan' && $item->product_id) {

                    // ✅ Cegah double rollback kalau produk sudah di-handle
                    if (in_array($item->product_id, $processedProducts)) {
                        Log::debug('Skip rollback SATUAN (already processed)', [
                            'product_id' => $item->product_id,
                        ]);
                        continue;
                    }

                    $qty = (float) $item->quantity;

                    // ============================
                    // 1) Cek jejak produksi dulu
                    // ============================
                    $hasVerifiedDesign = \App\Models\Design::where('order_id', $order->id)
                        ->whereRaw('LOWER(status) = ?', ['verified'])
                        ->exists();

                    $assignedQty = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->whereHas('progressItem', fn($q) => $q->where('product_id', $item->product_id))
                        ->sum('assigned_quantity');

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

                    // ===============================================
                    // 2) INVENTORY SELALU DI-ROLLBACK
                    //    (order SATUAN SELALU ngurangin inventory)
                    // ===============================================
                    $inventoryStock = InventoryStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'inventory_warehouse_id' => $inventoryWarehouseId],
                        ['stock_after_sales' => 0]
                    );
                    $inventoryStock->stock_after_sales += $qty;
                    $inventoryStock->save();

                    $skipProduction = (
                        !$hasVerifiedDesign &&
                        $assignedQty <= 0 &&
                        $producedQty <= 0 &&
                        $shippedQty <= 0
                    );

                    // ❗ MODE POLOSAN jangan pernah skip
                    if ($order->mode !== 'polosan' && $skipProduction) {

                        Log::warning('SKIP rollback PRODUCTION SATUAN — order ini belum menyentuh produksi', [
                            'order_id'   => $order->id,
                            'product_id' => $item->product_id,
                            'qty'        => $qty,
                        ]);

                        $processedProducts[] = $item->product_id;
                        continue;
                    }


                    // ===============================================
                    // 4) Mulai ROLLBACK PRODUKSI, karena ada jejak
                    // ===============================================
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
                        // MODE PRINTING
                        if ($hasVerifiedDesign) {
                            // 1) Verified → turunkan pending by base qty
                            $ps->pending_waiting_list -= $qty;

                            // 2) Ada assign → balikin ke available
                            if ($hasProgressHistory && $assignedQty > 0) {
                                $ps->available_quantity += $assignedQty;
                            }

                            // 3) Ada produksi → kurangi finished
                            if ($producedQty > 0) {
                                $ps->finished_product_stock -= $producedQty;
                            }

                            // 4) Ada shipped → finished naik lagi sebesar shipped
                            if ($shippedQty > 0) {
                                $ps->finished_product_stock += $shippedQty;
                            }
                        }
                    } else {
                        // MODE POLOSAN → kembalikan available by base qty
                        $ps->available_quantity += $qty;
                    }

                    // pastikan gak negatif
                    foreach (['available_quantity', 'finished_product_stock', 'pending_waiting_list'] as $f) {
                        $ps->{$f} = max(0, (float) $ps->{$f});
                    }
                    $ps->save();

                    Log::debug('Force delete rollback SATUAN', [
                        'order_id'          => $order->id,
                        'product_id'        => $item->product_id,
                        'qty'               => $qty,
                        'avail'             => $ps->available_quantity,
                        'pending'           => $ps->pending_waiting_list,
                        'finish'            => $ps->finished_product_stock,
                        'stock_after_sales' => $inventoryStock->stock_after_sales,
                    ]);

                    // ✅ Tandai sudah diproses biar gak double dari bundle
                    $processedProducts[] = $item->product_id;

                    continue; // lanjut ke item berikutnya
                }

                /**
                 * 🧩 HANDLE PRODUK BUNDLE
                 * ------------------------------------------------------
                 */
                if ($item->satuan !== 'bundle' || !$item->product_bundle_id) {
                    continue;
                }

                $bundle = ProductBundle::with('items.product')->find($item->product_bundle_id);
                if (!$bundle) continue;

                foreach ($bundle->items as $bundleItem) {
                    if (!$bundleItem->product_id) continue;

                    // Hitung total qty aktual komponen bundle
                    $componentQty = (float) $item->quantity * ($bundleItem->quantity ?? 1);

                    /**
                     * ======================================================
                     * 1) INVENTORY ALWAYS ROLLBACK — tanpa syarat
                     *    (Order SATUAN & BUNDLE SELALU mengurangi inventory)
                     * ======================================================
                     */
                    $inventoryStock = InventoryStock::firstOrCreate(
                        [
                            'product_id'             => $bundleItem->product_id,
                            'inventory_warehouse_id' => $inventoryWarehouseId
                        ],
                        ['stock_after_sales' => 0]
                    );
                    $inventoryStock->stock_after_sales += $componentQty;
                    $inventoryStock->save();

                    // Kalau produk sudah di-handle oleh SATUAN → skip
                    if (in_array($bundleItem->product_id, $processedProducts)) {
                        // Tapi masih harus kurangi pending kalau sudah verified
                        $hasVerifiedDesign = \App\Models\Design::where('order_id', $order->id)
                            ->whereRaw('LOWER(status) = ?', ['verified'])
                            ->exists();

                        if ($order->mode === 'printing' && $hasVerifiedDesign) {
                            $ps = ProductionStock::firstOrCreate(
                                [
                                    'product_id'             => $bundleItem->product_id,
                                    'production_warehouse_id' => $productionWarehouseId
                                ],
                                [
                                    'available_quantity'     => 0,
                                    'finished_product_stock' => 0,
                                    'pending_waiting_list'   => 0,
                                    'canceled_product_stock' => 0,
                                ]
                            );

                            $ps->pending_waiting_list = max(0, $ps->pending_waiting_list - $componentQty);
                            $ps->save();

                            Log::debug('Decrement pending (skip bundle handled by single)', [
                                'component_id' => $bundleItem->product_id,
                                'bundle_id' => $item->product_bundle_id,
                                'componentQty' => $componentQty,
                                'pending_after' => $ps->pending_waiting_list,
                            ]);
                        }

                        continue;
                    }

                    /**
                     * ======================================================
                     * 2) CEK JEJAK PRODUKSI → kalau TIDAK ADA → SKIP produksi
                     * ======================================================
                     */
                    $hasVerifiedDesign = \App\Models\Design::where('order_id', $order->id)
                        ->whereRaw('LOWER(status) = ?', ['verified'])
                        ->exists();

                    $assignedQty = \App\Models\OrderProgressAssign::whereHas('progressItem.progress', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->whereHas('progressItem', fn($q) => $q->where('product_id', $bundleItem->product_id))
                        ->sum('assigned_quantity');

                    $producedQty = \App\Models\OrderProgressHistory::whereHas('progressItem.progress', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->whereHas('progressItem', fn($q) => $q->where('product_id', $bundleItem->product_id))
                        ->selectRaw('COALESCE(SUM(completed_quantity + defect_quantity + reject_quantity), 0) as total')
                        ->value('total');

                    $shippedQty = \App\Models\DeliveryOrderItem::whereHas('deliveryOrder', function ($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                        ->where('product_id', $bundleItem->product_id)
                        ->sum('shipped_qty');

                    $skipProduction = (
                        !$hasVerifiedDesign &&
                        $assignedQty <= 0 &&
                        $producedQty <= 0 &&
                        $shippedQty <= 0
                    );

                    // ❗ MODE POLOSAN tidak boleh skip
                    if ($order->mode !== 'polosan' && $skipProduction) {

                        Log::warning("SKIP rollback PRODUCTION BUNDLE — order belum menyentuh produksi", [
                            'order_id' => $order->id,
                            'component_id' => $bundleItem->product_id,
                            'componentQty' => $componentQty,
                        ]);

                        $processedProducts[] = $bundleItem->product_id;
                        continue;
                    }


                    /**
                     * ======================================================
                     * 3) MULAI ROLLBACK PRODUKSI — karena ada jejak produksi
                     * ======================================================
                     */
                    $ps = ProductionStock::firstOrCreate(
                        ['product_id' => $bundleItem->product_id, 'production_warehouse_id' => $productionWarehouseId],
                        [
                            'available_quantity'     => 0,
                            'finished_product_stock' => 0,
                            'pending_waiting_list'   => 0,
                            'canceled_product_stock' => 0,
                        ]
                    );

                    if ($order->mode === 'printing') {

                        if ($hasVerifiedDesign) {
                            // 1) Verified — turunkan pending
                            $ps->pending_waiting_list -= $componentQty;

                            // 2) Assigned rollback
                            if ($hasProgressHistory && $assignedQty > 0) {
                                $ps->available_quantity += $assignedQty;
                            }

                            // 3) Produced rollback
                            if ($producedQty > 0) {
                                $ps->finished_product_stock -= $producedQty;
                            }

                            // 4) Shipped rollback
                            if ($shippedQty > 0) {
                                $ps->finished_product_stock += $shippedQty;
                            }
                        }
                    } else {
                        // MODE POLOSAN
                        $ps->available_quantity += $componentQty;
                    }

                    // pastikan tidak negatif
                    foreach (['available_quantity', 'finished_product_stock', 'pending_waiting_list'] as $field) {
                        $ps->{$field} = max(0, (float) $ps->{$field});
                    }
                    $ps->save();

                    Log::debug('Force delete rollback BUNDLE', [
                        'bundle_id' => $item->product_bundle_id,
                        'component_id' => $bundleItem->product_id,
                        'component_qty' => $componentQty,
                        'assigned_qty' => $assignedQty,
                        'avail' => $ps->available_quantity,
                        'pending' => $ps->pending_waiting_list,
                        'finish' => $ps->finished_product_stock,
                        'stock_after_sales' => $inventoryStock->stock_after_sales,
                    ]);

                    $processedProducts[] = $bundleItem->product_id;
                }
            }

            /**
             * 2️⃣ Hapus relasi + transaksi (tetap force delete semua)
             */

            // 🔹 DEFECT PRODUCT - Hapus berdasarkan order_progress_history
            \App\Models\DefectProduct::whereHas('orderProgressHistory2.progressItem.progress', function ($q) use ($order) {
                $q->where('order_id', $order->id);
            })->delete();

            // 🔹 REJECT PRODUCT  
            \App\Models\RejectProduct::whereHas('orderProgress', fn($q) => $q->where('order_id', $order->id))
                ->orWhereHas('orderProgressBatch.orderProgress', fn($q) => $q->where('order_id', $order->id))
                ->delete();

            // ======================================================
            // 🔥 ROLLBACK TRANSAKSI KEUANGAN (FORCE DELETE MODE)
            // ======================================================
            $transactions = AccountTransaction::where('order_id', $order->id)->get();

            $customer = $order->customer;
            $customerDepositAccount = Account::where('type', 'Customer Deposit')->first();

            foreach ($transactions as $trx) {

                $account = Account::find($trx->account_id);
                if (!$account) continue;

                $debit  = (float) $trx->debit;
                $credit = (float) $trx->credit;

                switch ($account->type) {

                    case 'Customer Deposit':
                        // deposit_used dicatat CREDIT
                        // deposit_topup dicatat DEBIT

                        if ($credit > 0) {
                            // deposit_used → dikembalikan
                            if ($customer) {
                                $customer->customer_deposit -= $credit;
                                $customer->save();
                            }
                            $customerDepositAccount->closing_balance -= $credit;
                        }

                        if ($debit > 0) {
                            // deposit topup → dihapus (saldo berkurang)
                            if ($customer) {
                                $customer->customer_deposit += $debit;
                                $customer->save();
                            }
                            $customerDepositAccount->closing_balance += $debit;
                        }

                        $customerDepositAccount->save();
                        break;

                    case 'Sale Account':
                        // SALE ACCOUNT always:
                        // debit = paid
                        // credit = sale order total
                        $account->closing_balance += $debit;
                        $account->closing_balance -= $credit;
                        $account->save();
                        break;

                    case 'Cash':
                    case 'Bank':
                        // bank/cash rollback:
                        $account->closing_balance -= $debit; // uang masuk → rollback
                        $account->closing_balance += $credit; // uang keluar → rollback
                        $account->save();
                        break;

                    default:
                        // account lain rollback standar
                        $account->closing_balance -= $debit;
                        $account->closing_balance += $credit;
                        $account->save();
                        break;
                }

                // HAPUS transaksi ini total
                $trx->delete();
            }

            // AccountTransaction::where('order_id', $order->id)->delete();
            $batches = OrderProgressAssignBatch::whereHas(
                'orderProgress',
                fn($q) => $q->where('order_id', $order->id)
            )->get();

            foreach ($batches as $batch) {
                $batch->delete(); // 🔥 INI YANG MEMICU booted()
            }

            OrderProgressHistory::whereHas('progressItem.progress', fn($q) => $q->where('order_id', $order->id))->delete();
            OrderProgressItem::whereHas('progress', fn($q) => $q->where('order_id', $order->id))->delete();
            OrderProgress::where('order_id', $order->id)->delete();
            OrderItem::where('order_id', $order->id)->delete();
            OrderEditHistory::where('order_id', $order->id)->delete();
            // Design::withTrashed()->where('order_id', $order->id)->delete();
            $designs = Design::withTrashed()->where('order_id', $order->id)->get();
            foreach ($designs as $design) {
                // kalau ada relasi items, ini opsional tapi lebih aman
                if (method_exists($design, 'items')) {
                    $design->items()->withTrashed()->delete(); // atau ->delete() kalau items soft delete
                }

                $design->delete(); // hard delete
            }

            FinancialReport::withTrashed()->where('reference_table', 'orders')->where('reference_id', $order->id)->delete();
            // 🔹 Delivery Order dan List
            $deliveryOrders = DeliveryOrder::with(['items', 'shipments'])->where('order_id', $order->id)->get();
            foreach ($deliveryOrders as $do) {
                if (method_exists($do, 'items')) $do->items()->delete();
                if (method_exists($do, 'shipments')) $do->shipments()->delete();
                $do->delete();
            }

            $deliveryLists = DeliveryList::with(['items'])
                ->whereHas('deliveryOrder', fn($q) => $q->where('order_id', $order->id))
                ->get();
            foreach ($deliveryLists as $dl) {
                if (method_exists($dl, 'items')) $dl->items()->delete();
                $dl->delete();
            }

            /**
             * 3️⃣ FORCE DELETE ORDER
             */
            $order->delete_notes = $request->input('delete_notes');
            $order->deleted_by   = Auth::id();
            $order->saveQuietly();
            $order->delete();

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
            'cash_bank_account_id' => 'nullable|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
            'payment_proof'        => 'nullable|array',
            'payment_proof.*'      => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'       => 'nullable|array',
            'deposit_used' => 'nullable|numeric|min:0',
            'use_write_off_only' => 'nullable|boolean',
        ]);

        $exists = (float) $request->paid_amount > 0
            && AccountTransaction::where('order_id', $request->order_id)
                ->where('account_id', $request->cash_bank_account_id)
                ->where('debit', $request->paid_amount)
                ->where('transaction_date', $request->transaction_date)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi duplikat terdeteksi.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $order = Order::with('customer')->findOrFail($request->order_id);
            $paidAmount = (float) $request->paid_amount;
            $depositUsed = (float) ($request->deposit_used ?? 0);
            $outstandingAmount = max(0, (float) $order->grand_total - (float) $order->paid_amount);

            if (($paidAmount + $depositUsed) > $outstandingAmount) {
                throw new \RuntimeException('Total pembayaran dan deposit tidak boleh melebihi remaining.');
            }

            if ($depositUsed > (float) ($order->customer?->customer_deposit ?? 0)) {
                throw new \RuntimeException('Customer deposit tidak mencukupi.');
            }

            if (($paidAmount + $depositUsed) <= 0 && !$request->boolean('use_write_off_only')) {
                throw new \RuntimeException('Isi pembayaran, gunakan customer deposit, atau pilih write off.');
            }

            if ($paidAmount > 0 && !$request->filled('cash_bank_account_id')) {
                throw new \RuntimeException('Pilih cash atau bank account untuk pembayaran.');
            }

            // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
            // $groupId = Str::uuid();

            $bankGroupId = Str::uuid();
            $depositGroupId = Str::uuid();
            $writeOffGroupId = Str::uuid();

            $saleAccount = Account::findOrFail($request->transaction_type); // Akun pembelian (debit)
            $cashBankAccount = $paidAmount > 0
                ? Account::findOrFail($request->cash_bank_account_id)
                : null; // Akun kas/bank (kredit)

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            if ($request->hasFile('payment_proof')) {
                $uploadPath = base_path('uploads/payment_proofs');

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

            // Transaksi KREDIT (kas/bank berkurang)
            // ===============================
            // 🔹 Hanya buat transaksi bank jika paid_amount > 0
            // ===============================
            if ($paidAmount > 0) {
                AccountTransaction::create([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'transaction_date' => $request->transaction_date,
                    'account_id' => $cashBankAccount->id,
                    'debit' => $paidAmount,
                    'credit' => 0,
                    'note' => $request->note ?? '',
                    'particular' => $saleAccount->name . ' - ' . $saleAccount->type,
                    'transaction_group_id' => $bankGroupId,
                    'proof' => $proofJson,
                ]);

                $cashBankAccount->closing_balance += $paidAmount;
                $cashBankAccount->save();
            }

            if ($depositUsed > 0) {

                // Kurangi deposit customer
                $order->customer->customer_deposit -= $depositUsed;
                $order->customer->save();

                // Buat transaksi pengurangan deposit
                $customerDepositAccount = Account::where('type', 'Customer Deposit')->firstOrFail();

                AccountTransaction::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'order_number' => $order->order_number,
                    'transaction_date' => $request->transaction_date,
                    'account_id' => $customerDepositAccount->id,
                    'debit' => $depositUsed,
                    'credit' => 0, // deposit berkurang
                    'note' => 'Deposit used for payment',
                    'particular' => 'Use Deposit',
                    'transaction_group_id' => $depositGroupId,
                    'proof' => $proofJson,
                ]);

                $customerDepositAccount->closing_balance -= $depositUsed;
                $customerDepositAccount->save();
            }

            // Update nilai paid_amount di orders (bertambah)
            $order->paid_amount += $paidAmount + $depositUsed;
            $order->remaining_amount = max(0, $order->grand_total - $order->paid_amount);

            // Kalau sebelumnya Unpaid, bisa ubah jadi Partially Paid atau Paid        

            // ===============================
            // 🔥 WRITE OFF (jika checkbox dicentang)
            // ===============================
            if ($request->has('use_write_off_only') && in_array($request->use_write_off_only, ['on', '1', 'true', true])) {
                $writeOffAmount = $order->remaining_amount;

                if ($writeOffAmount > 0) {
                    // Cari/buat akun Expense untuk Write Off
                    $expenseAccount = Account::firstOrCreate(
                        ['type' => 'Write Off'],
                        [
                            'name' => 'Expense',
                            'opening_balance' => 0,
                            'closing_balance' => 0,
                        ]
                    );

                    // Transaksi DEBIT di Expense Account (biaya bertambah)
                    AccountTransaction::create([
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'transaction_date' => $request->transaction_date,
                        'account_id' => $expenseAccount->id,
                        'debit' => $writeOffAmount,
                        'credit' => 0,
                        'note' => 'Write off remaining balance',
                        'particular' => 'Write Off - ' . $order->order_number,
                        'transaction_group_id' => $writeOffGroupId,
                        'proof' => $proofJson,
                    ]);

                    $expenseAccount->closing_balance += $writeOffAmount;
                    $expenseAccount->save();

                    $order->remaining_amount = 0;
                    $order->payment_status = 'Paid';
                }
            } else {
                $order->updatePaymentStatus();
            }

            $order->payment_method = $saleAccount->type;
            $order->verified = false;
            $order->save();

            // =======================================
            // CEK OVERDUE (HANYA VISUAL)
            // =======================================
            $isOverdue = false;

            if ($order->due_date) {
                $due = Carbon::parse($order->due_date)->endOfDay();
                if (now()->gt($due) && !in_array($order->payment_status, ['Paid', 'Overpaid'])) {
                    $isOverdue = true;
                }
            }


            // =======================================
            // PAYMENT STATUS BADGE (beda kolom!)
            // =======================================
            $statusClass = match (strtolower($order->payment_status)) {
                'paid' => 'bg-soft-success text-success',
                'overpaid' => 'bg-soft-primary text-primary',
                'partially paid' => 'bg-soft-warning text-warning',
                default => 'bg-soft-dark text-dark',
            };

            $paymentStatusHtml = '<span class="badge ' . $statusClass . '">' . ucfirst($order->payment_status) . '</span>';

            if ($isOverdue) {
                $paymentStatusHtml .= '<span class="badge bg-soft-danger text-danger ms-1">Overdue</span>';
            }

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => 'Pembayaran berhasil disimpan.',
                'order'   => [
                    'id'               => $order->id,
                    'order_number'     => $order->order_number,
                    'customer'         => optional($order->customer)->name ?? '-',
                    'grand_total'      => number_format($order->grand_total, 0, ',', '.'),
                    // 'paid_amount'      => number_format($order->paid_amount, 0, ',', '.'),
                    'paid_amount' =>
                    '<div class="text-success">Rp ' . number_format($order->paid_amount, 0, ',', '.') . '</div>
                        <small class="text-danger">Remaining: Rp ' . number_format($order->remaining_amount, 0, ',', '.') . '</small>',
                    // 'remaining_amount' => number_format($order->remaining_amount, 0, ',', '.'),
                    'payment_status'   => $paymentStatusHtml,
                    'mode'             => $order->mode,
                    'notes'            => $order->notes,
                    'created_at'       => $order->created_at->format('Y-m-d H:i:s'),
                    'action'           => view('erp.pages.sales.sale-list.partials.action-button', [
                        'order' => $order
                    ])->render(), // 🔥 ini penting
                    'products'         => $order->orderItems->map(function ($item) {
                        return [
                            'name'         => $item->product->name ?? '-',
                            'sku'          => $item->product->sku ?? '-',
                            'qty'          => $item->quantity,
                            'price'        => number_format($item->price, 0, ',', '.'),
                            'ready_qty'    => $item->ready_qty ?? 0,
                            'progress_qty' => $item->progress_qty ?? 0,
                            'delivered'    => $item->delivered_qty ?? 0,
                            'on_delivery'  => $item->on_delivery ?? 0,
                        ];
                    }),
                ],
            ]);
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

    /**
     * Catat kalau upload gambar invoice ke server luar memakan waktu lama.
     *
     * Menunggu jawaban server lain tidak memakai CPU, jadi kejadian ini tidak
     * muncul di grafik resource Hostinger sama sekali. Hanya catatan inilah
     * yang bisa membuktikannya.
     */
    private function logSlowImageUpload(float $startedAt, $orderId, int $payloadBytes): void
    {
        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        if ($elapsedMs < 1000) {
            return;
        }

        Log::channel('performance')->warning('performance.image_upload_slow', [
            'order_id' => $orderId,
            'upload_ms' => round($elapsedMs, 2),
            'payload_kb' => round($payloadBytes / 1024, 1),
        ]);
    }

    public function convertToImage(Request $request)
    {
        try {
            $imageData = $request->input('image');
            $orderId = $request->input('order_id');

            // Debug token
            $uploadToken = config('services.image_upload.token');
            $uploadUrl = config('services.image_upload.url');

            Log::debug('=== UPLOAD DEBUG START ===');
            Log::debug('Order ID', ['order_id' => $orderId]);
            Log::debug('Image data length', ['length' => strlen($imageData)]);

            if (!$uploadToken || !$uploadUrl) {
                throw new \RuntimeException('Invoice image upload service is not configured.');
            }

            // Upload
            Log::debug('Sending request to image server...');

            $invoiceNumber = $request->input('order_number');

            // Panggilan ke server luar ini terjadi di tengah request user, jadi
            // user ikut menunggu. Timeout 30 detik terlalu lama untuk itu:
            // kalau server gambar tersendat, ERP ikut terasa nge-buffer padahal
            // CPU kosong. connectTimeout dipisah supaya masalah jaringan atau
            // DNS gagal cepat, bukan menggantung sampai batas total.
            $uploadStartedAt = hrtime(true);

            $response = Http::connectTimeout((int) config('services.image_upload.connect_timeout', 5))
                ->timeout((int) config('services.image_upload.timeout', 15))
                ->withHeaders([
                    'X-Upload-Token' => $uploadToken,
                ])
                ->asJson()
                ->post($uploadUrl, [
                    'image' => $imageData,
                    'order_id' => $orderId,
                    'invoice_number' => $invoiceNumber,
                ]);

            $this->logSlowImageUpload($uploadStartedAt, $orderId, strlen((string) $imageData));

            Log::debug('Response received', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if (!$response->successful()) {
                Log::error('Upload failed!', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed: ' . $response->body(),
                    'status_code' => $response->status()
                ], 500);
            }

            $result = $response->json();
            Log::debug('Upload success!', $result);

            return response()->json([
                'success' => true,
                'url' => $result['url'],
                'filename' => $result['filename']
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            Log::error('General error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showInvoice($filename)
    {
        // Sanitize filename untuk keamanan
        $filename = basename($filename);

        $filepath = storage_path('app/invoices/' . $filename);

        // Cek apakah file ada
        if (!file_exists($filepath)) {
            abort(404, 'Invoice tidak ditemukan');
        }

        // Return file sebagai response
        return response()->file($filepath, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
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

    public function getInvoiceImage($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        $invoice = Invoice::with('termAndConditions')->first();
        return view('erp.pages.sales.invoice.invoice-image', compact('order', 'invoice'));
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
        $customerDepositAccounts = Account::where('name', 'Customer Deposit')->get();

        return view(
            'erp.pages.sales.sale-list.payment-history',
            compact('order', 'transactions', 'cashAccounts', 'bankAccounts', 'customerDepositAccounts')
        );
    }

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

            foreach ($transactions as $trx) {
                $trx->update(['verified' => false]);
            }
            $order->update(['verified' => false]);

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
                $uploadPath = base_path('uploads/payment_proofs');
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

            // 🔹 Jika paid_amount = 0, hapus semua transaksi dalam group
            if ($request->paid_amount == 0) {

                // rollback account balances
                foreach ($transactions as $trx) {
                    $account = $trx->account;

                    if ($trx->debit > 0) {
                        $account->decrement('closing_balance', $trx->debit);
                    } elseif ($trx->credit > 0) {
                        $account->increment('closing_balance', $trx->credit);
                    }

                    $trx->delete();
                }

                // 🔹 hitung payment lama yg dihapus
                $oldPaid = $transactions->where('debit', '>', 0)->sum('debit');

                // 🔹 update paid_amount order dengan cara decrement
                $newPaidAmount = max(0, $order->paid_amount - $oldPaid);
                $newRemaining  = max(0, $order->grand_total - $newPaidAmount);

                $order->update([
                    'paid_amount'      => $newPaidAmount,
                    'remaining_amount' => $newRemaining,
                    'payment_status'   => $newPaidAmount == 0
                        ? 'Unpaid'
                        : ($newPaidAmount < $order->grand_total ? 'Partially Paid' : 'Paid'),
                ]);

                DB::commit();

                if ($request->ajax()) {
                    return response()->json([
                        'status'  => 'deleted',
                        'message' => 'Payment berhasil dihapus.',
                        'group_id' => $groupId
                    ]);
                }
                return redirect()->back()->with('success', 'Payment dihapus dan status order diperbarui.');
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
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Payment berhasil diperbarui.',
                    'data'    => [
                        'transaction_group_id' => $groupId,
                        'transaction_date'     => \Carbon\Carbon::parse($request->transaction_date)->format('d-m-Y'),
                        'paid_amount'          => number_format($request->paid_amount, 0, ',', '.'),
                        'account_id'           => $cashBankAccount->id,
                        'account_name'         => $cashBankAccount->name,
                        'account_type'         => $cashBankAccount->type,
                        'note'                 => $request->note ?? '',
                        'proofs'               => $uploadedProofs, // array bukti [{file:'uploads/..', note:'..'}, ...]
                        'verified'             => false,
                    ],
                ]);
            }
            return redirect()->back()->with('success', 'Payment berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update payment: ' . $e->getMessage());
        }
    }

    public function verifyPayment($groupId)
    {
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();

            if ($transactions->isEmpty()) {
                return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
            }

            foreach ($transactions as $trx) {
                $trx->update(['verified' => true]);
            }

            $orderId = $transactions->first()->order_id;

            if ($orderId) {
                // 🔍 Ambil semua transaksi dengan order_id yang sama
                $orderTransactions = AccountTransaction::where('order_id', $orderId)->get();

                // 🔎 Hitung berapa yang verified
                $verifiedCount = $orderTransactions->where('verified', true)->count();
                $totalCount = $orderTransactions->count();

                // ✅ Kalau semua transaksi verified → update order
                if ($totalCount > 0 && $verifiedCount === $totalCount) {
                    \App\Models\Order::where('id', $orderId)->update(['verified' => true]);
                } else {
                    // ❌ Kalau masih ada yang belum verified, pastikan order tetap false
                    \App\Models\Order::where('id', $orderId)->update(['verified' => false]);
                }
            }

            return response()->json([
                'message' => 'Payment berhasil diverifikasi.',
                'group_id' => $groupId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal verifikasi payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function unverifyPayment($groupId)
    {
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();

            if ($transactions->isEmpty()) {
                return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
            }

            // 🔴 Set semua jadi FALSE
            foreach ($transactions as $trx) {
                $trx->update(['verified' => false]);
            }

            // Ambil order_id
            $orderId = $transactions->first()->order_id;

            if ($orderId) {
                // Cek apakah ada transaksi lain yang masih verified
                $orderTransactions = AccountTransaction::where('order_id', $orderId)->get();

                $anyVerified = $orderTransactions->contains('verified', true);

                // 🔄 Kalau masih ada yang verified → order tetap true
                // ❌ Kalau tidak ada yang verified → set false
                Order::where('id', $orderId)->update([
                    'verified' => $anyVerified
                ]);
            }

            return response()->json([
                'message' => 'Payment berhasil di-unverify.',
                'group_id' => $groupId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal unverify payment: ' . $e->getMessage(),
            ], 500);
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
            // 🔁 4️⃣ RESTORE TRANSAKSI KEUANGAN (kebalikan delete)
            // ======================================================
            $transactions = AccountTransaction::withTrashed()
                ->where('order_id', $order->id)
                ->orWhere(function ($q) use ($order) {
                    $q->whereNull('order_id')
                        ->where('note', 'like', '%[Order deleted]%');
                })
                ->get();

            $customerDepositAccount = Account::where('type', 'Customer Deposit')->first();
            $customer = $order->customer;

            foreach ($transactions as $trx) {

                $account = Account::find($trx->account_id);
                if (!$account) continue;

                $debit  = (float) $trx->debit;
                $credit = (float) $trx->credit;

                // ======================================================
                // 🔥 1) RESTORE – CUSTOMER DEPOSIT
                // ======================================================
                if ($account->type === 'Customer Deposit') {

                    // pada delete: deposit_used (CREDIT) ditambahkan kembali ke customer
                    // pada restore: deposit_used harus DIKURANGI lagi (kembali normal)
                    if ($credit > 0) {
                        $customer->customer_deposit -= $credit;
                        $customer->save();

                        $customerDepositAccount->closing_balance -= $credit;
                        $customerDepositAccount->save();
                    }

                    // restore soft delete
                    $trx->restore();
                    continue;
                }

                // ======================================================
                // 🔥 2) RESTORE – SALE ACCOUNT
                // ======================================================
                if ($account->type === 'Sale Account') {

                    // delete rollback: debit +, credit -
                    // restore kebalikannya: debit -, credit +
                    $account->closing_balance -= $debit;
                    $account->closing_balance += $credit;
                    $account->save();

                    $trx->restore();
                    continue;
                }

                // ======================================================
                // 🔥 3) RESTORE – BANK / CASH
                // ======================================================
                if (strtolower($account->type) === 'cash' || strtolower($account->type) === 'bank') {

                    // hapus tag deleted
                    $trx->note = trim(str_replace('[Order deleted]', '', $trx->note));

                    // pasangkan lagi ke order
                    $trx->order_id = $order->id;

                    $trx->save();
                    continue;
                }
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
