<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PurchaseOrderExport;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\ProductionStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\PurchaseNumberService;
use App\Services\PurchaseOrderForceDeleteService;
use App\Support\ExportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *
     * PO adalah wadah permanen per supplier, jadi filter tanggal dan nomor
     * invoice juga mencocokkan Purchase List anaknya. Tanpa itu PO lama yang
     * masih aktif menerima PL baru akan hilang dari filter "This Month".
     */
    private function applyPurchaseOrderFilters($purchases, Request $request)
    {
        $range = match ($request->filter) {
            'today' => [Carbon::today(), Carbon::today()->endOfDay()],
            'last_7_days' => [Carbon::now()->subDays(7), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'year_to_date' => [Carbon::now()->startOfYear(), Carbon::now()],
            'yearly' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => $request->filled('start_date') && $request->filled('end_date')
                ? [$request->start_date, $request->end_date]
                : null,
            default => null,
        };

        if ($range) {
            $purchases->where(fn ($query) => $query
                ->whereBetween('purchase_date', $range)
                ->orWhereHas('purchaseLists', fn ($lists) => $lists->whereBetween('purchase_date', $range))
            );
        }

        // ✅ Filter PO Status: Progress atau Completed (semua PL-nya sudah
        // stock in dan lunas). Bisa dipakai barengan dengan pencarian.
        if ($request->filled('po_status')) {
            $statuses = $request->po_status === 'completed'
                ? Purchase::APPROVAL_COMPLETED_STATUSES
                : Purchase::APPROVAL_PROGRESS_STATUSES;

            $purchases->whereIn('approval_status', $statuses);
        }

        // ✅ Filter pencarian
        if ($request->filled('search_keyword')) {
            $keyword = '%'.$request->search_keyword.'%';

            if ($request->search_type === 'supplier') {
                $purchases->whereHas('supplier', fn ($query) => $query->where('name', 'like', $keyword));
            } else {
                $purchases->where(fn ($query) => $query
                    ->where('purchase_number', 'like', $keyword)
                    ->orWhereHas('purchaseLists', fn ($lists) => $lists->where('purchase_number', 'like', $keyword))
                );
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

        // Export diurut dari transaksi paling lama ke paling baru, kebalikan
        // dari listing di halaman yang menaruh yang terbaru di atas.
        $purchases = Purchase::query()
            ->where('status', 'Purchase Orders')
            ->orderBy('purchase_date')
            ->orderBy('id');

        if ($period !== null) {
            // Periode dari modal menggantikan filter tanggal halaman. Filter
            // pencarian tetap ikut.
            // PO ikut kalau dibuat di periode itu atau punya PL di periode itu.
            $request->merge(['filter' => null]);
            $purchases->where(function ($query) use ($period) {
                $period->applyTo($query, 'purchase_date');
                $query->orWhereHas('purchaseLists', fn ($lists) => $period->applyTo($lists, 'purchase_date'));
            });
        }

        $this->applyPurchaseOrderFilters($purchases, $request);

        $filename = 'purchase-order-'.($period?->filenameSuffix() ?? Carbon::now()->format('Ymd-His')).'.xlsx';

        return (new PurchaseOrderExport($purchases))->download($filename);
    }

    public function dataPurchaseOrders(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        // PO hanya wadah per supplier, jadi semua angka di listing diambil
        // dari Purchase List anaknya. Agregatnya dihitung di SQL supaya satu
        // halaman PO tidak perlu menghidrasi seluruh item PL.
        $purchases = Purchase::with([
            'supplier:id,name',
            'user:id,name',
            'purchaseLists' => fn ($query) => $query
                ->select(['id', 'parent_purchase_id', 'purchase_number', 'purchase_date', 'total_amount_product', 'payment_status', 'stock_destination'])
                ->withSum('purchaseItems as qty_base_total', 'qty_base')
                ->withSum('purchaseInventoryItems as stock_in_base', 'stock_in')
                ->orderByDesc('purchase_date')
                ->orderByDesc('id'),
        ])
            ->withCount('purchaseLists')
            ->withSum('purchaseLists as purchase_list_product_total', 'total_amount_product')
            // PL anak yang sudah dibayar mengunci PO-nya (tombol Delete hilang).
            ->withExists([
                'purchaseLists as has_paid_purchase_list' => fn ($query) => $query
                    ->where(fn ($paid) => $paid
                        ->whereIn('payment_status', ['Paid', 'Partially Paid', 'Overpaid'])
                        ->orWhere('paid_amount', '>', 0)
                        ->orWhere('paid_amount_product', '>', 0)
                        ->orWhere('paid_amount_freight', '>', 0)
                    ),
            ])
            // Kebalikan dari purchaseListsFullyPaid(): dipakai untuk membedakan
            // PO yang barangnya sudah lengkap tapi PL-nya belum lunas.
            ->withExists([
                'purchaseLists as has_unpaid_purchase_list' => fn ($query) => $query
                    ->whereNotIn('payment_status', ['Paid', 'Overpaid'])
                    ->where('total_amount', '>', 0),
            ])
            ->where('status', 'Purchase Orders')
            // PO yang PL-nya paling baru bergerak tampil paling atas.
            ->orderByRaw('COALESCE((SELECT MAX(pl.purchase_date) FROM purchases pl
                WHERE pl.parent_purchase_id = purchases.id AND pl.deleted_at IS NULL), purchases.purchase_date) DESC')
            ->orderByDesc('id');

        $this->applyPurchaseOrderFilters($purchases, $request);

        [$data, $hasMore] = $this->lazyLoadPage($purchases, $start, $length);

        return response()->json([
            'data' => $data->map(function ($purchase) {
                $date = Carbon::parse($purchase->purchase_date)->format('j M y H:i');
                $lists = $purchase->purchaseLists;

                $isListStockedIn = fn ($list) => (float) ($list->qty_base_total ?? 0) > 0
                    && (float) ($list->stock_in_base ?? 0) >= (float) $list->qty_base_total;

                $stockInCompleted = $lists->isNotEmpty() && $lists->every($isListStockedIn);

                $completeIcon = $stockInCompleted
                    ? ' <i class="fa fa-check-circle text-success ms-1" title="Stock In selesai"></i>'
                    : '';

                // Listing PO cuma menampilkan supplier; nomor PO tidak ditampilkan.
                $supplierHtml = '
                <div>
                    <div>'.e($purchase->supplier->name ?? '-').$completeIcon.'</div>
                    <small class="text-muted">'.number_format((int) $purchase->purchase_lists_count, 0, ',', '.').' Purchase List</small>
                </div>';

                $totalAmount = 'Rp '.number_format((float) ($purchase->purchase_list_product_total ?? 0), 0, ',', '.');

                $purchaseLists = $lists->map(fn ($list) => [
                    'id' => $list->id,
                    'number' => $list->purchase_number,
                    'date' => Carbon::parse($list->purchase_date)->format('j M y H:i'),
                    'total' => 'Rp '.number_format((float) $list->total_amount_product, 0, ',', '.'),
                    'payment_status' => $list->payment_status ?: '-',
                    'destination' => $list->stock_destination === 'warehouse' ? 'Warehouse' : 'Production',
                    'stock_in_completed' => $isListStockedIn($list),
                ])->values()->toArray();

                $approvalStatus = $purchase->approval_status ?? 'Draft';

                $fullyPaid = ($purchase->purchase_lists_count ?? 0) > 0
                    && ! ($purchase->has_unpaid_purchase_list ?? false);
                $awaitingPayment = $purchase->isAwaitingPurchaseListPayment($stockInCompleted, $fullyPaid);

                $approvalStatusLabel = $purchase->approvalStatusLabel($stockInCompleted, $fullyPaid);
                $approvalBadgeClass = $awaitingPayment
                    ? 'bg-soft-warning text-warning'
                    : match (strtolower($approvalStatus)) {
                        'partial', 'completed pl' => 'bg-soft-info text-info',
                        'completed' => 'bg-soft-success text-success',
                        default => 'bg-soft-secondary text-secondary',
                    };
                $approvalBadgeTitle = $awaitingPayment
                    ? 'Stock In sudah lengkap. PO baru jadi Completed setelah seluruh Purchase List lunas.'
                    : '';

                $hasStockIn = $lists->contains(fn ($list) => (float) ($list->stock_in_base ?? 0) > 0);
                $hasPaidPurchaseList = (bool) ($purchase->has_paid_purchase_list ?? false);

                $actionHtml = view('erp.pages.purchases.purchase-orders.partials.action-button', compact(
                    'purchase',
                    'hasStockIn',
                    'hasPaidPurchaseList'
                ))->render();

                return [
                    'id' => $purchase->id,
                    'purchase_date' => $date,
                    'supplier' => $supplierHtml,
                    'total_amount' => $totalAmount,
                    'purchase_lists' => $purchaseLists,
                    'approval_status' => '<div class="badge '.$approvalBadgeClass.'" title="'.e($approvalBadgeTitle).'">'.e($approvalStatusLabel).'</div>',
                    'action' => $actionHtml,
                    'user' => $purchase->user->name ?? '-',
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    public function create()
    {
        // PO cuma wadah grouping per supplier. Supplier yang sudah punya PO
        // ditandai di form supaya user langsung tahu akan diarahkan ke sana.
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $suppliersWithOrder = Purchase::where('status', 'Purchase Orders')
            ->pluck('id', 'supplier_id');

        return view('erp.pages.purchases.purchase-orders.create-purchase', compact(
            'suppliers',
            'suppliersWithOrder'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'suppliers' => 'required|exists:suppliers,id',
        ]);

        DB::beginTransaction();

        try {
            // Satu supplier hanya punya satu PO. Dikunci supaya dua klik
            // bersamaan tidak membuat dua PO untuk supplier yang sama.
            $existing = Purchase::where('status', 'Purchase Orders')
                ->where('supplier_id', $request->suppliers)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::commit();

                return redirect('/erp/purchases/purchase-list?purchase_order_id='.$existing->id)
                    ->with('success', 'Supplier ini sudah punya Purchase Order '.$existing->purchase_number.'. Tambahkan Purchase List di sini.');
            }

            $purchaseDate = now();

            $purchase = Purchase::create([
                'purchase_number' => PurchaseNumberService::generate($purchaseDate),
                'purchase_date' => $purchaseDate,
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
                'stock_destination' => null,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return redirect('/erp/purchases/purchase-list?purchase_order_id='.$purchase->id)
                ->with('success', 'Purchase Order dibuat. Silakan tambahkan Purchase List.');
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

    public function delete($id, Request $request)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

            // PO yang sudah di-verify tetap boleh dihapus. Yang mengunci
            // penghapusan adalah Purchase List anaknya: begitu ada yang sudah
            // Stock In atau sudah dibayar, PO-nya tidak boleh hilang.
            $purchaseLists = Purchase::withTrashed()
                ->where('parent_purchase_id', $purchase->id)
                ->get();

            $stockedInList = $purchaseLists->first(fn ($list) => $list->hasStockIn());

            if ($stockedInList) {
                throw new \RuntimeException(
                    'PO ini tidak dapat dihapus karena Purchase List '
                    .($stockedInList->purchase_number ?? $stockedInList->id).' sudah memiliki Stock In.'
                );
            }

            // PL anak yang sudah dibayar (sebagian maupun lunas) juga mengunci
            // PO-nya, supaya pembayaran yang sudah tercatat tidak ikut hilang.
            // PL yang sudah di-soft delete pembayarannya sudah di-unlink, jadi
            // tidak ikut mengunci (sama seperti cek di listing).
            $paidList = $purchaseLists->whereNull('deleted_at')->first(fn ($list) => in_array($list->payment_status, ['Paid', 'Partially Paid', 'Overpaid'], true)
                || (float) ($list->paid_amount ?? 0) > 0
                || (float) ($list->paid_amount_product ?? 0) > 0
                || (float) ($list->paid_amount_freight ?? 0) > 0
            );

            if ($paidList) {
                throw new \RuntimeException(
                    'PO ini tidak dapat dihapus karena Purchase List '
                    .($paidList->purchase_number ?? $paidList->id).' sudah memiliki pembayaran.'
                );
            }

            // Soft delete, bukan forceDelete: PO yang "hilang" dari list harus
            // tetap bisa ditelusuri dan dipulihkan dari database. Nomor PO tetap
            // aman karena PurchaseNumberService sudah menghitung yang di-trash.
            // Purchase List anak (belum stock in & belum bayar) ikut di-soft
            // delete dengan pola yang sama seperti hapus Purchase List biasa,
            // supaya stok incoming dan transaksi akunnya ikut dibalik.
            $activeLists = $purchaseLists->whereNull('deleted_at');

            foreach ($activeLists as $purchaseList) {
                $this->softDeletePurchase($purchaseList);
            }

            $this->softDeletePurchase($purchase);

            DB::commit();

            Log::warning('purchase_order.deleted', [
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
                'purchase_list_count' => $activeLists->count(),
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()?->name,
                'ip' => $request->ip(),
            ]);

            $message = $activeLists->isNotEmpty()
                ? 'Purchase Order beserta Purchase List-nya berhasil dihapus.'
                : 'Purchase Order berhasil dihapus.';

            return $this->purchaseOrderDeleteResponse($request, true, $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: '.$e->getMessage());

            return $this->purchaseOrderDeleteResponse($request, false, $e->getMessage());
        }
    }

    /**
     * Soft delete satu purchase (PO maupun PL anak) beserta efek sampingnya:
     * stok incoming dibalik, transaksi Purchase Account dibalik lalu di-soft
     * delete, transaksi Cash/Bank hanya di-unlink, item & inventory kosong
     * di-soft delete. Pola sama dengan PurchaseListController::delete.
     */
    private function softDeletePurchase(Purchase $purchase): void
    {
        $purchase->loadMissing('purchaseItems');

        foreach ($purchase->purchaseItems as $item) {
            if (! $item->product_id) {
                continue;
            }

            $stockInQty = InventoryItem::where('purchase_item_id', $item->id)
                ->where('stock_in', '>', 0)
                ->sum('stock_in');
            $quantityBase = $item->qty_base ?? ($item->quantity * ($item->unit_conversion_value ?: 1));
            $incomingLeft = max(0, $quantityBase - $stockInQty);

            $stock = $purchase->stock_destination === 'production'
                ? ProductionStock::where('product_id', $item->product_id)
                    ->where('production_warehouse_id', $item->production_warehouse_id ?? 2)
                    ->first()
                : InventoryStock::where('product_id', $item->product_id)
                    ->where('inventory_warehouse_id', $item->inventory_warehouse_id ?? 1)
                    ->first();

            if ($stock && $incomingLeft > 0) {
                $stock->incoming_stock = max(0, (float) $stock->incoming_stock - $incomingLeft);
                $stock->save();
            }
        }

        $transactions = AccountTransaction::where('purchase_id', $purchase->id)->get();

        foreach ($transactions as $trx) {
            $account = Account::find($trx->account_id);
            if (! $account) {
                continue;
            }

            if ($account->type === 'Purchase Account') {
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

        PurchaseItem::where('purchase_id', $purchase->id)->delete();

        // Inventory PL yang masih kosong (belum ada stock_in) ikut dihapus.
        foreach (Inventory::where('purchase_id', $purchase->id)->get() as $inventory) {
            $inventoryItemIds = InventoryItem::where('inventory_id', $inventory->id)->pluck('id');

            $hasReceivedStock = InventoryItem::whereIn('id', $inventoryItemIds)
                ->where('stock_in', '>', 0)
                ->exists();

            if ($hasReceivedStock) {
                continue;
            }

            $stockInIds = InventoryStockIn::where('inventory_id', $inventory->id)->pluck('id');

            if ($stockInIds->isNotEmpty() || $inventoryItemIds->isNotEmpty()) {
                InventoryStockInHistory::where(function ($query) use ($stockInIds, $inventoryItemIds) {
                    $query->whereIn('inventory_stock_in_id', $stockInIds)
                        ->orWhereIn('inventory_item_id', $inventoryItemIds);
                })->delete();

                InventoryStockIn::whereIn('id', $stockInIds)->delete();
            }

            InventoryItem::whereIn('id', $inventoryItemIds)->delete();
            $inventory->delete();
        }

        $purchase->deleted_by = auth()->id();
        $purchase->save();
        $purchase->delete();
    }

    /**
     * Balasan hapus PO. Tabel PO menghapus barisnya lewat AJAX, jadi request
     * JSON harus dapat JSON (dulu selalu redirect, sehingga pesan gagalnya
     * tidak pernah kelihatan di layar).
     */
    private function purchaseOrderDeleteResponse(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => $success ? 'success' : 'error',
                'message' => $message,
            ], $success ? 200 : 400);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Hapus permanen PO beserta seluruh Purchase List anaknya.
     * Khusus Owner, pola sama dengan force delete di Purchase List.
     */
    public function forceDeleteOwner(
        $id,
        Request $request,
        PurchaseOrderForceDeleteService $forceDeleteService
    ) {
        if (! auth()->check() || auth()->user()->role !== 'Owner') {
            abort(403, 'Only Owner can force delete.');
        }

        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $filesToDelete = $forceDeleteService->execute($purchase);

            DB::commit();

            foreach ($filesToDelete as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            return redirect('/erp/purchases/purchase-orders')->with(
                'success',
                'Purchase Order beserta Purchase List-nya berhasil dihapus permanen. Stok, stock-in, inventory, dan transaksi akun telah dibalik.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Force delete purchase order failed: '.$e->getMessage(), [
                'purchase_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal force delete: '.$e->getMessage());
        }
    }
}
