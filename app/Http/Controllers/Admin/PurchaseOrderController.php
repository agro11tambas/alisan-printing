<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PurchaseOrderExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\PurchaseNumberService;
use App\Services\UnitConversionService;
use App\Support\ExportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function getPurchaseOrders()
    {
        $purchase_number = Purchase::first();
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        // Tahun untuk dropdown periode di modal export.
        $exportYears = ExportPeriod::yearOptions(
            Purchase::where('status', 'Purchase Orders')->min('purchase_date')
        );

        return view('erp.pages.purchases.purchase-orders.purchase-orders', compact('purchase_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'exportYears'));
    }

    /**
     * Filter tanggal dan pencarian keyword.
     * Dipakai bersama oleh listing dan export Excel supaya hasilnya identik.
     */
    private function applyPurchaseOrderFilters($purchases, Request $request)
    {
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
                default:
                    // all time -> no filter
                    break;
            }
        }

        // ✅ Filter pencarian
        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'supplier') {
                $purchases->whereHas('supplier', function ($query) use ($request) {
                    $query->where('name', 'like', $request->search_keyword.'%');
                });
            } else {
                $purchases->where('purchase_number', 'like', $request->search_keyword.'%');
            }
        }

        return $purchases;
    }

    public function exportExcel(Request $request)
    {
        $period = ExportPeriod::fromRequest($request);

        if ($period === null && ExportPeriod::isRequested($request)) {
            return back()->with('error', 'Periode export tidak valid. Silakan pilih ulang periodenya.');
        }

        $purchases = Purchase::query()
            ->where('status', 'Purchase Orders')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id');

        if ($period !== null) {
            // Periode dari modal menggantikan filter tanggal halaman. Filter
            // pencarian tetap ikut.
            $request->merge(['filter' => null]);
            $period->applyTo($purchases, 'purchase_date');
        }

        $this->applyPurchaseOrderFilters($purchases, $request);

        $filename = 'purchase-order-'.($period?->filenameSuffix() ?? Carbon::now()->format('Ymd-His')).'.xlsx';

        return (new PurchaseOrderExport($purchases))->download($filename);
    }

    public function dataPurchaseOrders(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchases = Purchase::with([
            'supplier',
            'purchaseAccount',
            'user',
            'purchaseItems.purchaseProduct',
            'purchaseItems.purchaseListItems.inventoryItems',
        ])
            ->where('status', 'Purchase Orders')
            ->orderByDesc('purchase_date');

        $this->applyPurchaseOrderFilters($purchases, $request);

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $purchases;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $purchases->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($purchase) {
                $date = Carbon::parse($purchase->purchase_date)->format('j M y H:i');

                // 🧾 Nomor + Tanggal
                $purchaseNumberHtml = '
                <div>
                    <div>'.e($purchase->purchase_number).'</div>
                    <small class="text-muted">'.$date.'</small>
                </div>';

                // 👤 Supplier
                $supplier = e($purchase->supplier->name ?? '-');

                // 💰 Total Amount
                $totalAmount = 'Rp '.number_format($purchase->total_amount, 0, ',', '.');

                // 🏷️ Payment Status Badge
                $paymentStatus = strtolower($purchase->payment_status);
                $paymentBadge = match ($paymentStatus) {
                    'paid' => '<div class="badge bg-soft-success text-success">'.e($purchase->payment_status).'</div>',
                    'unpaid' => '<div class="badge bg-soft-danger text-danger">'.e($purchase->payment_status).'</div>',
                    default => '<div class="badge bg-soft-warning text-warning">'.e($purchase->payment_status).'</div>',
                };

                // 🧾 Account & Payment Method
                $accountName = e(optional($purchase->purchaseAccount)->type ?? '-');
                $paymentMethod = e($purchase->payment_method ?? '-');
                $items = $purchase->purchaseItems;
                $products = $items->map(function ($item) {
                    $approved = (float) $item->purchaseListItems->sum('quantity');
                    $remaining = max(0, (float) $item->quantity - $approved);
                    $stockInBase = (float) $item->purchaseListItems->sum(
                        fn ($purchaseListItem) => $purchaseListItem->inventoryItems->sum('stock_in')
                    );
                    $stockIn = $stockInBase / max(1, (float) ($item->unit_conversion_value ?? 1));

                    return [
                        'name' => $item->purchaseProduct->name ?? '-',
                        'sku' => $item->purchaseProduct->sku ?? '-',
                        'qty' => number_format($item->quantity, 0, ',', '.'),
                        'approved_qty' => number_format($approved, 0, ',', '.'),
                        'remaining_qty' => number_format($remaining, 0, ',', '.'),
                        'stock_in' => number_format($stockIn, 0, ',', '.'),
                        'unit' => $item->unit_name ?? 'Pcs',
                    ];
                })->toArray();

                // 🏷️ Status
                $status = strtolower($purchase->status);
                $statusBadge = match ($status) {
                    'purchase orders' => '<div class="badge bg-soft-warning text-warning">'.e($purchase->status).'</div>',
                    'purchase list' => '<div class="badge bg-soft-success text-success">'.e($purchase->status).'</div>',
                    default => '<div class="badge bg-secondary">'.e($purchase->status).'</div>',
                };

                $approvalStatus = $purchase->approval_status ?? 'Draft';
                $approvalStatusLabel = $approvalStatus === 'Approved' ? 'Verify' : $approvalStatus;
                $approvalBadgeClass = match (strtolower($approvalStatus)) {
                    'approved' => 'bg-soft-primary text-primary',
                    'partial' => 'bg-soft-warning text-warning',
                    'completed' => 'bg-soft-success text-success',
                    default => 'bg-soft-secondary text-secondary',
                };

                // ⚙️ Action button partial
                $actionHtml = view('erp.pages.purchases.purchase-orders.partials.action-button', compact('purchase'))->render();

                return [
                    'id' => $purchase->id,
                    'purchase_number' => $purchaseNumberHtml,
                    'purchase_date' => $date,
                    'supplier' => $supplier,
                    'total_amount' => $totalAmount,
                    'payment_status' => $paymentBadge,
                    'account_name' => $accountName,
                    'payment_method' => $paymentMethod,
                    'products' => $products,
                    'status' => $statusBadge,
                    'approval_status' => '<div class="badge '.$approvalBadgeClass.'">'.e($approvalStatusLabel).'</div>',
                    'action' => $actionHtml,
                    'user' => $purchase->user->name ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create()
    {
        $products = Products::query()
            ->select(['id', 'name', 'sku', 'purchase_unit_id'])
            ->with([
                'unitConversions:id,product_id,unit_id,ratio_value,conversion_value',
                'unitConversions.unit:id,name',
            ])
            ->orderBy('name', 'asc')
            ->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('erp.pages.purchases.purchase-orders.create-purchase', compact(
            'products',
            'suppliers'
        ));
    }

    public function checkNumber(Request $request)
    {
        $exists = \App\Models\Purchase::where('purchase_number', $request->purchase_number)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Nomor invoice sudah terdaftar.');
        }

        return response()->json(['exists' => $exists]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date' => 'required|date_format:Y-m-d\TH:i',
            'suppliers' => 'required|exists:suppliers,id',
            'product' => 'required|array',
            'product.*' => 'exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'numeric|min:1',

            'product_unit_id' => 'nullable|array',
            'product_unit_id.*' => 'nullable',
            'unit_conversion_value' => 'nullable|array',
            'unit_conversion_value.*' => 'nullable|numeric|min:0.01',
            'unit_name' => 'nullable|array',
            'unit_name.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchaseNumber = PurchaseNumberService::generate($request->purchase_date);

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'purchase_date' => $request->purchase_date,
                'supplier_id' => $request->suppliers,
                'status' => 'Purchase Orders',
                'approval_status' => 'Draft',
                'payment_status' => 'Pending',
                'sub_total' => 0,
                'total_amount' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'total_amount_product' => 0,
                'total_amount_freight' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                // Stock destination ditentukan saat pembuatan Purchase List, bukan di PO.
                'stock_destination' => null,
            ]);

            foreach ($request->product as $index => $productId) {
                $qty = (float) $request->qty[$index];
                $product = Products::findOrFail($productId);

                $unit = UnitConversionService::resolve(
                    (int) $productId,
                    $request->product_unit_id[$index] ?? null,
                    $request->unit_name[$index] ?? 'Pcs'
                );
                $unitConversionId = $unit['id'];
                $unitConversionValue = $unit['factor'];
                $unitName = $unit['unit_name'];

                $qtyBase = $qty * $unitConversionValue;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,

                    'product_unit_conversion_id' => $unitConversionId,
                    'unit_name' => $unitName,
                    'unit_conversion_value' => $unitConversionValue,
                    'qty_base' => $qtyBase,

                    // Gudang tujuan baru diisi saat Purchase List dibuat.
                    'inventory_warehouse_id' => null,
                    'production_warehouse_id' => null,

                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'price' => 0,
                    'freight' => 0,
                    'subtotal' => 0,
                ]);
            }

            DB::commit();

            return redirect('/erp/purchases/purchase-orders')
                ->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Purchase store failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()
                ->with('error', 'Purchase order failed to create: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $purchase = Purchase::with([
            'purchaseItems.purchaseProduct',
            'purchaseItems.productUnitConversion',
        ])->findOrFail($id);

        if (($purchase->approval_status ?? 'Draft') !== 'Draft') {
            return redirect('/erp/purchases/purchase-orders')
                ->with('error', 'PO yang sudah di-verify tidak dapat diedit.');
        }

        $products = Products::with('unitConversions.unit')
            ->orderBy('name', 'asc')
            ->get();

        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('erp.pages.purchases.purchase-orders.edit-purchase', compact(
            'purchase',
            'products',
            'suppliers',
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date' => 'required|date_format:Y-m-d\TH:i',
            'suppliers' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string',
            'product' => 'required|array',
            'product.*' => 'exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'numeric|min:1',
            // 'price'                  => 'required|array',
            // 'price.*'                => 'numeric|min:0',
            // 'freight'                => 'required|array',
            // 'freight.*'              => 'numeric|min:0',
            // 'total'                  => 'required|array',
            // 'total.*'                => 'numeric|min:0',
            // 'sub_total'              => 'required|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            // 'total_amount_product'   => 'required|numeric|min:0',
            // 'total_amount_freight'   => 'required|numeric|min:0',
            // 'total_amount'           => 'required|numeric|min:0',
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

            if (($purchase->approval_status ?? 'Draft') !== 'Draft') {
                throw new \RuntimeException('PO yang sudah di-verify tidak dapat diedit.');
            }

            // ===== 1️⃣ HITUNG TOTAL & TAX =====
            $subtotalProduct = 0;
            $subtotalFreight = 0;

            foreach ($request->product as $i => $productId) {
                $qty = $request->qty[$i] ?? 0;
                $price = $request->price[$i] ?? 0;
                $freight = $request->freight[$i] ?? 0;
                $subtotalProduct += $qty * $price;
                $subtotalFreight += $qty * $freight;
            }

            $taxPercent = $request->tax_percent ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $totalProduct = $subtotalProduct + $taxAmount;
            $totalFreight = $subtotalFreight;
            $grandTotal = $totalProduct + $totalFreight;
            $subTotal = $subtotalProduct + $subtotalFreight;

            // ===== 2️⃣ PEMBAYARAN =====
            $paidProduct = $purchase->paid_amount_product ?? 0;
            $paidFreight = $purchase->paid_amount_freight ?? 0;

            $remainingProduct = max(0, $totalProduct - $paidProduct);
            $remainingFreight = max(0, $totalFreight - $paidFreight);
            $remainingAmount = $remainingProduct + $remainingFreight;

            $status = 'Purchase Orders';
            $paymentStatus = 'Pending';

            // ===== 3️⃣ UPDATE PURCHASE HEADER =====
            $purchase->update([
                'purchase_date' => $request->purchase_date,
                'supplier_id' => $request->suppliers,
                'payment_status' => $paymentStatus,
                'sub_total' => $subTotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total_amount_product' => $totalProduct,
                'total_amount_freight' => $totalFreight,
                'total_amount' => $grandTotal,
                'paid_amount_product' => $paidProduct,
                'paid_amount_freight' => $paidFreight,
                'paid_amount' => $paidProduct + $paidFreight,
                'remaining_amount_product' => $remainingProduct,
                'remaining_amount_freight' => $remainingFreight,
                'remaining_amount' => $remainingAmount,
                'status' => $status,
                'notes' => $request->notes,
            ]);

            // ===== 4️⃣ UPDATE / INSERT ITEM BARU =====
            $processedItemIds = [];

            foreach ($request->product as $index => $productId) {
                $qty = $request->qty[$index] ?? 0;
                $price = $request->price[$index] ?? 0;
                $freight = $request->freight[$index] ?? 0;
                $total = $request->total[$index] ?? 0;

                $unit = UnitConversionService::resolve(
                    (int) $productId,
                    $request->product_unit_id[$index] ?? null,
                    $request->unit_name[$index] ?? 'Pcs'
                );
                $unitConversionId = $unit['id'];
                $unitConversionValue = $unit['factor'];
                $unitName = $unit['unit_name'];

                $qtyBase = (float) $qty * $unitConversionValue;

                $product = Products::findOrFail($productId);

                $item = PurchaseItem::updateOrCreate(
                    [
                        'purchase_id' => $purchase->id,
                        'product_id' => $productId,
                    ],
                    [
                        // Gudang tujuan baru diisi saat Purchase List dibuat.
                        'inventory_warehouse_id' => null,
                        'production_warehouse_id' => null,

                        'product_unit_conversion_id' => $unitConversionId,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,

                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'price' => $price,
                        'freight' => $freight,
                        'subtotal' => $total,
                        'deleted_at' => null,
                    ]
                );

                $processedItemIds[] = $item->id;
            }

            // ===== 5️⃣ HAPUS ITEM YANG TIDAK ADA =====
            $purchase->purchaseItems()
                ->whereNotIn('id', $processedItemIds)
                ->delete();

            // ===== 6️⃣ HANDLE INVENTORY =====
            if ($request->status === 'Purchase List') {
                $inventory = Inventory::updateOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_number' => $purchase->purchase_number,
                        'date' => $purchase->purchase_date,
                    ]
                );

                // Reset item inventory
                InventoryItem::where('inventory_id', $inventory->id)->delete();

                // Tambahkan item baru dari purchase items
                $items = $purchase->purchaseItems()->get();
                foreach ($items as $item) {
                    InventoryItem::create([
                        'inventory_id' => $inventory->id,
                        'product_id' => $item->product_id,
                        'purchase_item_id' => $item->id,
                        'quantity' => $item->quantity,
                        'unit_name' => $item->unit_name,
                        'unit_conversion_value' => $item->unit_conversion_value,
                        'qty_base' => $item->qty_base,
                        'price' => $item->price,
                        'stock_in' => 0,
                        'remaining_stock_in' => $item->qty_base,
                        'stock_out' => 0,
                    ]);
                }
            } else {
                // Jika status bukan Purchase List → hapus inventory
                $existingInventory = Inventory::where('purchase_id', $purchase->id)->first();
                if ($existingInventory) {
                    InventoryItem::where('inventory_id', $existingInventory->id)->delete();
                    $existingInventory->delete();
                }
            }

            DB::commit();

            $redirectUrl = $request->status === 'Purchase List'
                ? '/erp/purchases/purchase-list'
                : '/erp/purchases/purchase-orders';

            return redirect($redirectUrl)->with('success', 'Purchase updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase update failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->back()->with('error', 'Purchase order failed to update. '.$e->getMessage());
        }
    }

    public function approve($id)
    {
        $purchase = Purchase::where('status', 'Purchase Orders')->findOrFail($id);

        if (($purchase->approval_status ?? 'Draft') !== 'Draft') {
            return back()->with('error', 'PO ini sudah di-verify.');
        }

        if (! $purchase->purchaseItems()->exists()) {
            return back()->with('error', 'PO tanpa produk tidak dapat di-verify.');
        }

        $purchase->update(['approval_status' => 'Approved']);

        return back()->with('success', 'Purchase Order berhasil di-verify dan siap dibuatkan Purchase List.');
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

            if ($purchase->purchaseLists()->exists()) {
                throw new \RuntimeException('PO yang sudah memiliki Purchase List tidak dapat dihapus.');
            }

            $productIds = [];

            foreach ($purchase->purchaseItems as $item) {
                $productIds[] = $item->product_id;
            }

            // Hard delete semua purchase items
            PurchaseItem::where('purchase_id', $purchase->id)->forceDelete();

            // Hard delete transaksi akun kalau ada
            if ($purchase->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $purchase->transaction_group_id)->forceDelete();
            }

            // Hapus file image kalau ada
            if ($purchase->image && file_exists(public_path('storage/'.$purchase->image))) {
                unlink(public_path('storage/'.$purchase->image));
            }

            // Hard delete purchase
            $purchase->forceDelete();

            DB::commit();

            return redirect()->back()->with('success', 'Purchase deleted permanently');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete purchase: '.$e->getMessage());
        }
    }

    public function markAsPurchaseList($id)
    {
        $purchase = Purchase::with([
            'supplier',
            'purchaseItems.purchaseProduct',
            'purchaseItems.productUnitConversion',
            'purchaseItems.purchaseListItems',
        ])->findOrFail($id);

        if ($purchase->status !== 'Purchase Orders') {
            return redirect('/erp/purchases/purchase-orders')
                ->with('error', 'Dokumen sumber bukan Purchase Order.');
        }

        if (! in_array($purchase->approval_status, ['Approved', 'Partial'], true)) {
            return redirect('/erp/purchases/purchase-orders')
                ->with('error', 'PO harus di-verify sebelum membuat Purchase List.');
        }

        $purchase->purchaseItems->each(function ($item) {
            $approved = (float) $item->purchaseListItems->sum('quantity');
            $item->setAttribute('approved_quantity', $approved);
            $item->setAttribute('remaining_quantity', max(0, (float) $item->quantity - $approved));
        });

        $purchase->setRelation(
            'purchaseItems',
            $purchase->purchaseItems->filter(fn ($item) => $item->remaining_quantity > 0)->values()
        );

        if ($purchase->purchaseItems->isEmpty()) {
            $purchase->update(['approval_status' => 'Completed']);

            return redirect('/erp/purchases/purchase-orders')
                ->with('error', 'Seluruh quantity PO sudah dibuatkan Purchase List.');
        }

        $suppliers = Supplier::whereKey($purchase->supplier_id)->get();
        $products = Products::with('unitConversions.unit')->orderBy('name', 'asc')
            ->addSelect([
                'last_price' => DB::table('purchase_items as pi')
                    ->select('pi.price')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.price', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1),
            ])
            ->get();

        return view('erp.pages.purchases.purchase-orders.mark-as-purchase-list', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function updatePurchaseList(Request $request, $id)
    {
        return $this->createPurchaseListFromOrder($request, $id);

        // Legacy conversion flow is intentionally kept below for migration history.
        // It is unreachable because a PO must now remain as the parent document.
        $request->validate([
            'purchase_number' => 'required|string|unique:purchases,purchase_number,'.$id,
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
            $purchase = Purchase::with(['purchaseItems'])->findOrFail($id);
            $purchaseDate = Carbon::parse($request->purchase_date);

            // ====== DUE DATE ======
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

            // =============== HITUNG TOTAL ===============
            $totalProduct = $request->total_amount_product;
            $totalFreight = $request->total_amount_freight;
            $taxPercent = $request->tax_percent ?? 0;

            // ✅ Pajak hanya dihitung dari product
            $taxAmount = ($totalProduct * $taxPercent) / 100;

            // ✅ Product (sudah termasuk pajak)
            $totalProductWithTax = $totalProduct + $taxAmount;

            // ✅ Grand total = produk (termasuk pajak) + freight
            $grandTotal = $totalProductWithTax + $totalFreight;

            // ✅ Semua unpaid
            $paidProduct = 0;
            $remainingProduct = $totalProductWithTax;
            $paidFreight = 0;
            $remainingFreight = $totalFreight;

            foreach ($purchase->purchaseItems as $oldItem) {
                if ($purchase->stock_destination === 'warehouse' && $oldItem->inventory_warehouse_id) {
                    $oldStock = InventoryStock::where('product_id', $oldItem->product_id)
                        ->where('inventory_warehouse_id', $oldItem->inventory_warehouse_id)
                        ->first();
                    if ($oldStock) {
                        $oldQtyBase = $oldItem->qty_base ?? ($oldItem->quantity * ($oldItem->unit_conversion_value ?? 1));
                        $oldStock->decrement('incoming_stock', $oldQtyBase);
                    }
                }

                if ($purchase->stock_destination === 'production' && $oldItem->production_warehouse_id) {
                    $oldStock = ProductionStock::where('product_id', $oldItem->product_id)
                        ->where('production_warehouse_id', $oldItem->production_warehouse_id)
                        ->first();
                    if ($oldStock) {
                        $oldQtyBase = $oldItem->qty_base ?? ($oldItem->quantity * ($oldItem->unit_conversion_value ?? 1));
                        $oldStock->decrement('incoming_stock', $oldQtyBase);
                    }
                }
            }

            // ====== UPDATE HEADER ======
            $purchase->update([
                'purchase_number' => $request->purchase_number,
                'purchase_date' => $purchaseDate,
                'due_date' => $dueDate,
                'supplier_id' => $request->suppliers,
                'payment_status' => $paymentStatus,

                // detail nilai
                'sub_total' => $totalProduct + $totalFreight,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'freight_total' => $totalFreight,

                // ✅ Produk (sudah termasuk pajak)
                'total_amount_product' => $totalProductWithTax,
                'paid_amount_product' => $paidProduct,
                'remaining_amount_product' => $remainingProduct,

                // ✅ Freight (tanpa pajak)
                'total_amount_freight' => $totalFreight,
                'paid_amount_freight' => $paidFreight,
                'remaining_amount_freight' => $remainingFreight,

                // ✅ Grand total
                'total_amount' => $grandTotal,
                'paid_amount' => 0,
                'remaining_amount' => $grandTotal,
                'status' => $status,
                'stock_destination' => $request->stock_destination,
            ]);

            $purchase->refresh();

            $inventoryStatus = match ($request->stock_destination) {
                'warehouse' => 'Stock In',
                'production' => 'Stock In Production',
            };

            // foreach ($purchase->purchaseItems as $oldItem) {
            //     $oldStock = InventoryStock::where('product_id', $oldItem->product_id)
            //         ->where('inventory_warehouse_id', $oldItem->inventory_warehouse_id ?? 1)
            //         ->first();
            //     if ($oldStock) {
            //         $oldStock->decrement('incoming_stock', $oldItem->quantity);
            //     }
            // }

            // ======= UPDATE / CREATE ITEM ==========
            foreach ($request->product as $index => $productId) {
                $qty = $request->qty[$index];
                $price = $request->price[$index];
                $freight = $request->freight[$index];
                $total = $request->total[$index];

                $unitConversionId = $request->product_unit_id[$index] ?? null;

                if (! is_numeric($unitConversionId)) {
                    $unitConversionId = null;
                }

                $unitConversionValue = (float) ($request->unit_conversion_value[$index] ?? 1);
                $unitName = $request->unit_name[$index] ?? 'Pcs';

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $qtyBase = (float) $qty * $unitConversionValue;

                // ✅ Hitung price_after_tax & final_price
                $taxPercent = $request->tax_percent ?? 0;
                $priceAfterTax = $price + ($price * $taxPercent / 100);
                $finalPrice = $priceAfterTax + $freight;

                $item = PurchaseItem::updateOrCreate(
                    [
                        'purchase_id' => $purchase->id,
                        'product_id' => $productId,
                    ],
                    [
                        'inventory_warehouse_id' => $purchase->stock_destination === 'warehouse'
                            ? ($request->inventory_warehouse_id ?? 1)
                            : null,

                        'production_warehouse_id' => $purchase->stock_destination === 'production'
                            ? ($request->production_warehouse_id ?? 2)
                            : null,

                        'product_unit_conversion_id' => $unitConversionId,
                        'unit_name' => $unitName,
                        'unit_conversion_value' => $unitConversionValue,
                        'qty_base' => $qtyBase,

                        'status' => 'Purchase Account',
                        'quantity' => $qty,
                        'price' => $price,
                        'price_after_tax' => $priceAfterTax,
                        'freight' => $freight,
                        'final_price' => $finalPrice,
                        'subtotal' => $total,
                        'deleted_at' => null,
                    ]
                );

                // // === Update atau buat Inventory ===
                // $inventory = Inventory::firstOrCreate(
                //     ['purchase_id' => $purchase->id],
                //     [
                //         'purchase_number' => $purchase->purchase_number,
                //         'supplier_id'     => $purchase->supplier_id,
                //         'date'            => $purchase->purchase_date,
                //         'status'          => 'Stock In',
                //         'note'            => 'Purchase Account',
                //     ]
                // );

                // $inventoryStatus = $purchase->stock_destination === 'production'
                //     ? 'Stock In Production'
                //     : 'Stock In';

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

                $inventoryItem = InventoryItem::firstOrNew([
                    'purchase_item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                ]);

                $inventoryItem->fill([
                    'product_id' => $productId,
                    // 'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 1,
                    'inventory_warehouse_id' => $purchase->stock_destination === 'warehouse'
                        ? ($request->inventory_warehouse_id ?? 1)
                        : null,

                    'production_warehouse_id' => $purchase->stock_destination === 'production'
                        ? ($request->production_warehouse_id ?? 2)
                        : null,
                    'quantity' => $qty,
                    'price' => $price,
                    'stock_in' => 0,
                    'remaining_stock_in' => $qtyBase,
                    'stock_out' => 0,
                ]);
                $inventoryItem->save();

                // $inventoryStock = InventoryStock::firstOrCreate(
                //     [
                //         'product_id' => $productId,
                //         'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                //     ],
                //     ['incoming_stock' => 0]
                // );

                // $inventoryStock->increment('incoming_stock', $qtyBase);

                // 🧩 UPDATE INCOMING STOCK SESUAI DESTINATION
                if ($purchase->stock_destination === 'warehouse') {
                    $inventoryStock = InventoryStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $inventoryStock->increment('incoming_stock', $qtyBase);
                }

                if ($purchase->stock_destination === 'production') {
                    $productionStock = ProductionStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'production_warehouse_id' => $request->production_warehouse_id ?? 2,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $productionStock->increment('incoming_stock', $qtyBase);
                }
            }

            // ======= UPDATE / CREATE TRANSAKSI AKUN ==========
            $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

            $trx = AccountTransaction::firstOrNew([
                'purchase_id' => $purchase->id,
                'account_id' => $purchaseAccount->id,
            ]);

            $trx->fill([
                'purchase_number' => $purchase->purchase_number,
                'transaction_date' => $purchase->purchase_date,
                'debit' => $grandTotal,
                'credit' => 0,
                'note' => 'Purchase Account Transaction (Updated)',
                'particular' => 'Purchase Invoice',
                'transaction_group_id' => $trx->transaction_group_id ?? Str::uuid(),
            ]);
            $trx->save();

            DB::commit();

            return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase list berhasil diperbarui tanpa hapus data lama!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase update failed: '.$e->getMessage());

            return back()->with('error', 'Gagal memperbarui purchase list: '.$e->getMessage());
        }
    }

    private function createPurchaseListFromOrder(Request $request, int $id)
    {
        $validated = $request->validate([
            'purchase_number' => 'required|string|unique:purchases,purchase_number',
            'purchase_date' => 'required|date_format:Y-m-d\TH:i',
            'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date' => 'nullable|date',
            'suppliers' => 'required|exists:suppliers,id',
            'source_purchase_item_id' => 'required|array',
            'source_purchase_item_id.*' => 'required|integer|exists:purchase_items,id',
            'product' => 'required|array',
            'product.*' => 'required|integer|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'numeric|min:0',
            'price' => 'required|array',
            'price.*' => 'numeric|min:0',
            'freight' => 'required|array',
            'freight.*' => 'numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0',
            'stock_destination' => 'required|in:warehouse,production',
            'waybill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $order = Purchase::with('purchaseItems')
                ->where('status', 'Purchase Orders')
                ->lockForUpdate()
                ->findOrFail($id);

            if (! in_array($order->approval_status, ['Approved', 'Partial'], true)) {
                throw new \RuntimeException('PO harus di-verify sebelum membuat Purchase List.');
            }

            if ((int) $validated['suppliers'] !== (int) $order->supplier_id) {
                throw new \RuntimeException('Supplier Purchase List harus sama dengan supplier PO.');
            }

            $purchaseDate = Carbon::parse($validated['purchase_date']);
            $dueDate = match ($validated['due_date_option'] ?? 'none') {
                'today' => $purchaseDate->copy(),
                '1_week' => $purchaseDate->copy()->addWeek(),
                '1_month' => $purchaseDate->copy()->addMonth(),
                '3_months' => $purchaseDate->copy()->addMonths(3),
                'custom' => ! empty($validated['custom_due_date']) ? Carbon::parse($validated['custom_due_date']) : null,
                default => null,
            };

            $rows = [];
            $subtotalProduct = 0;
            $subtotalFreight = 0;

            foreach ($validated['source_purchase_item_id'] as $index => $sourceItemId) {
                $qty = (float) ($validated['qty'][$index] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $sourceItem = $order->purchaseItems->firstWhere('id', (int) $sourceItemId);
                if (! $sourceItem || (int) $sourceItem->product_id !== (int) $validated['product'][$index]) {
                    throw new \RuntimeException('Produk PL tidak sesuai dengan produk pada PO.');
                }

                $alreadyAllocated = (float) PurchaseItem::where('source_purchase_item_id', $sourceItem->id)
                    ->sum('quantity');
                $remaining = max(0, (float) $sourceItem->quantity - $alreadyAllocated);

                if ($qty > $remaining) {
                    throw new \RuntimeException(
                        "Qty {$sourceItem->purchaseProduct?->name} melebihi sisa PO ({$remaining} {$sourceItem->unit_name})."
                    );
                }

                $price = (float) ($validated['price'][$index] ?? 0);
                $freight = (float) ($validated['freight'][$index] ?? 0);
                $subtotalProduct += $qty * $price;
                $subtotalFreight += $qty * $freight;
                $rows[] = compact('sourceItem', 'qty', 'price', 'freight');
            }

            if (empty($rows)) {
                throw new \RuntimeException('Isi minimal satu quantity produk untuk membuat Purchase List.');
            }

            // Waybill difoto/diupload saat Purchase List dibuat (pola sama dengan Stock In).
            $waybillImagePath = null;
            if ($request->hasFile('waybill_image')) {
                $image = $request->file('waybill_image');
                $filename = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
                $uploadPath = base_path('public/uploads/waybill_image');

                if (! file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $filename);
                $waybillImagePath = 'uploads/waybill_image/'.$filename;
            }

            // Stock destination ditentukan di form Purchase List (PO tidak lagi menyimpannya).
            $stockDestination = $validated['stock_destination'];
            $inventoryWarehouseId = $stockDestination === 'warehouse'
                ? ($request->inventory_warehouse_id ?? 1)
                : null;
            $productionWarehouseId = $stockDestination === 'production'
                ? ($request->production_warehouse_id ?? 2)
                : null;

            $taxPercent = (float) ($validated['tax_percent'] ?? 0);
            $taxAmount = $subtotalProduct * $taxPercent / 100;
            $totalProduct = $subtotalProduct + $taxAmount;
            $grandTotal = $totalProduct + $subtotalFreight;

            $purchaseList = Purchase::create([
                'parent_purchase_id' => $order->id,
                'purchase_number' => $validated['purchase_number'],
                'purchase_date' => $purchaseDate,
                'due_date' => $dueDate,
                'supplier_id' => $order->supplier_id,
                'status' => 'Purchase List',
                'approval_status' => 'Approved',
                'payment_status' => 'Unpaid',
                'stock_destination' => $stockDestination,
                'sub_total' => $subtotalProduct + $subtotalFreight,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'total_amount_product' => $totalProduct,
                'paid_amount_product' => 0,
                'remaining_amount_product' => $totalProduct,
                'total_amount_freight' => $subtotalFreight,
                'paid_amount_freight' => 0,
                'remaining_amount_freight' => $subtotalFreight,
                'total_amount' => $grandTotal,
                'paid_amount' => 0,
                'remaining_amount' => $grandTotal,
                'notes' => $request->notes,
                'waybill_image' => $waybillImagePath,
                'user_id' => auth()->id(),
            ]);

            $inventoryStatus = $stockDestination === 'production'
                ? 'Stock In Production'
                : 'Stock In';
            $inventory = Inventory::create([
                'purchase_id' => $purchaseList->id,
                'purchase_number' => $purchaseList->purchase_number,
                'date' => $purchaseDate,
                'status' => $inventoryStatus,
                'note' => 'Purchase Account',
            ]);

            foreach ($rows as $row) {
                /** @var PurchaseItem $sourceItem */
                $sourceItem = $row['sourceItem'];
                $qty = $row['qty'];
                $price = $row['price'];
                $freight = $row['freight'];
                $conversion = (float) ($sourceItem->unit_conversion_value ?: 1);
                $qtyBase = $qty * $conversion;
                $priceAfterTax = $price + ($price * $taxPercent / 100);

                $item = PurchaseItem::create([
                    'purchase_id' => $purchaseList->id,
                    'source_purchase_item_id' => $sourceItem->id,
                    'product_id' => $sourceItem->product_id,
                    'product_unit_conversion_id' => $sourceItem->product_unit_conversion_id,
                    'unit_name' => $sourceItem->unit_name,
                    'unit_conversion_value' => $conversion,
                    'qty_base' => $qtyBase,
                    'inventory_warehouse_id' => $inventoryWarehouseId,
                    'production_warehouse_id' => $productionWarehouseId,
                    'status' => 'Purchase Account',
                    'quantity' => $qty,
                    'price' => $price,
                    'price_after_tax' => $priceAfterTax,
                    'freight' => $freight,
                    'final_price' => $priceAfterTax + $freight,
                    'subtotal' => $qty * ($price + $freight),
                    'stock_in' => 0,
                ]);

                InventoryItem::create([
                    'inventory_id' => $inventory->id,
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'inventory_warehouse_id' => $item->inventory_warehouse_id,
                    'production_warehouse_id' => $item->production_warehouse_id,
                    'quantity' => $qty,
                    'qty_base' => $qtyBase,
                    'unit_name' => $item->unit_name,
                    'unit_conversion_value' => $conversion,
                    'price' => $price,
                    'stock_in' => 0,
                    'remaining_stock_in' => $qtyBase,
                    'stock_out' => 0,
                ]);

                if ($stockDestination === 'warehouse') {
                    InventoryStock::firstOrCreate([
                        'product_id' => $item->product_id,
                        'inventory_warehouse_id' => $item->inventory_warehouse_id ?? 1,
                    ], ['incoming_stock' => 0])->increment('incoming_stock', $qtyBase);
                } else {
                    ProductionStock::firstOrCreate([
                        'product_id' => $item->product_id,
                        'production_warehouse_id' => $item->production_warehouse_id ?? 2,
                    ], ['incoming_stock' => 0])->increment('incoming_stock', $qtyBase);
                }
            }

            $purchaseAccount = Account::where('type', 'Purchase Account')->first();
            if ($purchaseAccount) {
                AccountTransaction::create([
                    'purchase_id' => $purchaseList->id,
                    'account_id' => $purchaseAccount->id,
                    'purchase_number' => $purchaseList->purchase_number,
                    'transaction_date' => $purchaseDate,
                    'debit' => $grandTotal,
                    'credit' => 0,
                    'note' => 'Purchase Account Transaction',
                    'particular' => 'Purchase Invoice',
                    'transaction_group_id' => Str::uuid(),
                    'verified' => 1,
                ]);
                $purchaseAccount->increment('closing_balance', $grandTotal);
            }

            $this->refreshPurchaseOrderProgress($order);
            DB::commit();

            return redirect('/erp/purchases/purchase-list?purchase_order_id='.$order->id)
                ->with('success', 'Purchase List dan draft Stock In berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Create purchase list from PO failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function refreshPurchaseOrderProgress(Purchase $order): void
    {
        $items = $order->purchaseItems()->get();
        $ordered = (float) $items->sum('quantity');
        $allocated = (float) PurchaseItem::whereIn('source_purchase_item_id', $items->pluck('id'))->sum('quantity');

        $order->update([
            'approval_status' => $allocated <= 0
                ? 'Approved'
                : ($allocated >= $ordered ? 'Completed' : 'Partial'),
        ]);
    }
}
