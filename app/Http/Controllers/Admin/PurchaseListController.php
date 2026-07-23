<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialReport;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\Purchase;
use App\Models\PurchaseEditHistory;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Services\ProductCostService;
use App\Services\UnitConversionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseListController extends Controller
{
    public function getLatestPrice($productId)
    {
        $latestPurchase = PurchaseItem::where('product_id', $productId)
            ->orderByDesc('created_at')
            ->first();

        $latestPrice = $latestPurchase ? $latestPurchase->price : 0;

        return response()->json(['price' => $latestPrice]);
    }

    public function getPurchaseList(Request $request)
    {
        $purchase_number = Purchase::first();
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $defaultAccount = Account::where('is_default_purchase', true)->first();
        $purchaseOrder = null;

        if ($request->integer('purchase_order_id') > 0) {
            $purchaseOrder = Purchase::where('status', 'Purchase Orders')
                ->findOrFail($request->integer('purchase_order_id'));
        }

        return view('erp.pages.purchases.purchase-list.purchase-list', compact('purchase_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'defaultAccount', 'purchaseOrder'));
    }

    public function dataPurchaseList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchases = Purchase::with(['supplier', 'parentPurchase', 'purchaseItems.purchaseProduct', 'inventories.stockIns'])
            ->where('status', 'Purchase List')
            ->orderByDesc('id');

        if ($request->integer('purchase_order_id') > 0) {
            $purchases->where('parent_purchase_id', $request->integer('purchase_order_id'));
        }

        // ✅ Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $purchases->whereDate('purchase_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $purchases->whereMonth('purchase_date', Carbon::now()->month)
                        ->whereYear('purchase_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $purchases->whereYear('purchase_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $purchases->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // ✅ Filter payment status, due date, dan pencarian
        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $purchases->whereIn('payment_status', ['Paid', 'Overpaid']);
            } else {
                $purchases->where('payment_status', $request->payment_status);
            }
        } elseif ($request->search_type === 'due_date') {
            $direction = strtolower($request->due_date_order ?? 'asc');
            if ($direction === 'asc') {
                $purchases->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('due_date', 'asc');
            } else {
                $purchases->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC')
                    ->orderBy('due_date', 'desc');
            }
        } elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'supplier') {
                $purchases->whereHas('supplier', function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search_keyword.'%');
                });
            } else {
                $purchases->where('purchase_number', 'like', '%'.$request->search_keyword.'%');
            }
        }

        // ✅ Hitung total sebelum pagination
        $totalQuery = clone $purchases;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $purchases->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($purchase) {
                $date = Carbon::parse($purchase->purchase_date)->format('j M y H:i');
                $dueDate = $purchase->due_date ? Carbon::parse($purchase->due_date)->format('j M y') : '-';

                // 🏷️ Edited dan Return badge
                $editedBadge = $purchase->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                $returnBadge = $purchase->purchaseReturn()->exists()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Purchase Return</span></div>'
                    : '';

                // 🔥 Cek apakah semua Stock In sudah complete
                $stockInCompleted = InventoryItem::whereHas('inventory', function ($q) use ($purchase) {
                    $q->where('purchase_id', $purchase->id)
                        ->where('note', 'Purchase Account');
                })
                    ->whereColumn('stock_in', '<', 'quantity')
                    ->doesntExist();

                // Icon centang
                $completeIcon = $stockInCompleted
                    ? ' <i class="fa fa-check-circle text-success ms-1"></i>'
                    : '';

                $parentOrderHtml = $purchase->parentPurchase
                    ? '<small class="text-primary">PO: '.e($purchase->parentPurchase->purchase_number).'</small><br>'
                    : '';
                $purchaseNumberHtml = $returnBadge.'
                <div>
                    <div>'.e($purchase->purchase_number).$editedBadge.'</div>
                    '.$parentOrderHtml.'
                    <small class="text-muted">'.$date.'</small>,
                    <small class="text-danger">Due: '.$dueDate.'</small>
                </div>';

                // 👤 Supplier
                $supplier = e($purchase->supplier->name ?? '-');

                // 💰 Total, Paid, Remaining
                // $totalAmount = 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
                // $paidTotal = ($purchase->paid_amount_product ?? 0) + ($purchase->paid_amount_freight ?? 0);
                // $remainingTotal = ($purchase->remaining_amount_product ?? 0) + ($purchase->remaining_amount_freight ?? 0);
                // $paidHtml = '<span class="text-success">Rp ' . number_format($paidTotal, 0, ',', '.') . '</span>';
                // $remainingHtml = '<span class="text-danger">Rp ' . number_format($remainingTotal, 0, ',', '.') . '</span>';

                // 💰 Produk
                $totalProduct = $purchase->total_amount_product ?? 0;
                $paidProduct = $purchase->paid_amount_product ?? 0;
                $remainProduct = $purchase->remaining_amount_product ?? 0;

                // 🚛 Freight
                $totalFreight = $purchase->total_amount_freight ?? 0;
                $paidFreight = $purchase->paid_amount_freight ?? 0;
                $remainFreight = $purchase->remaining_amount_freight ?? 0;

                // Format HTML
                $totalProductHtml = '<span class="text-primary fw-semibold">Rp '.number_format($totalProduct, 0, ',', '.').'</span>';
                $paidProductHtml = '<span class="text-success fw-semibold">Rp '.number_format($paidProduct, 0, ',', '.').'</span>';
                $remainProductHtml = '<span class="text-danger fw-semibold">Rp '.number_format($remainProduct, 0, ',', '.').'</span>';

                $totalFreightHtml = '<span class="text-primary fw-semibold">Rp '.number_format($totalFreight, 0, ',', '.').'</span>';
                $paidFreightHtml = '<span class="text-success fw-semibold">Rp '.number_format($paidFreight, 0, ',', '.').'</span>';
                $remainFreightHtml = '<span class="text-danger fw-semibold">Rp '.number_format($remainFreight, 0, ',', '.').'</span>';

                $paidProductColumn = '
                    <div class="text-success fw-semibold">Rp '.number_format($paidProduct, 0, ',', '.').'</div>'
                    .($remainProduct > 0
                        ? '<small class="text-danger fw-semibold">Remaining: Rp '.number_format($remainProduct, 0, ',', '.').'</small>'
                        : ''
                    );

                $paidFreightColumn = '
                    <div class="text-success fw-semibold">Rp '.number_format($paidFreight, 0, ',', '.').'</div>'
                    .($remainFreight > 0
                        ? '<small class="text-danger fw-semibold">Remaining: Rp '.number_format($remainFreight, 0, ',', '.').'</small>'
                        : ''
                    );

                // 🏷️ Payment Status + Verified check
                $paymentStatus = strtolower($purchase->payment_status);
                $badgeClass = match ($paymentStatus) {
                    'paid' => 'bg-soft-success text-success',
                    'overpaid' => 'bg-soft-primary text-primary',
                    'unpaid' => 'bg-soft-danger text-danger',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default => 'bg-secondary',
                };

                $verifiedIcon = '';
                if ($purchase->verified) {
                    $verifiedIcon = ' <i class="fa fa-check-circle text-success ms-1" title="Verified"></i>';
                }

                $paymentBadge = '<div class="badge '.$badgeClass.'">'.ucfirst($paymentStatus).'</div>'.$verifiedIcon;

                // 💳 Payment method
                $paymentMethod = e($purchase->payment_method ?? '-');

                // 📦 Products list (sudah + tax)
                $items = $purchase->purchaseItems()->with(['purchaseProduct' => fn ($q) => $q->withTrashed()])->get();
                $taxPercent = $purchase->tax_percent ?? 0;
                $products = $items->map(function ($item) use ($taxPercent) {
                    $price = $item->price ?? 0;
                    $freight = $item->freight ?? 0;
                    $priceWithTax = $price + ($price * ($taxPercent / 100));
                    $total = ($priceWithTax + $freight) * $item->quantity;

                    $stockInBase = InventoryItem::where('purchase_item_id', $item->id)->sum('stock_in');
                    $stockIn = $stockInBase / max(1, $item->unit_conversion_value ?? 1);

                    return [
                        'name' => $item->purchaseProduct->name ?? '-',
                        'sku' => $item->purchaseProduct->sku ?? '-',
                        'qty' => number_format($item->quantity, 0, ',', '.').' '.$item->unit_name,
                        'stock_in' => number_format($stockIn, 0, ',', '.'), // jika perlu ditampilkan
                        'price' => number_format($priceWithTax, 0, ',', '.'),
                        'freight' => number_format($freight, 0, ',', '.'),
                        'total_price' => number_format($priceWithTax + $freight, 0, ',', '.'),
                        'total' => number_format($total, 0, ',', '.'),
                    ];
                })->toArray();

                // 🏷️ Status
                $status = strtolower($purchase->status);
                $statusBadge = match ($status) {
                    'purchase orders' => '<div class="badge bg-soft-warning text-warning">'.e($purchase->status).'</div>',
                    'purchase list' => '<div class="badge bg-soft-success text-success">'.e($purchase->status).'</div>',
                    default => '<div class="badge bg-secondary">'.e($purchase->status).'</div>',
                };

                // ⚙️ Action Button Partial
                $purchase->is_fully_returned = $purchase->purchaseItems->every(function ($item) use ($purchase) {
                    $returnedQty = PurchaseReturnItem::where('product_id', $item->product_id)
                        ->whereHas('purchaseReturn', function ($q) use ($purchase) {
                            $q->where('purchase_id', $purchase->id);
                        })->sum('quantity');

                    return $returnedQty >= $item->quantity;
                });

                $actionHtml = view('erp.pages.purchases.purchase-list.partials.action-button', compact('purchase'))->render();

                return [
                    'id' => $purchase->id,
                    'purchase_number' => $purchaseNumberHtml,
                    'purchase_date' => $date,
                    'supplier' => '
                        <div style="white-space: normal; word-break: break-word; max-width:180px;">
                            <div class="d-flex align-items-center fw-semibold">
                                '.($stockInCompleted ? '<i class="fa fa-check-circle text-success me-1"></i>' : '').'
                                '.e($purchase->supplier->name ?? '-').'
                            </div>
                            <small class="text-muted">Supplier</small>
                        </div>
                    ',
                    // 'total_amount' => $totalAmount,
                    // 'paid_amount' => $paidHtml,
                    // 'remaining_amount' => $remainingHtml,
                    'total_amount_product' => $totalProductHtml,
                    'paid_amount_product' => $paidProductColumn,
                    'remaining_amount_product' => $remainProductHtml,

                    'total_amount_freight' => $totalFreightHtml,
                    'paid_amount_freight' => $paidFreightColumn,
                    'remaining_amount_freight' => $remainFreightHtml,
                    'payment_status' => $paymentBadge,
                    'payment_method' => $paymentMethod,
                    'products' => $products,
                    'status' => $statusBadge,
                    'action' => $actionHtml,
                    'user' => $purchase->user->name ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function dataDeletedPurchaseList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchases = Purchase::onlyTrashed()
            ->with(['supplier', 'purchaseItems.purchaseProduct', 'deletedByUser'])
            ->where('status', 'Purchase List')
            ->orderByDesc('deleted_at');

        if ($request->integer('purchase_order_id') > 0) {
            $purchases->where('parent_purchase_id', $request->integer('purchase_order_id'));
        }

        // Filter tanggal (based on purchase_date)
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $purchases->whereDate('purchase_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $purchases->whereMonth('purchase_date', Carbon::now()->month)
                        ->whereYear('purchase_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $purchases->whereBetween('purchase_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $purchases->whereYear('purchase_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $purchases->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // 🔎 Search keyword (purchase number, supplier, deleted_by, notes)
        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword;

            $purchases->where(function ($q) use ($keyword) {
                $q->where('purchase_number', 'like', "%$keyword%")
                    ->orWhere('delete_notes', 'like', "%$keyword%")
                    ->orWhereHas(
                        'supplier',
                        fn ($qs) => $qs->where('name', 'like', "%$keyword%")
                    )
                    ->orWhereHas(
                        'deletedByUser',
                        fn ($qs) => $qs->where('name', 'like', "%$keyword%")
                    );
            });
        }

        // ✅ Hitung total sebelum pagination
        $totalQuery = clone $purchases;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $purchases->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($purchase) {
                $date = Carbon::parse($purchase->purchase_date)->format('j M y');
                $deletedAt = $purchase->deleted_at
                    ? $purchase->deleted_at->format('j M y H:i')
                    : '-';

                // 🧾 Nomor + tanggal
                $purchaseNumberHtml = '
                <div>
                    <div>'.e($purchase->purchase_number).'</div>
                    <small class="text-muted">'.$date.'</small>
                </div>';

                // 👤 Supplier
                $supplier = e($purchase->supplier->name ?? '-');

                // 💰 Grand total
                $totalAmount = '<span class="text-primary">Rp '.number_format($purchase->total_amount, 0, ',', '.').'</span>';

                // 📦 Produk list
                $products = $purchase->purchaseItems->map(function ($item) {
                    return [
                        'name' => $item->purchaseProduct?->name ?? '-',
                        'sku' => $item->purchaseProduct?->sku ?? '-',
                        'qty' => number_format($item->quantity ?? 0, 0, ',', '.'),
                        'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 0, ',', '.'),
                        'total_price' => number_format(($item->price ?? 0) + ($item->freight ?? 0), 2, ',', '.'),
                    ];
                })->toArray();

                // 📝 Notes & Deleted Info
                $deleteNotes = e($purchase->delete_notes ?? '-');
                $deletedBy = e($purchase->deletedByUser->name ?? '-');

                // ⚙️ Action Buttons (Owner Only)
                $action = '';
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    $action = '
                    <div class="d-flex gap-2">
                        <button type="button" 
                            class="btn btn-success btn-sm me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRestoreOrder"
                            data-id="'.$purchase->id.'"
                            data-name="'.e($purchase->purchase_number).'"
                            data-url="'.route('purchases.restore', $purchase->id).'">
                                Restore
                        </button>
                        <button type="button" 
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalForceDeleteOrder"
                            data-id="'.$purchase->id.'"
                            data-name="'.e($purchase->purchase_number).'"
                            data-url="'.route('purchases.forceDelete', $purchase->id).'">
                                Hapus Permanen
                        </button>
                    </div>';
                }

                return [
                    'id' => $purchase->id,
                    'purchase_number' => $purchaseNumberHtml,
                    'supplier' => $supplier,
                    'total_amount' => $totalAmount,
                    'deleted_at' => $deletedAt,
                    'products' => $products,
                    'delete_notes' => $deleteNotes,
                    'deleted_by' => $deletedBy,
                    'action' => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    // public function create()
    // {
    //     $products = Products::orderBy('name', 'asc')
    //         ->addSelect([
    //             'last_price' => DB::table('purchase_items as pi')
    //                 ->select('pi.price')
    //                 ->whereColumn('pi.product_id', 'products.id')
    //                 ->where('pi.price', '>', 0)
    //                 ->orderByDesc('pi.id')
    //                 ->limit(1),

    //             'last_freight' => DB::table('purchase_items as pi')
    //                 ->select('pi.freight')
    //                 ->whereColumn('pi.product_id', 'products.id')
    //                 ->where('pi.freight', '>', 0)
    //                 ->orderByDesc('pi.id')
    //                 ->limit(1),
    //         ])
    //         ->get();

    //     // $products = Products::orderBy('name', 'asc')->get();
    //     $suppliers = Supplier::orderBy('name', 'asc')->get();

    //     $transactionTypes = Account::where('name', 'Purchase')->get();
    //     $cashAccounts = Account::where('name', 'Cash')->get();
    //     $bankAccounts = Account::where('name', 'Bank')->get();

    //     return view('erp.pages.purchases.purchase-list.create-purchase', compact('products', 'suppliers', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    // }

    public function create()
    {
        $products = Products::with([
            'unitConversions.unit',
        ])
            ->orderBy('name', 'asc')
            ->addSelect([
                'last_price' => DB::table('purchase_items as pi')
                    ->select('pi.price')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.price', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1),

                'last_freight' => DB::table('purchase_items as pi')
                    ->select('pi.freight')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.freight', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1),
            ])
            ->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'last_price' => $product->last_price,
                'last_freight' => $product->last_freight,

                'units' => $product->unitConversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'unit_id' => $conversion->unit_id,
                        'unit_name' => optional($conversion->unit)->name,
                        'conversion_value' => $conversion->conversion_value,
                        'sale_price' => $conversion->sale_price,
                    ];
                })->values()->toArray(),
            ];
        })->toArray();

        $suppliers = Supplier::orderBy('name', 'asc')->get();

        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-list.create-purchase', compact(
            'products',
            'productsJson',
            'suppliers',
            'transactionTypes',
            'cashAccounts',
            'bankAccounts'
        ));
    }

    public function checkNumber(Request $request)
    {
        $exists = \App\Models\Purchase::where('purchase_number', $request->purchase_number)
            ->where('id', '!=', $request->id)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_number' => 'required|string|unique:purchases,purchase_number',
            'purchase_date' => 'required|date_format:Y-m-d\TH:i',
            'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date' => 'nullable|date',
            'suppliers' => 'required|exists:suppliers,id',
            'product' => 'required|array',
            'product.*' => 'exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'numeric|min:1',
            'price' => 'required|array',
            'price.*' => 'numeric|min:0',
            'freight' => 'required|array',
            'freight.*' => 'numeric|min:0',
            'total' => 'required|array',
            'total.*' => 'numeric|min:0',
            'sub_total' => 'required|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount_product' => 'required|numeric|min:0',
            'total_amount_freight' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'stock_destination' => 'required|in:warehouse,production',
            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',
            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',
            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchaseDate = Carbon::parse($request->purchase_date);

            $dueDate = match ($request->due_date_option) {
                'today' => $purchaseDate,
                '1_week' => $purchaseDate->copy()->addWeek(),
                '1_month' => $purchaseDate->copy()->addMonth(),
                '3_months' => $purchaseDate->copy()->addMonths(3),
                'custom' => $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null,
                default => null
            };

            $status = 'Purchase List';
            $paymentStatus = 'Unpaid';

            $totalProduct = $request->total_amount_product;
            $totalFreight = $request->total_amount_freight;
            $taxPercent = $request->tax_percent ?? 0;

            $taxAmount = ($totalProduct * $taxPercent) / 100;

            $totalProductWithTax = $totalProduct + $taxAmount;

            $grandTotal = $totalProductWithTax + $totalFreight;

            $paidProduct = 0;
            $remainingProduct = $totalProductWithTax;
            $paidFreight = 0;
            $remainingFreight = $totalFreight;

            $purchase = Purchase::create([
                'purchase_number' => $request->purchase_number,
                'purchase_date' => $purchaseDate,
                'due_date' => $dueDate,
                'supplier_id' => $request->suppliers,
                'payment_status' => $paymentStatus,
                'sub_total' => $totalProduct + $totalFreight,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'freight_total' => $totalFreight,
                'total_amount_product' => $totalProductWithTax,
                'paid_amount_product' => $paidProduct,
                'remaining_amount_product' => $remainingProduct,
                'total_amount_freight' => $totalFreight,
                'paid_amount_freight' => $paidFreight,
                'remaining_amount_freight' => $remainingFreight,
                'total_amount' => $grandTotal,
                'paid_amount' => 0,
                'remaining_amount' => $grandTotal,
                'status' => $status,
                'stock_destination' => $request->stock_destination,
                'user_id' => Auth::id(),
            ]);

            $inventoryStatus = match ($request->stock_destination) {
                'warehouse' => 'Stock In',
                'production' => 'Stock In Production',
            };

            foreach ($request->product as $index => $productId) {
                $qty = (float) $request->qty[$index];

                $unit = UnitConversionService::resolve(
                    (int) $productId,
                    $request->product_unit_id[$index] ?? null,
                    $request->unit_name[$index] ?? 'Pcs'
                );
                $unitConversionId = $unit['id'];
                $unitConversionValue = $unit['factor'];
                $unitName = $unit['unit_name'];

                $qtyBase = $qty * $unitConversionValue;
                $price = $request->price[$index];
                $freight = $request->freight[$index];
                $total = $request->total[$index];

                $taxPercent = $request->tax_percent ?? 0;

                $priceAfterTax = $price + ($price * $taxPercent / 100);
                $finalPrice = $priceAfterTax + $freight;

                $product = Products::findOrFail($productId);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,

                    'product_unit_conversion_id' => $unitConversionId,
                    'unit_name' => $unitName,
                    'unit_conversion_value' => $unitConversionValue,
                    'qty_base' => $qtyBase,

                    'inventory_warehouse_id' => $request->stock_destination === 'warehouse'
                        ? ($request->inventory_warehouse_id ?? 1)
                        : null,

                    'production_warehouse_id' => $request->stock_destination === 'production'
                        ? ($request->production_warehouse_id ?? 2)
                        : null,

                    'status' => 'Purchase Account',
                    'quantity' => $qty,
                    'price' => $price,
                    'price_after_tax' => $priceAfterTax,
                    'freight' => $freight,
                    'final_price' => $finalPrice,
                    'subtotal' => $total,
                ]);

                if ($purchase->status === 'Purchase List') {
                    $inventory = Inventory::firstOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'purchase_number' => $purchase->purchase_number,
                            'supplier_id' => $purchase->supplier_id,
                            'date' => $purchase->purchase_date,
                            'status' => $inventoryStatus,
                            'note' => 'Purchase Account',
                        ]
                    );

                    InventoryItem::create([
                        'inventory_id' => $inventory->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'product_id' => $productId,

                        'inventory_warehouse_id' => $request->stock_destination === 'warehouse'
                            ? ($request->inventory_warehouse_id ?? 1)
                            : null,

                        'production_warehouse_id' => $request->stock_destination === 'production'
                            ? ($request->production_warehouse_id ?? 2)
                            : null,

                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,

                        'quantity' => $qty,
                        'price' => $finalPrice,
                        'stock_in' => 0,
                        'remaining_stock_in' => $qtyBase,
                        'stock_out' => 0,
                    ]);

                    // 🧩 Update Stock berdasarkan destination
                    if ($request->stock_destination === 'warehouse') {
                        $inventoryStock = InventoryStock::firstOrCreate(
                            [
                                'product_id' => $productId,
                                'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                            ],
                            ['incoming_stock' => 0]
                        );

                        $inventoryStock->increment('incoming_stock', $qtyBase);
                    }

                    if ($request->stock_destination === 'production') {
                        $productionStock = ProductionStock::firstOrCreate(
                            [
                                'product_id' => $productId,
                                'production_warehouse_id' => $request->production_warehouse_id ?? 2,
                            ],
                            ['incoming_stock' => 0]
                        );

                        $productionStock->increment('incoming_stock', $qty);
                    }
                }
            }

            // =============== ACCOUNT TRANSACTION ===============
            $groupId = Str::uuid();
            $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

            AccountTransaction::create([
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'transaction_date' => $purchase->purchase_date,
                'account_id' => $purchaseAccount->id,
                'debit' => $grandTotal, // hanya product
                'credit' => 0,
                'note' => 'Purchase Account Transaction',
                'particular' => 'Purchase Invoice',
                'transaction_group_id' => $groupId,
                'verified' => 1,
            ]);

            $purchaseAccount->increment('closing_balance', $grandTotal);

            DB::commit();

            return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase store failed: '.$e->getMessage());

            return back()->with('error', 'Purchase order failed to create');
        }
    }

    // public function edit($id)
    // {
    //     $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

    //     // 🔹 Tentukan default due_date_option berdasarkan nilai due_date
    //     $dueDateOption = 'none';
    //     $customDueDate = null;

    //     if ($purchase->due_date) {
    //         $purchaseDate = \Carbon\Carbon::parse($purchase->purchase_date)->startOfDay();
    //         $due = \Carbon\Carbon::parse($purchase->due_date)->startOfDay();

    //         if ($due->equalTo($purchaseDate)) {
    //             $dueDateOption = 'today';
    //         } elseif ($due->equalTo($purchaseDate->copy()->addWeek())) {
    //             $dueDateOption = '1_week';
    //         } elseif ($due->equalTo($purchaseDate->copy()->addMonth())) {
    //             $dueDateOption = '1_month';
    //         } elseif ($due->equalTo($purchaseDate->copy()->addMonths(3))) {
    //             $dueDateOption = '3_months';
    //         } else {
    //             $dueDateOption = 'custom';
    //             $customDueDate = $due->toDateString();
    //         }
    //     }

    //     $products = Products::all();
    //     $suppliers = Supplier::all();

    //     return view('erp.pages.purchases.purchase-list.edit-purchase', compact(
    //         'purchase',
    //         'products',
    //         'suppliers',
    //         'dueDateOption',
    //         'customDueDate'
    //     ));
    // }

    public function edit($id)
    {
        $purchase = Purchase::with([
            'purchaseItems.purchaseProduct.unitConversions.unit',
        ])->findOrFail($id);

        // 🔹 Tentukan default due_date_option berdasarkan nilai due_date
        $dueDateOption = 'none';
        $customDueDate = null;

        if ($purchase->due_date) {
            $purchaseDate = \Carbon\Carbon::parse($purchase->purchase_date)->startOfDay();
            $due = \Carbon\Carbon::parse($purchase->due_date)->startOfDay();

            if ($due->equalTo($purchaseDate)) {
                $dueDateOption = 'today';
            } elseif ($due->equalTo($purchaseDate->copy()->addWeek())) {
                $dueDateOption = '1_week';
            } elseif ($due->equalTo($purchaseDate->copy()->addMonth())) {
                $dueDateOption = '1_month';
            } elseif ($due->equalTo($purchaseDate->copy()->addMonths(3))) {
                $dueDateOption = '3_months';
            } else {
                $dueDateOption = 'custom';
                $customDueDate = $due->toDateString();
            }
        }

        $products = Products::with([
            'unitConversions.unit',
        ])
            ->orderBy('name', 'asc')
            ->addSelect([
                'last_price' => DB::table('purchase_items as pi')
                    ->select('pi.price')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.price', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1),

                'last_freight' => DB::table('purchase_items as pi')
                    ->select('pi.freight')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.freight', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1),
            ])
            ->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'last_price' => $product->last_price,
                'last_freight' => $product->last_freight,
                'units' => $product->unitConversions->map(function ($conversion) {
                    return [
                        'id' => $conversion->id,
                        'unit_id' => $conversion->unit_id,
                        'unit_name' => optional($conversion->unit)->name,
                        'conversion_value' => $conversion->conversion_value,
                        'sale_price' => $conversion->sale_price,
                    ];
                })->values()->toArray(),
            ];
        })->toArray();

        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('erp.pages.purchases.purchase-list.edit-purchase', compact(
            'purchase',
            'products',
            'productsJson',
            'suppliers',
            'dueDateOption',
            'customDueDate'
        ));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'purchase_date' => 'required|date',
            'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date' => 'nullable|date',
            'suppliers' => 'required|exists:suppliers,id',
            'purchase_number' => 'required|string|unique:purchases,purchase_number,'.$id,
            'status' => 'required|string',
            'product' => 'required|array',
            'product.*' => 'exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'numeric|min:1',
            'price' => 'required|array',
            'price.*' => 'numeric|min:0',
            'freight' => 'required|array',
            'freight.*' => 'numeric|min:0',
            'total' => 'required|array',
            'total.*' => 'numeric|min:0',
            'sub_total' => 'required|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount_product' => 'required|numeric|min:0',
            'total_amount_freight' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'edit_note' => 'required|string|max:500',
            'stock_destination' => 'required|in:warehouse,production',

            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',
            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',
            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);
            if ($purchase->parent_purchase_id) {
                $parent = Purchase::with('purchaseItems')->findOrFail($purchase->parent_purchase_id);
                if ((int) $request->suppliers !== (int) $parent->supplier_id) {
                    throw new \RuntimeException('Supplier PL harus sama dengan supplier PO.');
                }
                if ($request->stock_destination !== $parent->stock_destination) {
                    throw new \RuntimeException('Tujuan stok PL harus sama dengan tujuan stok PO.');
                }

                foreach ($request->input('product', []) as $index => $productId) {
                    $existingChildItem = $purchase->purchaseItems->firstWhere('product_id', (int) $productId);
                    if (! $existingChildItem || ! $existingChildItem->source_purchase_item_id) {
                        throw new \RuntimeException('Produk baru tidak dapat ditambahkan ke PL turunan PO.');
                    }

                    $sourceItem = $parent->purchaseItems->firstWhere('id', $existingChildItem->source_purchase_item_id);
                    $allocatedByOtherLists = (float) PurchaseItem::where('source_purchase_item_id', $sourceItem->id)
                        ->where('id', '!=', $existingChildItem->id)
                        ->sum('quantity');
                    $maximum = max(0, (float) $sourceItem->quantity - $allocatedByOtherLists);

                    if ((float) ($request->qty[$index] ?? 0) > $maximum) {
                        throw new \RuntimeException("Qty {$sourceItem->purchaseProduct?->name} melebihi sisa PO ({$maximum}).");
                    }
                }
            }

            // 🚫 Cegah edit jika sudah ada return/stock-in
            if ($purchase->purchaseReturn()->exists()) {
                DB::rollBack();

                return back()->with('error', 'Purchase ini memiliki Purchase Return dan tidak bisa diedit lagi.');
            }
            // if ($purchase->hasStockIn()) {
            //     DB::rollBack();
            //     return back()->with('error', 'Purchase ini sudah memiliki Stock In dan tidak bisa diedit lagi.');
            // }

            // ===== 1️⃣ SNAPSHOT LAMA
            $oldPurchase = $purchase->only([
                'purchase_number',
                'due_date',
                'purchase_date',
                'supplier_id',
                'status',
                'total_amount_product',
                'total_amount_freight',
                'remaining_amount_product',
                'remaining_amount_freight',
            ]);
            $oldItems = $purchase->purchaseItems->mapWithKeys(fn ($i) => [
                $i->product_id => [
                    'product' => $i->purchaseProduct->name ?? 'Unknown',
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                    'freight' => $i->freight,
                    'subtotal' => $i->subtotal,
                ],
            ]);

            // ===== 2️⃣ HITUNG DUE DATE
            $orderDate = Carbon::parse($request->purchase_date);
            $dueDate = match ($request->due_date_option) {
                'today' => $orderDate,
                '1_week' => $orderDate->copy()->addWeek(),
                '1_month' => $orderDate->copy()->addMonth(),
                '3_months' => $orderDate->copy()->addMonths(3),
                'custom' => $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null,
                default => null,
            };

            // ===== 3️⃣ HITUNG NILAI BARU
            $totalProduct = $request->total_amount_product;
            $totalFreight = $request->total_amount_freight;
            $grandTotal = $totalProduct + $totalFreight;

            $paidProduct = $purchase->paid_amount_product ?? 0;
            $paidFreight = $purchase->paid_amount_freight ?? 0;

            $remainingProduct = max(0, $totalProduct - $paidProduct);
            $remainingFreight = max(0, $totalFreight - $paidFreight);
            $remainingAmount = $remainingProduct + $remainingFreight;

            $stockDestination = $request->stock_destination;

            // ===== 4️⃣ UPDATE PURCHASE HEADER
            $purchase->update([
                'purchase_number' => $request->purchase_number,
                'purchase_date' => $request->purchase_date,
                'due_date' => $dueDate,
                'supplier_id' => $request->suppliers,
                'status' => $request->status,
                'sub_total' => $request->sub_total,
                'tax_percent' => $request->tax_percent,
                'tax_amount' => $request->tax_amount,
                'total_amount_product' => $totalProduct,
                'total_amount_freight' => $totalFreight,
                'total_amount' => $grandTotal,
                'remaining_amount_product' => $remainingProduct,
                'remaining_amount_freight' => $remainingFreight,
                'remaining_amount' => $remainingAmount,
                'stock_destination' => $stockDestination,
            ]);

            // ===== 5️⃣ UPDATE ITEMS
            $existingItems = $purchase->purchaseItems->keyBy('product_id');
            $requestKeys = [];

            $inventoryStatus = match ($stockDestination) {
                'warehouse' => 'Stock In',
                'production' => 'Stock In Production',
            };

            $invWarehouseId = $stockDestination === 'warehouse'
                ? ($request->inventory_warehouse_id ?? 1)
                : null;

            $prodWarehouseId = $stockDestination === 'production'
                ? ($request->production_warehouse_id ?? 2)
                : null;

            foreach ($request->input('product', []) as $index => $productId) {
                $qty = $request->qty[$index] ?? 0;

                $unit = UnitConversionService::resolve(
                    (int) $productId,
                    $request->product_unit_id[$index] ?? null,
                    $request->unit_name[$index] ?? 'Pcs'
                );
                $unitConversionId = $unit['id'];
                $unitConversionValue = $unit['factor'];
                $unitName = $unit['unit_name'];

                $qtyBase = $qty * $unitConversionValue;

                $price = $request->price[$index] ?? 0;
                $freight = $request->freight[$index] ?? 0;
                $total = $request->total[$index] ?? 0;

                if (! $productId) {
                    continue;
                }

                $product = Products::findOrFail($productId);
                $requestKeys[] = $productId;

                // ✅ Hitung price_after_tax & final_price per item
                $taxPercent = $request->tax_percent ?? 0;
                $priceAfterTax = $price + ($price * $taxPercent / 100);
                $finalPrice = $priceAfterTax + $freight;

                // 🔎 Cek apakah qty baru < stock_in yang sudah tercatat di inventory_items
                $existingItem = $purchase->purchaseItems->firstWhere('product_id', $productId);

                if ($existingItem) {
                    $invItem = \App\Models\InventoryItem::where('purchase_item_id', $existingItem->id)->first();

                    if ($invItem && $qtyBase < $invItem->stock_in) {
                        DB::rollBack();

                        return back()->with(
                            'error',
                            "Quantity untuk produk {$product->name} (".number_format($qtyBase).') tidak boleh lebih kecil dari jumlah stock in ('.number_format($invItem->stock_in).').'
                        );
                    }
                }

                $oldQty = 0;
                if ($existingItems->has($productId)) {
                    $item = $existingItems[$productId];
                    $oldQty = $item->qty_base ?? ($item->quantity * ($item->unit_conversion_value ?? 1));

                    $item->update([
                        'inventory_warehouse_id' => $stockDestination === 'warehouse' ? ($request->inventory_warehouse_id ?? 1) : null,
                        'production_warehouse_id' => $stockDestination === 'production' ? ($request->production_warehouse_id ?? 2) : null,
                        'quantity' => $qty,
                        'price' => $price,
                        'price_after_tax' => $priceAfterTax,
                        'freight' => $freight,
                        'final_price' => $finalPrice,
                        'subtotal' => $total,
                        'product_unit_conversion_id' => $unitConversionId,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,
                    ]);
                } else {
                    $oldQty = 0;
                    $item = PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $productId,

                        'inventory_warehouse_id' => $stockDestination === 'warehouse'
                            ? ($request->inventory_warehouse_id ?? 1)
                            : null,

                        'production_warehouse_id' => $stockDestination === 'production'
                            ? ($request->production_warehouse_id ?? 2)
                            : null,

                        'status' => 'Purchase Account',
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'price' => $price,
                        'price_after_tax' => $priceAfterTax,
                        'freight' => $freight,
                        'final_price' => $finalPrice,
                        'subtotal' => $total,

                        'product_unit_conversion_id' => $unitConversionId,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,
                    ]);
                }

                // 🧩 Sinkronisasi Inventory
                $inventory = Inventory::firstOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'purchase_number' => $purchase->purchase_number,
                        'supplier_id' => $purchase->supplier_id,
                        'date' => $purchase->purchase_date,
                        'status' => $inventoryStatus,
                        'note' => 'Purchase Account',
                    ]
                );

                if ($inventory->status !== $inventoryStatus) {
                    $inventory->update([
                        'status' => $inventoryStatus,
                    ]);
                }

                $invItem = InventoryItem::firstOrNew([
                    'inventory_id' => $inventory->id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $productId,
                ]);

                if ($invItem->exists) {
                    $invItem->fill([
                        'inventory_warehouse_id' => $invWarehouseId,
                        'production_warehouse_id' => $prodWarehouseId,
                        'quantity' => $qty,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,
                        'price' => $price,
                        'remaining_stock_in' => $qtyBase,
                    ]);
                } else {
                    $invItem->fill([
                        'inventory_warehouse_id' => $invWarehouseId,
                        'production_warehouse_id' => $prodWarehouseId,
                        'quantity' => $qty,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,
                        'price' => $finalPrice,
                        'remaining_stock_in' => $qtyBase,
                        'stock_in' => 0,
                        'stock_out' => 0,
                    ]);
                }

                $invItem->save();

                // 🧩 Update InventoryStock
                if ($stockDestination === 'warehouse') {
                    $invStock = InventoryStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'inventory_warehouse_id' => $invWarehouseId,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $difference = $qtyBase - $oldQty;
                    $invStock->increment('incoming_stock', $difference);
                }

                if ($stockDestination === 'production') {
                    $prodStock = ProductionStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'production_warehouse_id' => $prodWarehouseId,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $difference = $qtyBase - $oldQty;
                    $prodStock->increment('incoming_stock', $difference);
                }

                // $difference = $qtyBase - $oldQty;
                // $invStock->increment('incoming_stock', $difference);
            }

            foreach ($existingItems as $pid => $item) {
                if (! in_array($pid, $requestKeys)) {
                    $item->forceDelete();
                    InventoryItem::where('purchase_item_id', $item->id)->delete();
                    $invStock = InventoryStock::where('product_id', $pid)->first();
                    if ($invStock) {
                        $totalPurchasedQty = PurchaseItem::where('product_id', $pid)->sum('qty_base');
                        $invStock->update(['incoming_stock' => $totalPurchasedQty]);
                    }

                    if ($stockDestination === 'production') {
                        $prodStock = ProductionStock::where('product_id', $pid)
                            ->where('production_warehouse_id', $prodWarehouseId)
                            ->first();

                        if ($prodStock) {
                            $totalPurchasedQty = PurchaseItem::where('product_id', $pid)
                                ->whereHas(
                                    'purchase',
                                    fn ($q) => $q->where('stock_destination', 'production')
                                )
                                ->sum('qty_base');

                            $prodStock->update([
                                'incoming_stock' => $totalPurchasedQty,
                            ]);
                        }
                    }
                }
            }

            // ===== 6️⃣ SNAPSHOT BARU
            $purchase->refresh()->load('purchaseItems');
            $newPurchase = $purchase->only([
                'purchase_number',
                'due_date',
                'purchase_date',
                'supplier_id',
                'status',
                'total_amount_product',
                'total_amount_freight',
                'remaining_amount_product',
                'remaining_amount_freight',
            ]);
            $newItems = $purchase->purchaseItems->mapWithKeys(fn ($i) => [
                $i->product_id => [
                    'product' => $i->purchaseProduct->name ?? 'Unknown',
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                    'freight' => $i->freight,
                    'subtotal' => $i->subtotal,
                ],
            ]);

            // ===== 7️⃣ DIFF
            $purchaseDiff = ['old' => [], 'new' => []];
            foreach ($newPurchase as $field => $newVal) {
                $oldVal = $oldPurchase[$field] ?? null;
                if (in_array($field, ['purchase_date', 'due_date'])) {
                    $oldVal = $oldVal ? Carbon::parse($oldVal)->format('Y-m-d') : null;
                    $newVal = $newVal ? Carbon::parse($newVal)->format('Y-m-d') : null;
                }
                if ($oldVal != $newVal) {
                    $purchaseDiff['old'][$field] = $oldVal;
                    $purchaseDiff['new'][$field] = $newVal;
                }
            }

            $itemsDiff = [];
            $allKeys = array_unique(array_merge(array_keys($oldItems->toArray()), array_keys($newItems->toArray())));
            foreach ($allKeys as $pid) {
                $old = $oldItems[$pid] ?? null;
                $new = $newItems[$pid] ?? null;

                if ($old && ! $new) {
                    $itemsDiff[] = ['product' => $old['product'], 'action' => 'removed', 'old' => $old, 'new' => null];
                } elseif (! $old && $new) {
                    $itemsDiff[] = ['product' => $new['product'], 'action' => 'added', 'old' => null, 'new' => $new];
                } elseif ($old && $new && $old != $new) {
                    $itemsDiff[] = ['product' => $new['product'], 'action' => 'updated', 'old' => $old, 'new' => $new];
                }
            }

            // ===== 8️⃣ SIMPAN HISTORY
            PurchaseEditHistory::create([
                'purchase_id' => $purchase->id,
                'edited_by' => Auth::id(),
                'changes' => ['purchase' => $purchaseDiff, 'items' => $itemsDiff],
                'text' => $request->edit_note,
                'edited_at' => now(),
            ]);

            $purchase->update(['status_edited' => true]);

            // ===== 9️⃣ UPDATE ACCOUNT TRANSACTION
            try {
                $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

                $accountTransaction = AccountTransaction::where('purchase_id', $purchase->id)
                    ->where('account_id', $purchaseAccount->id)
                    ->first();

                $groupId = $accountTransaction->transaction_group_id ?? Str::uuid();

                if ($accountTransaction) {
                    // 💰 Kurangi dulu balance lama, lalu tambahkan balance baru
                    $purchaseAccount->decrement('closing_balance', $accountTransaction->debit);

                    $accountTransaction->update([
                        'transaction_date' => $purchase->purchase_date,
                        'debit' => $grandTotal,
                        'credit' => 0,
                        'note' => 'Purchase Account Transaction (Edited)',
                        'particular' => 'Purchase Invoice Updated',
                    ]);
                } else {
                    AccountTransaction::create([
                        'purchase_id' => $purchase->id,
                        'purchase_number' => $purchase->purchase_number,
                        'transaction_date' => $purchase->purchase_date,
                        'account_id' => $purchaseAccount->id,
                        'debit' => $grandTotal,
                        'credit' => 0,
                        'note' => 'Purchase Account Transaction (Created via Edit)',
                        'particular' => 'Purchase Invoice Updated',
                        'transaction_group_id' => $groupId,
                    ]);
                }

                $purchaseAccount->increment('closing_balance', $grandTotal);
            } catch (\Exception $e) {
                Log::warning("Gagal update account transaction untuk purchase {$purchase->id}: ".$e->getMessage());
            }

            if ($purchase->parent_purchase_id) {
                $this->refreshParentPurchaseProgress($purchase->parent_purchase_id);
            }

            DB::commit();

            return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Update Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    private function deleteResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $message,
            ], $success ? 200 : 400);
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', $message);
    }

    public function delete($id, Request $request)
    {
        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);
            $parentPurchaseId = $purchase->parent_purchase_id;

            // 🚫 Cek dulu apakah ada Purchase Return
            if ($purchase->purchaseReturn()->exists()) {
                DB::rollBack();
                $msg = 'Tidak dapat menghapus order ini karena sudah memiliki Purchase Return.';

                return $this->deleteResponse($request, false, $msg);
            }

            // 🚫 Cek apakah sudah ada stock in
            if ($purchase->hasStockIn()) {
                DB::rollBack();
                $msg = 'Tidak dapat menghapus order ini karena sudah memiliki Stock In.';

                return $this->deleteResponse($request, false, $msg);
            }

            // 🔁 Rollback stok incoming & stock-in
            foreach ($purchase->purchaseItems as $item) {
                $stockInQty = InventoryItem::where('purchase_item_id', $item->id)
                    ->where('stock_in', '>', 0)
                    ->sum('stock_in');
                $quantityBase = $item->qty_base ?? ($item->quantity * ($item->unit_conversion_value ?? 1));
                $incomingLeft = max(0, $quantityBase - $stockInQty);

                $stock = $purchase->stock_destination === 'production'
                    ? ProductionStock::where('product_id', $item->product_id)
                        ->where('production_warehouse_id', $item->production_warehouse_id ?? 2)
                        ->first()
                    : InventoryStock::where('product_id', $item->product_id)
                        ->where('inventory_warehouse_id', $item->inventory_warehouse_id ?? 1)
                        ->first();

                if ($stock && $incomingLeft > 0) {
                    $stock->decrement('incoming_stock', $incomingLeft);
                }
            }

            $productIds = $purchase->purchaseItems->pluck('product_id')->filter()->unique()->toArray();

            // 🔁 Handle account transactions (persis kayak Sale)
            $transactions = AccountTransaction::where('purchase_id', $purchase->id)->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (! $account) {
                    continue;
                }

                if ($account->type === 'Purchase Account') {
                    // Hapus transaksi Purchase Account
                    $account->closing_balance -= $trx->debit;
                    $account->closing_balance += $trx->credit;
                    $trx->delete();
                } else {
                    // Cash / Bank → jangan dihapus, hanya unlink
                    $trx->purchase_id = null;
                    $trx->note = trim(($trx->note ?? '').' [Purchase deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // 🔁 Hapus items
            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            // 🔁 Hapus file image kalau ada
            if ($purchase->image && file_exists(public_path('storage/'.$purchase->image))) {
                unlink(public_path('storage/'.$purchase->image));
            }

            // 🔁 Hapus inventory kalau status Purchase List
            if ($purchase->status === 'Purchase List') {
                $warehouse = Inventory::where('purchase_id', $purchase->id)->first();
                if ($warehouse) {
                    InventoryItem::where('inventory_id', $warehouse->id)->delete();
                    $warehouse->delete();
                }
            }

            // Simpan delete_notes & deleted_by
            $purchase->delete_notes = $request->input('delete_notes');
            $purchase->deleted_by = Auth::id();
            $purchase->save();

            // Soft delete purchase
            $purchase->delete();

            if ($parentPurchaseId) {
                $this->refreshParentPurchaseProgress($parentPurchaseId);
            }

            DB::commit();

            return back()->with('success', 'Purchase berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: '.$e->getMessage());

            return back()->with('error', 'Gagal menghapus purchase: '.$e->getMessage());
        }
    }

    public function forceDeleteOwner($id, Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'Owner') {
            abort(403, 'Only Owner can force delete.');
        }

        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with(['purchaseItems'])->findOrFail($id);
            $parentPurchaseId = $purchase->parent_purchase_id;

            // $hasStockIn = InventoryItem::whereIn('purchase_item_id', $purchase->purchaseItems->pluck('id'))
            //     ->where('stock_in', '>', 0)
            //     ->exists();

            // if ($hasStockIn) {
            //     return back()->with('error', 'Purchase tidak bisa dihapus total karena sudah pernah stock-in. Gunakan Purchase Return atau Stock Adjustment.');
            // }

            $productIds = $purchase->purchaseItems->pluck('product_id')->filter()->unique()->toArray();

            // 1️⃣ ROLLBACK STOK (Termasuk Stock In)
            foreach ($purchase->purchaseItems as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $inventoryStock = InventoryStock::firstOrCreate(
                    ['product_id' => $item->product_id],
                    [
                        'incoming_stock' => 0,
                        'stock_after_sales' => 0,
                        'inventory_stock' => 0, // stok utama
                    ]
                );

                // Ambil total stock_in dari InventoryItem yang terkait purchase ini
                $stockInQty = InventoryItem::where('purchase_item_id', $item->id)
                    ->where('stock_in', '>', 0)
                    ->sum('stock_in');

                // 🔹 Jika ada Stock In → rollback stok utama & after sales
                if ($stockInQty > 0) {
                    $inventoryStock->decrement('stock_after_sales', $stockInQty);
                    $inventoryStock->decrement('inventory_stock', $stockInQty);
                }
                // 🔹 Jika belum ada Stock In → rollback incoming stock (barang belum diterima)
                else {
                    if ($item->quantity > 0) {
                        $inventoryStock->decrement('incoming_stock', $item->quantity);
                    }
                }

                // 🔹 Hapus semua inventory_item yang terkait purchase ini
                InventoryItem::where('purchase_item_id', $item->id)->forceDelete();

                $inventoryStock->save();
            }

            // 2️⃣ HAPUS SEMUA TRANSAKSI KEUANGAN
            $transactions = AccountTransaction::where('purchase_id', $purchase->id)->get();
            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (! $account) {
                    continue;
                }

                if ($account->type === 'Purchase Account') {
                    $account->closing_balance -= $trx->debit;
                    $account->closing_balance += $trx->credit;
                    $trx->forceDelete();
                } else {
                    $trx->purchase_id = null;
                    $trx->note = trim(($trx->note ?? '').' [Purchase deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // 3️⃣ HAPUS INVENTORY YANG TERTAUT KE PURCHASE
            $inventory = Inventory::where('purchase_id', $purchase->id)->first();
            if ($inventory) {
                InventoryItem::where('inventory_id', $inventory->id)->forceDelete();
                $inventory->forceDelete();
            }

            // 4️⃣ HAPUS PURCHASE ITEM & RELASI LAIN
            PurchaseItem::where('purchase_id', $purchase->id)->forceDelete();
            FinancialReport::where('reference_table', 'purchases')
                ->where('reference_id', $purchase->id)
                ->forceDelete();

            // 5️⃣ HAPUS FILE IMAGE
            if ($purchase->image && file_exists(public_path('storage/'.$purchase->image))) {
                unlink(public_path('storage/'.$purchase->image));
            }

            // 6️⃣ FORCE DELETE PURCHASE
            $purchase->delete_notes = $request->input('delete_notes');
            $purchase->deleted_by = Auth::id();
            $purchase->saveQuietly();
            $purchase->forceDelete();

            if ($parentPurchaseId) {
                $this->refreshParentPurchaseProgress($parentPurchaseId);
            }

            // // 7️⃣ HITUNG ULANG AVG COST & STOCK PRODUK YANG TERDAMPAK
            // foreach ($productIds as $productId) {

            //     $product = Products::find($productId);
            //     if (!$product) continue;

            //     // MULAI DARI STOK REAL SETELAH ROLLBACK
            //     $inventory = InventoryStock::where('product_id', $productId)->first();

            //     $currentQty   = $inventory->inventory_stock ?? 0;   // stok setelah rollback
            //     $currentAvg   = $product->avg_cost ?? 0;            // avg cost sebelum purchase ini dihapus

            //     $totalQty     = $currentQty;
            //     $totalValue   = $currentQty * $currentAvg;

            //     // Ambil semua purchase item lain yg sudah stock in
            //     $otherPurchases = PurchaseItem::where('product_id', $productId)
            //         ->where('purchase_id', '!=', $purchase->id)
            //         ->get();

            //     foreach ($otherPurchases as $item) {
            //         $stockInQty = InventoryItem::where('purchase_item_id', $item->id)
            //             ->sum('stock_in');

            //         if ($stockInQty > 0) {
            //             $totalQty   += $stockInQty;
            //             $totalValue += ($stockInQty * $item->price);
            //         }
            //     }

            //     // Hitung avg cost baru
            //     $newAvg = $totalQty > 0 ? ($totalValue / $totalQty) : $currentAvg;

            //     // Update product
            //     $product->avg_cost = $newAvg;
            //     $product->inventory_stock = $totalQty;
            //     $product->stock_after_sales = $totalQty;
            //     $product->save();
            // }

            DB::commit();

            return back()->with('success', 'Purchase berhasil dihapus total (force delete oleh Owner). Semua efek stok & transaksi telah direset.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Force delete purchase failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal force delete: '.$e->getMessage());
        }
    }

    public function markAsPaidProduct($id, Request $request)
    {
        // $request->merge([
        //     'paid_amount' => str_replace(',', '.', str_replace('.', '', $request->paid_amount)),
        // ]);

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
            'payment_proof' => 'nullable|array',
            'payment_proof.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($request->purchase_id);
            $groupId = Str::uuid();

            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            if ($request->hasFile('payment_proof')) {
                $uploadPath = base_path('uploads/payment_proofs');
                if (! file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);

                    $uploadedProofs[] = [
                        'file' => 'uploads/payment_proofs/'.$fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            $proofJson = ! empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // =========================
            // 1️⃣ Kas / Bank - CREDIT
            // =========================
            AccountTransaction::create([
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'transaction_date' => $request->transaction_date,
                'account_id' => $cashBankAccount->id,
                'debit' => 0,
                'credit' => $request->paid_amount,
                'note' => 'Product Payment',
                'particular' => 'Product Payment - '.$purchaseAccount->name,
                'transaction_group_id' => $groupId,
                'proof' => $proofJson,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // =========================
            // 2️⃣ Purchase Account - DEBIT
            // =========================
            // AccountTransaction::create([
            //     'purchase_id'          => $purchase->id,
            //     'purchase_number'      => $purchase->purchase_number,
            //     'transaction_date'     => $request->transaction_date,
            //     'account_id'           => $purchaseAccount->id,
            //     'debit'                => $request->paid_amount,
            //     'credit'               => 0,
            //     'note'                 => $request->note ?? '',
            //     'particular'           => 'Purchase Product Payment - ' . $cashBankAccount->name,
            //     'transaction_group_id' => $groupId,
            //     'proof'         => $proofJson
            // ]);

            // $purchaseAccount->increment('closing_balance', $request->paid_amount);

            // =========================
            // 3️⃣ Update Purchase Fields
            // =========================
            $purchase->paid_amount_product += $request->paid_amount;
            $purchase->remaining_amount_product = $purchase->total_amount_product - $purchase->paid_amount_product;

            // 🔹 Status final: gabungkan hasil pembayaran produk + freight
            $totalPaid = $purchase->paid_amount_product + $purchase->paid_amount_freight;
            $totalAll = $purchase->total_amount_product + $purchase->total_amount_freight;

            if ($totalPaid >= $totalAll) {
                $purchase->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $purchase->payment_status = 'Partially Paid';
            } else {
                $purchase->payment_status = 'Unpaid';
            }

            $purchase->transaction_group_id = $groupId;
            $purchase->verified = false;
            $purchase->save();

            DB::commit();

            $paymentBadge = match (strtolower($purchase->payment_status)) {
                'paid' => '<div class="badge bg-soft-success text-success">Paid</div>',
                'partially paid' => '<div class="badge bg-soft-warning text-warning">Partially Paid</div>',
                'unpaid' => '<div class="badge bg-soft-dark text-dark">Unpaid</div>',
                default => '<div class="badge bg-secondary text-white">'.e($purchase->payment_status).'</div>',
            };

            // 💰 Hitung ulang total gabungan
            $paidTotal = ($purchase->paid_amount_product ?? 0) + ($purchase->paid_amount_freight ?? 0);
            $remainingTotal = ($purchase->remaining_amount_product ?? 0) + ($purchase->remaining_amount_freight ?? 0);

            $paidProductColumn = '
                <div class="text-success">Rp '.number_format($purchase->paid_amount_product, 0, ',', '.').'</div>
                <small class="text-danger">Remaining: Rp '.number_format($purchase->remaining_amount_product, 0, ',', '.').'</small>
            ';

            $paidFreightColumn = '
                <div class="text-success">Rp '.number_format($purchase->paid_amount_freight, 0, ',', '.').'</div>
                <small class="text-danger">Remaining: Rp '.number_format($purchase->remaining_amount_freight, 0, ',', '.').'</small>
            ';

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran produk berhasil disimpan!',
                'purchase' => [
                    'id' => $purchase->id,
                    'paid_amount_product_html' => $paidProductColumn,
                    'paid_amount_freight_html' => $paidFreightColumn,
                    'remaining_amount_html' => '<span class="text-danger">Rp '.number_format($remainingTotal, 0, ',', '.').'</span>',
                    'payment_status_html' => $paymentBadge,
                    'action_html' => view('erp.pages.purchases.purchase-list.partials.action-button', [
                        'purchase' => $purchase,
                    ])->render(),
                ],
            ]);

            return back()->with('success', 'Pembayaran produk berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }

            return back()->with('error', 'Gagal menyimpan pembayaran produk: '.$e->getMessage());
        }
    }

    public function markAsPaidFreight($id, Request $request)
    {
        // $request->merge([
        //     'paid_amount' => str_replace(',', '.', str_replace('.', '', $request->paid_amount)),
        // ]);

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
            'payment_proof' => 'nullable|array',
            'payment_proof.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($request->purchase_id);
            $groupId = Str::uuid();

            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

            if ($request->hasFile('payment_proof')) {
                $uploadPath = base_path('uploads/payment_proofs');
                if (! file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);

                    $uploadedProofs[] = [
                        'file' => 'uploads/payment_proofs/'.$fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            $proofJson = ! empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // =========================
            // 1️⃣ Kas / Bank - CREDIT
            // =========================
            AccountTransaction::create([
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'transaction_date' => $request->transaction_date,
                'account_id' => $cashBankAccount->id,
                'debit' => 0,
                'credit' => $request->paid_amount,
                'note' => 'Freight Payment',
                'particular' => 'Freight Payment - '.$purchaseAccount->name,
                'transaction_group_id' => $groupId,
                'proof' => $proofJson,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // =========================
            // 2️⃣ Purchase Account - DEBIT
            // =========================
            // AccountTransaction::create([
            //     'purchase_id'          => $purchase->id,
            //     'purchase_number'      => $purchase->purchase_number,
            //     'transaction_date'     => $request->transaction_date,
            //     'account_id'           => $purchaseAccount->id,
            //     'debit'                => $request->paid_amount,
            //     'credit'               => 0,
            //     'note'                 => $request->note ?? '',
            //     'particular'           => 'Freight Payment - ' . $cashBankAccount->name,
            //     'transaction_group_id' => $groupId,
            //     'proof'                => $proofJson
            // ]);

            // $purchaseAccount->increment('closing_balance', $request->paid_amount);

            // =========================
            // 3️⃣ Update Purchase Fields (Freight)
            // =========================
            $purchase->paid_amount_freight += $request->paid_amount;
            $purchase->remaining_amount_freight = $purchase->total_amount_freight - $purchase->paid_amount_freight;

            // 🔹 Status akhir (gabungan produk + freight)
            $totalPaid = $purchase->paid_amount_product + $purchase->paid_amount_freight;
            $totalAll = $purchase->total_amount_product + $purchase->total_amount_freight;

            if ($totalPaid >= $totalAll) {
                $purchase->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $purchase->payment_status = 'Partially Paid';
            } else {
                $purchase->payment_status = 'Unpaid';
            }

            $purchase->transaction_group_id = $groupId;
            $purchase->verified = false;
            $purchase->save();

            DB::commit();
            $paymentBadge = match (strtolower($purchase->payment_status)) {
                'paid' => '<div class="badge bg-soft-success text-success">Paid</div>',
                'partially paid' => '<div class="badge bg-soft-warning text-warning">Partially Paid</div>',
                'unpaid' => '<div class="badge bg-soft-dark text-dark">Unpaid</div>',
                default => '<div class="badge bg-secondary text-white">'.e($purchase->payment_status).'</div>',
            };

            // 💰 Hitung ulang total gabungan
            $paidTotal = ($purchase->paid_amount_product ?? 0) + ($purchase->paid_amount_freight ?? 0);
            $remainingTotal = ($purchase->remaining_amount_product ?? 0) + ($purchase->remaining_amount_freight ?? 0);

            $paidProductColumn = '
                <div class="text-success">Rp '.number_format($purchase->paid_amount_product, 0, ',', '.').'</div>
                <small class="text-danger">Remaining: Rp '.number_format($purchase->remaining_amount_product, 0, ',', '.').'</small>
            ';

            $paidFreightColumn = '
                <div class="text-success">Rp '.number_format($purchase->paid_amount_freight, 0, ',', '.').'</div>
                <small class="text-danger">Remaining: Rp '.number_format($purchase->remaining_amount_freight, 0, ',', '.').'</small>
            ';

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran freight berhasil disimpan!',
                'purchase' => [
                    'id' => $purchase->id,
                    'paid_amount_product_html' => $paidProductColumn,
                    'paid_amount_freight_html' => $paidFreightColumn,
                    'remaining_amount_html' => '<span class="text-danger">Rp '.number_format($remainingTotal, 0, ',', '.').'</span>',
                    'payment_status_html' => $paymentBadge,
                    'action_html' => view('erp.pages.purchases.purchase-list.partials.action-button', [
                        'purchase' => $purchase,
                    ])->render(),
                ],
            ]);

            return back()->with('success', 'Pembayaran freight berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }

            return back()->with('error', 'Gagal menyimpan pembayaran freight: '.$e->getMessage());
        }
    }

    public function getPaymentHistory($id)
    {
        $purchase = Purchase::with('supplier')->findOrFail($id);

        $transactions = AccountTransaction::with('account')
            ->where('purchase_id', $purchase->id)
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy('transaction_group_id');

        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-list.payment-history', [
            'purchase' => $purchase,
            'transactions' => $transactions,
            'cashAccounts' => $cashAccounts,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function updatePayment(Request $request, $groupId)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'transaction_date' => 'required|date',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'payment_proof' => 'nullable|array',
            'payment_proof.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception('Payment not found');
            }

            $purchaseId = $transactions->first()->purchase_id;
            $purchase = Purchase::findOrFail($purchaseId);

            foreach ($transactions as $trx) {
                $trx->update(['verified' => false]);
            }
            $purchase->update(['verified' => false]);

            // =====================================================
            // 🔹 Handle Multiple Uploads (bukti + note)
            // =====================================================
            $uploadedProofs = [];
            $notes = $request->note_per_image ?? [];

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
                if (! file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);
                    $uploadedProofs[] = [
                        'file' => 'uploads/payment_proofs/'.$fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            if (empty($uploadedProofs)) {
                foreach ($oldProofs as $index => &$proof) {
                    $proof['note'] = $notes[$index] ?? ($proof['note'] ?? '');
                }
                $uploadedProofs = $oldProofs;
            }

            $proofJson = ! empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // =====================================================
            // 🔥 Jika paid_amount = 0 → hapus semua transaksi dalam group
            // =====================================================
            if ($request->paid_amount == 0) {

                // rollback saldo account utk semua trx
                foreach ($transactions as $trx) {
                    $account = $trx->account;

                    if ($trx->credit > 0) {
                        // refund uang masuk sebelumnya
                        $account->increment('closing_balance', $trx->credit);
                    } elseif ($trx->debit > 0) {
                        // rollback debit pembelian (harus kurangi balance)
                        $account->decrement('closing_balance', $trx->debit);
                    }

                    $trx->delete();
                }

                // =====================================================
                // 🔹 Hitung ulang paid_amount & remaining_amount Purchase
                // =====================================================
                $totalProductPaid = AccountTransaction::where('purchase_id', $purchase->id)
                    ->where('credit', '>', 0)
                    ->where('particular', 'like', '%product%')
                    ->sum('credit');

                $totalFreightPaid = AccountTransaction::where('purchase_id', $purchase->id)
                    ->where('credit', '>', 0)
                    ->where('particular', 'like', '%freight%')
                    ->sum('credit');

                $purchase->paid_amount_product = $totalProductPaid;
                $purchase->remaining_amount_product = max(0, $purchase->total_amount_product - $totalProductPaid);

                $purchase->paid_amount_freight = $totalFreightPaid;
                $purchase->remaining_amount_freight = max(0, $purchase->total_amount_freight - $totalFreightPaid);

                $totalPaid = $totalProductPaid + $totalFreightPaid;
                $totalAll = ($purchase->total_amount_product ?? 0) + ($purchase->total_amount_freight ?? 0);

                if ($totalPaid == 0) {
                    $purchase->payment_status = 'Unpaid';
                } elseif ($totalPaid < $totalAll) {
                    $purchase->payment_status = 'Partially Paid';
                } else {
                    $purchase->payment_status = 'Paid';
                }

                $purchase->verified = false;
                $purchase->save();

                DB::commit();

                // =====================================================
                // 🔹 AJAX Return → Frontend hapus card payment
                // =====================================================
                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'deleted',
                        'message' => 'Payment berhasil dihapus.',
                        'group_id' => $groupId,
                    ]);
                }

                return back()->with('success', 'Payment berhasil dihapus.');
            }

            // =====================================================
            // 🔹 Identify Payment Type: Product vs Freight
            // =====================================================
            $isFreight = false;
            $isProduct = false;

            $firstTrx = $transactions->first();
            if ($firstTrx && str_contains(strtolower($firstTrx->particular), 'freight')) {
                $isFreight = true;
            } elseif ($firstTrx && str_contains(strtolower($firstTrx->particular), 'product')) {
                $isProduct = true;
            }

            // =====================================================
            // 🔹 Payment Process
            // =====================================================
            $oldCredit = $transactions->firstWhere('credit', '>', 0);
            if (! $oldCredit) {
                throw new \Exception('Credit transaction not found in this group');
            }

            $oldAccount = $oldCredit->account;
            $oldAmount = $oldCredit->credit;

            // rollback saldo lama
            $oldAccount->increment('closing_balance', $oldAmount);

            // update transaksi credit
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldCredit->update([
                'transaction_date' => $request->transaction_date,
                'account_id' => $cashBankAccount->id,
                'credit' => $request->paid_amount,
                'note' => $request->note ?? '',
                'proof' => $proofJson,
            ]);

            // kurangi saldo akun baru
            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // update juga baris debit (biar tanggal + note sama)
            $purchaseTrx = $transactions->firstWhere('debit', '>', 0);
            if ($purchaseTrx) {
                $purchaseTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note' => $request->note ?? '',
                ]);
            }

            // =====================================================
            // 🔹 Recalculate purchase paid amount
            // =====================================================
            // Total kredit untuk produk
            $totalProductPaid = AccountTransaction::where('purchase_id', $purchase->id)
                ->where('credit', '>', 0)
                ->where('particular', 'like', '%product%')
                ->sum('credit');

            // Total kredit untuk freight
            $totalFreightPaid = AccountTransaction::where('purchase_id', $purchase->id)
                ->where('credit', '>', 0)
                ->where('particular', 'like', '%freight%')
                ->sum('credit');

            // update field spesifik
            $purchase->paid_amount_product = $totalProductPaid;
            $purchase->remaining_amount_product = max(0, $purchase->total_amount_product - $totalProductPaid);

            $purchase->paid_amount_freight = $totalFreightPaid;
            $purchase->remaining_amount_freight = max(0, $purchase->total_amount_freight - $totalFreightPaid);

            // gabungan
            $totalPaid = $totalProductPaid + $totalFreightPaid;
            $totalAll = ($purchase->total_amount_product ?? 0) + ($purchase->total_amount_freight ?? 0);

            if ($totalPaid == 0) {
                $purchase->payment_status = 'Unpaid';
            } elseif ($totalPaid < $totalAll) {
                $purchase->payment_status = 'Partially Paid';
            } elseif ($totalPaid >= $totalAll) {
                $purchase->payment_status = 'Paid';
            }

            $purchase->save();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment berhasil diperbarui.',
                    'data' => [
                        'transaction_group_id' => $groupId,
                        'transaction_date' => \Carbon\Carbon::parse($request->transaction_date)->format('d-m-Y'),
                        'paid_amount' => number_format($request->paid_amount, 0, ',', '.'),
                        'account_id' => $cashBankAccount->id,
                        'account_name' => $cashBankAccount->name,
                        'account_type' => $cashBankAccount->type,
                        'note' => $request->note ?? '',
                        'proofs' => $uploadedProofs,
                        'verified' => false,
                    ],
                ]);
            }

            return back()->with('success', 'Payment berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal update payment: '.$e->getMessage());
        }
    }

    public function verifyPayment($groupId)
    {
        try {
            // 🔍 Ambil semua transaksi dalam group ini
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();

            if ($transactions->isEmpty()) {
                return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
            }

            // ✅ Update semua transaksi di group ini jadi verified
            foreach ($transactions as $trx) {
                $trx->update(['verified' => true]);
            }

            // ✅ Ambil purchase_id dari transaksi (pastikan gak null)
            $purchaseId = $transactions->firstWhere('purchase_id', '!=', null)?->purchase_id;

            if ($purchaseId) {
                // 🔍 Ambil semua transaksi dengan purchase_id yang sama
                $purchaseTransactions = AccountTransaction::where('purchase_id', $purchaseId)->get();

                // 🔎 Hitung berapa yang verified
                $verifiedCount = $purchaseTransactions->where('verified', true)->count();
                $totalCount = $purchaseTransactions->count();

                // ✅ Kalau semua transaksi verified → update purchase
                if ($totalCount > 0 && $verifiedCount === $totalCount) {
                    \App\Models\Purchase::where('id', $purchaseId)->update(['verified' => true]);
                } else {
                    // ❌ Kalau masih ada yang belum verified, pastikan purchase tetap false
                    \App\Models\Purchase::where('id', $purchaseId)->update(['verified' => false]);
                }
            }

            return response()->json([
                'message' => 'Payment berhasil diverifikasi.',
                'group_id' => $groupId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal verifikasi payment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getEditHistory($id)
    {
        $purchase = Purchase::findOrFail($id);

        $histories = PurchaseEditHistory::with('user')
            ->where('purchase_id', $id)
            ->orderBy('edited_at', 'desc')
            ->get();

        // dd($histories->toArray());

        return view('erp.pages.purchases.purchase-list.edit-purchase-histories', compact('purchase', 'histories'));
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::onlyTrashed()->findOrFail($id);
            $purchase->forceDelete();
            DB::commit();

            return redirect()->back()->with('success', 'Purchase berhasil dihapus permanen!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete purchase gagal', ['purchase_id' => $id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Gagal menghapus permanen purchase!');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::onlyTrashed()
                ->with(['purchaseItems' => fn ($q) => $q->withTrashed()])
                ->findOrFail($id);

            // ✅ Restore purchase
            $purchase->restore();

            // ✅ Restore purchase items
            $purchase->purchaseItems()->withTrashed()->restore();

            if ($purchase->parent_purchase_id) {
                $this->refreshParentPurchaseProgress($purchase->parent_purchase_id);
            }

            $inventories = \App\Models\Inventory::with(['items' => fn ($q) => $q->withTrashed()])
                ->withTrashed()
                ->where('purchase_id', $purchase->id)
                ->get();

            foreach ($inventories as $inventory) {
                if ($inventory->trashed()) {
                    $inventory->restore();
                }

                foreach ($inventory->items as $invItem) {
                    if ($invItem->trashed()) {
                        $invItem->restore();
                    }
                }
            }

            // Fallback jaga-jaga
            \App\Models\InventoryItem::withTrashed()
                ->whereIn('inventory_id', $inventories->pluck('id'))
                ->restore();

            // ✅ Pastikan stok incoming dikembalikan
            foreach ($purchase->purchaseItems as $item) {
                $warehouseId = $item->inventory_warehouse_id ?? 1;

                $inventoryStock = InventoryStock::firstOrCreate(
                    ['product_id' => $item->product_id, 'inventory_warehouse_id' => $warehouseId],
                    ['incoming_stock' => 0]
                );

                $inventoryStock->increment('incoming_stock', $item->quantity);
            }

            // ✅ Restore transaksi akun
            $transactions = AccountTransaction::withTrashed()
                ->where(function ($q) use ($purchase) {
                    $q->where('purchase_id', $purchase->id)
                        ->orWhere('note', 'like', '%[Purchase deleted]%');
                })
                ->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (! $account) {
                    continue;
                }

                if ($account->type === 'Purchase Account') {
                    if ($trx->trashed()) {
                        $trx->restore();
                    }

                    if ($trx->debit > 0) {
                        $account->closing_balance += $trx->debit;
                    }
                    if ($trx->credit > 0) {
                        $account->closing_balance -= $trx->credit;
                    }
                } else {
                    $trx->purchase_id = $purchase->id;
                    $trx->note = str_replace('[Purchase deleted]', '', $trx->note ?? '');
                    $trx->save();
                }

                $account->save();
            }

            // ✅ Update ulang avg cost produk
            $productIds = $purchase->purchaseItems->pluck('product_id')->filter()->unique()->toArray();
            foreach ($productIds as $productId) {
                $product = Products::find($productId);
                if ($product) {
                    ProductCostService::updateCostAndStock($product);
                    $product->stock_after_sales = $product->inventory_stock;
                    $product->save();
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Purchase dan Inventory terkait berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore purchase gagal', [
                'purchase_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal mengembalikan purchase! '.$e->getMessage());
        }
    }

    private function refreshParentPurchaseProgress(int $parentPurchaseId): void
    {
        $parent = Purchase::with('purchaseItems')->find($parentPurchaseId);
        if (! $parent) {
            return;
        }

        $ordered = (float) $parent->purchaseItems->sum('quantity');
        $allocated = (float) PurchaseItem::whereIn(
            'source_purchase_item_id',
            $parent->purchaseItems->pluck('id')
        )->sum('quantity');

        $parent->update([
            'approval_status' => $allocated <= 0
                ? 'Approved'
                : ($allocated >= $ordered ? 'Completed' : 'Partial'),
        ]);
    }
}
