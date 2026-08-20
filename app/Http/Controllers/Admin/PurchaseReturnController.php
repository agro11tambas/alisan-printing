<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Products;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnEditHistory;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ProductCostService;
use Illuminate\Support\Facades\Auth;

class PurchaseReturnController extends Controller
{
    public function getPurchaseReturns()
    {
        $purchase_number = PurchaseReturn::first();
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $defaultAccount = Account::where('is_default', true)->first();

        return view('erp.pages.purchases.purchase-returns.purchase-returns', compact('purchase_number', 'transactionTypes', 'cashAccounts', 'bankAccounts', 'defaultAccount'));
    }

    public function dataPurchaseReturns(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchases = PurchaseReturn::with(['supplier', 'items.product'])
            ->where('status', 'Purchase Returns')
            ->orderByDesc('id');

        // ✅ Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $purchases->whereDate('return_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $purchases->whereBetween('return_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $purchases->whereMonth('return_date', Carbon::now()->month)
                        ->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $purchases->whereBetween('return_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $purchases->whereBetween('return_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $purchases->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $purchases->whereBetween('return_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // ✅ Filter payment status dan pencarian
        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $purchases->whereIn('payment_status', ['Paid', 'Over Refunded']);
            } else {
                $purchases->where('payment_status', $request->payment_status);
            }
        } elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'supplier') {
                $purchases->whereHas('supplier', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $purchases->where('purchase_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        // ✅ Ambil data sesuai offset dan limit
        [$data, $hasMore] = $this->lazyLoadPage($purchases, $start, $length);

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($purchase) {
                $date = Carbon::parse($purchase->return_date)->format('j M y H:i');

                // 🧾 Nomor + tanggal + badge Edited
                $html = '';
                if ((int)($purchase->status_edited ?? 0) === 1) {
                    $html .= '<div class="mb-1">
                    <span class="badge bg-soft-primary text-primary">Edited</span>
                </div>';
                }
                $html .= '
                <div>
                    <div>' . e($purchase->purchase_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                // 👤 Supplier
                $supplier = e($purchase->supplier->name ?? '-');

                // 💰 Total
                $totalAmount = 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');

                // 💸 Refund dan Remaining
                // $refundTotal = ($purchase->refund_amount_product ?? 0) + ($purchase->refund_amount_freight ?? 0);
                // $remainingTotal = ($purchase->remaining_amount_product ?? 0) + ($purchase->remaining_amount_freight ?? 0);

                // $refundHtml = '<span class="text-success">Rp ' . number_format($refundTotal, 0, ',', '.') . '</span>';
                // $remainingHtml = '<span class="text-danger">Rp ' . number_format($remainingTotal, 0, ',', '.') . '</span>';

                // =========================
                // 🔹 Pisah Product & Freight
                // =========================

                // PRODUCT
                $totalProduct   = $purchase->total_amount_product ?? 0;
                $refundProduct  = $purchase->refund_amount_product ?? 0;
                $remainProduct  = $purchase->remaining_amount_product ?? 0;

                // FREIGHT
                $totalFreight   = $purchase->total_amount_freight ?? 0;
                $refundFreight  = $purchase->refund_amount_freight ?? 0;
                $remainFreight  = $purchase->remaining_amount_freight ?? 0;

                // Format tampilan
                $totalProductHtml  = '<span class="text-primary fw-semibold">Rp ' . number_format($totalProduct, 0, ',', '.') . '</span>';
                $totalFreightHtml  = '<span class="text-primary fw-semibold">Rp ' . number_format($totalFreight, 0, ',', '.') . '</span>';

                $refundProductColumn = '
                    <div class="text-success fw-semibold">Rp ' . number_format($refundProduct, 0, ',', '.') . '</div>'
                    . ($remainProduct > 0
                        ? '<small class="text-danger fw-semibold">Remaining: Rp ' . number_format($remainProduct, 0, ',', '.') . '</small>'
                        : ''
                    );

                $refundFreightColumn = '
                    <div class="text-success fw-semibold">Rp ' . number_format($refundFreight, 0, ',', '.') . '</div>'
                    . ($remainFreight > 0
                        ? '<small class="text-danger fw-semibold">Remaining: Rp ' . number_format($remainFreight, 0, ',', '.') . '</small>'
                        : ''
                    );

                // 🏷️ Payment Status + Verified check
                $paymentStatus = strtolower($purchase->payment_status);
                $badgeClass = match ($paymentStatus) {
                    'paid'          => 'bg-soft-success text-success',
                    'over refunded' => 'bg-soft-primary text-primary',
                    'unpaid'        => 'bg-soft-danger text-danger',
                    'partially paid' => 'bg-soft-warning text-warning',
                    default          => 'bg-secondary',
                };

                $verifiedIcon = '';
                if ($purchase->verified) {
                    $verifiedIcon = ' <i class="fa fa-check-circle text-success ms-1" title="Verified"></i>';
                }

                $paymentBadge = '<div class="badge ' . $badgeClass . '">' . ucfirst($paymentStatus) . '</div>' . $verifiedIcon;

                // 🏦 Account
                $account = e($purchase->account ?? '-');

                // 📦 Products
                $products = $purchase->items->map(function ($item) {
                    return [
                        'name'  => $item->product?->name ?? '-',
                        'sku'   => $item->product?->sku ?? '-',
                        'qty'   => number_format($item->quantity ?? 0, 0, ',', '.'),
                        'price' => number_format($item->price ?? 0, 2, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 2, ',', '.'),
                        'total_price' => number_format(($item->price ?? 0) + ($item->freight ?? 0), 2, ',', '.'),
                    ];
                })->toArray();

                // 📋 Status
                $status = strtolower($purchase->status);
                $statusBadge = match ($status) {
                    'purchase orders' => '<div class="badge bg-soft-warning text-warning">' . e($purchase->status) . '</div>',
                    'purchase list' => '<div class="badge bg-soft-success text-success">' . e($purchase->status) . '</div>',
                    default => '<div class="badge bg-secondary">' . e($purchase->status) . '</div>',
                };

                // ⚙️ Action Partial
                $actionHtml = view('erp.pages.purchases.purchase-returns.partials.action-button', compact('purchase'))->render();

                return [
                    'id' => $purchase->id,
                    'purchase_number' => $html,
                    'return_date' => $date,
                    'supplier' => $supplier,
                    // 'total_amount' => $totalAmount,
                    // 'refund_amount' => $refundHtml,
                    // 'remaining_amount' => $remainingHtml,

                    // PRODUCT
                    'total_amount_product'   => $totalProductHtml,
                    'refund_amount_product'  => $refundProductColumn,

                    // FREIGHT
                    'total_amount_freight'   => $totalFreightHtml,
                    'refund_amount_freight'  => $refundFreightColumn,

                    'payment_status' => $paymentBadge,
                    'account' => $account,
                    'products' => $products,
                    'status' => $statusBadge,
                    'action' => $actionHtml,
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    public function dataDeletedPurchaseReturns(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $returns = PurchaseReturn::onlyTrashed()
            ->with(['supplier', 'items.product', 'deletedByUser'])
            ->where('status', 'Purchase Returns')
            ->orderByDesc('id');

        // === FILTER DATE ===
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $returns->whereDate('return_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $returns->whereBetween('return_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $returns->whereMonth('return_date', Carbon::now()->month)
                        ->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $returns->whereBetween('return_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $returns->whereBetween('return_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $returns->whereYear('return_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $returns->whereBetween('return_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // === FILTER SEARCH ===
        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $returns->whereIn('payment_status', ['Paid', 'Over Refunded']);
            } else {
                $returns->where('payment_status', $request->payment_status);
            }
        } elseif ($request->filled('search_keyword')) {
            if ($request->search_type === 'supplier') {
                $returns->whereHas('supplier', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $returns->where('purchase_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        // ✅ Ambil data sesuai offset dan limit
        [$data, $hasMore] = $this->lazyLoadPage($returns, $start, $length);

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($return) {
                $date = $return->return_date ? Carbon::parse($return->return_date)->format('j M y') : '-';
                $deletedAt = $return->deleted_at ? $return->deleted_at->format('j M y H:i') : '-';

                // 🧾 Purchase Number + Date
                $purchaseNumberHtml = '
                <div>
                    <div>' . e($return->purchase_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                // 👤 Supplier
                $supplier = e($return->supplier->name ?? '-');

                // 💰 Grand Total
                $totalAmount = '<span class="text-primary">Rp ' . number_format($return->total_amount, 0, ',', '.') . '</span>';

                // 📦 Products
                $products = $return->items->map(function ($item) {
                    return [
                        'name'  => $item->product?->name ?? '-',
                        'sku'   => $item->product?->sku ?? '-',
                        'qty'   => number_format($item->quantity ?? 0, 0, ',', '.'),
                        'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 0, ',', '.'),
                        'total_price' => number_format(($item->price ?? 0) + ($item->freight ?? 0), 2, ',', '.'),
                    ];
                })->toArray();

                // 📝 Delete notes & Deleted by
                $deleteNotes = e($return->delete_notes ?? '-');
                $deletedBy = e($return->deletedByUser->name ?? '-');

                // ⚙️ Action Buttons (Owner only)
                $action = '';
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    $action = '
                    <div class="d-flex gap-2">
                        <button type="button" 
                            class="btn btn-success btn-sm me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRestoreOrder"
                            data-id="' . $return->id . '" 
                            data-name="' . e($return->purchase_number) . '"
                            data-url="' . route('purchase-returns.restore', $return->id) . '">
                                Restore
                        </button>
                        <button type="button" 
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalForceDeleteOrder"
                            data-id="' . $return->id . '" 
                            data-name="' . e($return->purchase_number) . '"
                            data-url="' . route('purchase-returns.forceDelete', $return->id) . '">
                                Hapus Permanen
                        </button>
                    </div>';
                }

                return [
                    'id' => $return->id,
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
            'has_more' => $hasMore,
        ]);
    }

    public function create($id)
    {
        $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

        if (!$purchase->hasStockIn()) {
            return redirect()->back()->with('error', 'Tidak bisa membuat Purchase Return karena barang belum masuk ke warehouse.');
        }

        $products = Products::orderBy('name', 'asc')->get();

        // Hitung sisa qty return per item.
        //
        // Qty yang sudah diretur diambil sekali untuk semua produk sekaligus.
        // Versi lama menjalankan satu query berisi whereHas untuk tiap item PO.
        $returnedPerProduct = PurchaseReturnItem::query()
            ->whereIn('product_id', $purchase->purchaseItems->pluck('product_id')->filter()->unique())
            ->whereHas('purchaseReturn', fn ($q) => $q->where('purchase_id', $purchase->id))
            ->selectRaw('product_id, SUM(quantity) as returned_quantity')
            ->groupBy('product_id')
            ->pluck('returned_quantity', 'product_id');

        $remainingItems = $purchase->purchaseItems->map(function ($item) use ($returnedPerProduct) {
            $returnedQty = (float) ($returnedPerProduct[$item->product_id] ?? 0);

            // qty yang belum direturn
            $item->remaining_qty = max(0, $item->quantity - $returnedQty);

            return $item;
        });

        $suppliers = Supplier::all();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();
        $transactionTypes = Account::where('name', 'Purchase Return')->get();
        $accounts = Account::all();

        return view('erp.pages.purchases.purchase-returns.create-purchase', compact(
            'purchase',
            'products',
            'remainingItems', // gunakan ini di blade
            'suppliers',
            'cashAccounts',
            'bankAccounts',
            'transactionTypes',
            'accounts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_id'             => 'required|exists:purchases,id',
            'purchase_number'         => 'required|string',
            'return_date'             => 'required|date_format:Y-m-d\TH:i',
            'status'                  => 'required|string',
            'suppliers'               => 'required|exists:suppliers,id',
            'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
            'product'                 => 'required|array',
            'product.*'               => 'exists:products,id',
            'qty'                     => 'required|array',
            'qty.*'                   => 'numeric|min:1',
            'price'                   => 'required|array',
            'price.*'                 => 'numeric|min:0',
            'freight'                 => 'required|array',
            'freight.*'               => 'numeric|min:0',
            'total'                   => 'required|array',
            'total.*'                 => 'numeric|min:0',
            'sub_total'               => 'required|numeric|min:0',
            'total_amount'            => 'required|numeric|min:0',
            'note'                    => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $groupId = Str::uuid();

            // 🔹 Hitung total produk dan freight secara terpisah
            $totalProduct = 0;
            $totalFreight = 0;

            foreach ($request->product as $i => $productId) {
                $qty = (float) $request->qty[$i];
                $price = (float) $request->price[$i];
                $freight = (float) $request->freight[$i];

                $totalProduct += $qty * $price;
                $totalFreight += $qty * $freight;
            }

            $grandTotal = $totalProduct + $totalFreight;

            // 🔹 Pembayaran (sementara diisi 0)
            $paidProduct = $request->paid_amount_product ?? 0;
            $paidFreight = $request->paid_amount_freight ?? 0;

            $remainingProduct = $totalProduct - $paidProduct;
            $remainingFreight = $totalFreight - $paidFreight;
            $remainingAmount  = $remainingProduct + $remainingFreight;

            $paymentStatus = ($paidProduct + $paidFreight) <= 0
                ? 'Unpaid'
                : (($paidProduct + $paidFreight) < $grandTotal ? 'Partially Paid' : 'Paid');

            // 🔹 1️⃣ Simpan Purchase Return Header
            $purchaseReturn = PurchaseReturn::create([
                'purchase_id'              => $request->purchase_id,
                'supplier_id'              => $request->suppliers,
                'purchase_number'          => $request->purchase_number,
                'return_date'              => $request->return_date,
                'status'                   => $request->status,
                'account'                  => 'Purchase Return',
                'payment_status'           => $paymentStatus,

                // 💰 Total breakdown
                'total_amount_product'     => $totalProduct,
                'refund_amount_product'      => $paidProduct,
                'remaining_amount_product' => $remainingProduct,
                'total_amount_freight'     => $totalFreight,
                'refund_amount_freight'      => $paidFreight,
                'remaining_amount_freight' => $remainingFreight,

                // 💰 Grand total summary
                'total_amount'             => $grandTotal,
                'refund_amount'            => $paidProduct + $paidFreight,
                'remaining_amount'         => $remainingAmount,

                'transaction_type'         => $request->transaction_type,
                'transaction_group_id'     => $groupId,
                'note'                     => $request->note ?? null,
                'reason'                   => $request->reason ?? null,
            ]);

            // 🔹 2️⃣ Buat Inventory Header (Stock Out)
            $inventory = Inventory::create([
                'purchase_return_id' => $purchaseReturn->id,
                'purchase_number'    => $purchaseReturn->purchase_number,
                'date'               => $purchaseReturn->return_date,
                'status'             => 'Stock Out',
                'note'               => 'Purchase Returns',
            ]);

            // 🔹 3️⃣ Simpan Item dan Inventory Items
            $touchedProductIds = [];
            foreach ($request->product as $i => $productId) {
                $qty     = (float) $request->qty[$i];
                if ($qty <= 0) continue;

                $price   = (float) $request->price[$i];
                $freight = (float) $request->freight[$i];
                $total   = (float) $request->total[$i];

                $item = PurchaseReturnItem::create([
                    'purchase_return_id'     => $purchaseReturn->id,
                    'product_id'             => $productId,
                    'inventory_warehouse_id' => $request->inventory_warehouse_id,
                    'purchase_item_id'       => $request->purchase_item_ids[$i] ?? null,
                    'status'                 => 'Purchase Return',
                    'quantity'               => $qty,
                    'price'                  => $price,
                    'freight'                => $freight,
                    'total'                  => $total,
                ]);

                // 🔹 Buat Inventory Item (Stock Out)
                InventoryItem::create([
                    'inventory_id'            => $inventory->id,
                    'product_id'              => $productId,
                    'inventory_warehouse_id'  => $request->inventory_warehouse_id,
                    'purchase_return_item_id' => $item->id,
                    'quantity'                => $qty,
                    'price'                   => $price,
                    'stock_in'                => 0,
                    'stock_out'               => 0,
                    'remaining_stock_in'      => $qty,
                ]);

                $touchedProductIds[] = $productId;
            }

            // 🔹 4️⃣ Update stok dan cost
            // foreach (array_unique($touchedProductIds) as $pid) {
            //     if ($product = Products::find($pid)) {
            //         ProductCostService::updateCostAndStock($product);
            //     }
            // }

            // 🔹 5️⃣ Buat jurnal akuntansi (opsional)
            if (!empty($request->transaction_type)) {
                $purchaseAccount = Account::findOrFail($request->transaction_type);

                AccountTransaction::create([
                    'purchase_return_id'   => $purchaseReturn->id,
                    'purchase_number'      => $purchaseReturn->purchase_number,
                    'transaction_date'     => $request->return_date,
                    'account_id'           => $purchaseAccount->id,
                    'credit'               => $grandTotal, // pengurangan hutang
                    'debit'                => 0,
                    'note'                 => $request->note ?? '',
                    'particular'           => 'Purchase Return',
                    'transaction_group_id' => $groupId,
                    'verified'             => 1,
                ]);

                // jika tipe akun liability, credit akan menambah saldo hutang
                $purchaseAccount->increment('closing_balance', $grandTotal);
            }

            DB::commit();
            return redirect('/erp/purchases/purchase-returns')->with('success', 'Purchase return berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Return Store Failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Gagal menyimpan purchase return: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $purchaseReturn = PurchaseReturn::with(['items.product'])->findOrFail($id);

        // Ambil purchase asli dari purchase return ini
        $purchase = $purchaseReturn->purchase()
            ->with(['purchaseItems.purchaseProduct'])
            ->first();

        // Satu query untuk semua item, bukan satu query per item: purchase
        // dengan puluhan baris sebelumnya menembak puluhan query di sini.
        $returnedPerItem = PurchaseReturnItem::query()
            ->whereIn('purchase_item_id', $purchase->purchaseItems->pluck('id'))
            ->where('purchase_return_id', '!=', $purchaseReturn->id)
            ->groupBy('purchase_item_id')
            ->selectRaw('purchase_item_id, SUM(quantity) as returned_quantity')
            ->pluck('returned_quantity', 'purchase_item_id');

        $expandedItems = collect();

        foreach ($purchase->purchaseItems as $item) {
            // Qty yang sudah direturn kecuali purchaseReturn ini
            $returnedQty = (float) ($returnedPerItem[$item->id] ?? 0);

            // hitung sisa qty
            $item->remaining_qty = max(0, $item->quantity - $returnedQty);

            // cek apakah sudah ada di purchaseReturn ini
            $existingItem = $purchaseReturn->items->where('purchase_item_id', $item->id)->first();

            $item->return_qty = $existingItem->quantity ?? 0;

            // PurchaseItem tidak punya relasi `product` — yang ada `purchaseProduct`.
            // Sebelumnya baris ini membaca properti dari null tiap kali item belum
            // pernah direturn, jadi harganya kosong.
            $item->return_price = $existingItem->price ?? optional($item->purchaseProduct)->price;

            $expandedItems->push($item);
        }

        $products       = Products::all();
        $suppliers      = Supplier::all();
        $transactionTypes = Account::where('name', 'Purchase Return')->get();
        $cashAccounts   = Account::where('name', 'Cash')->get();
        $bankAccounts   = Account::where('name', 'Bank')->get();
        $accounts       = Account::all();

        return view('erp.pages.purchases.purchase-returns.edit-purchase', [
            'purchaseReturn' => $purchaseReturn,
            'purchase'       => $purchase,
            'products'       => $products,
            'suppliers'      => $suppliers,
            'transactionTypes' => $transactionTypes,
            'cashAccounts'   => $cashAccounts,
            'bankAccounts'   => $bankAccounts,
            'accounts'       => $accounts,
            'remainingItems' => $expandedItems, // jangan filter → tampil semua
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'return_date'             => 'required|date_format:Y-m-d\TH:i',
            'suppliers'               => 'required|exists:suppliers,id',
            'purchase_number'         => 'required|string',
            'status'                  => 'required|string',
            'notes'                   => 'nullable|string',
            'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
            'product'                 => 'required|array',
            'product.*'               => 'exists:products,id',
            'qty'                     => 'required|array',
            'qty.*'                   => 'numeric|min:1',
            'price'                   => 'required|array',
            'price.*'                 => 'numeric|min:0',
            'freight'                 => 'required|array',
            'freight.*'               => 'numeric|min:0',
            'total'                   => 'required|array',
            'total.*'                 => 'numeric|min:0',
            'total_amount_product'    => 'required|numeric|min:0',
            'total_amount_freight'    => 'required|numeric|min:0',
            'total_amount'            => 'required|numeric|min:0',
            'image'                   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'edit_note'               => 'required|string|max:500',
            'note'                    => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::with(['items', 'accountTransactions'])->findOrFail($id);

            if ($purchaseReturn->hasStockOut()) {
                DB::rollBack();
                return back()->with('error', 'Purchase Return ini sudah memiliki Stock Out dan tidak bisa diedit lagi.');
            }

            // ===== 1) SNAPSHOT LAMA (buat log edit)
            $oldReturn = $purchaseReturn->only([
                'purchase_number',
                'return_date',
                'supplier_id',
                'status',
                'total_amount',
                'refund_amount',
                'remaining_amount',
                'payment_status',
                'total_amount_product',
                'total_amount_freight',
            ]);

            $oldItems = $purchaseReturn->items->mapWithKeys(fn($i) => [
                $i->product_id => [
                    'product'  => $i->product_name,
                    'quantity' => $i->quantity,
                    'price'    => $i->price,
                    'freight'  => $i->freight,
                    'total'    => $i->total,
                ]
            ]);

            // ===== 2) Hitung ulang total
            $totalProduct = (float) $request->total_amount_product;
            $totalFreight = (float) $request->total_amount_freight;
            $grandTotal   = (float) $request->total_amount;

            $paidAmount      = $purchaseReturn->refund_amount ?? 0;
            $remainingAmount = $grandTotal - $paidAmount;
            $paymentStatus   = $paidAmount <= 0 ? 'Unpaid' : ($paidAmount < $grandTotal ? 'Partially Paid' : 'Refunded');

            // ===== 3) Handle image
            if ($request->hasFile('image')) {
                if ($purchaseReturn->image && file_exists(public_path('storage/' . $purchaseReturn->image))) {
                    @unlink(public_path('storage/' . $purchaseReturn->image));
                }
                $image    = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('storage/uploads/purchase_returns'), $filename);
                $purchaseReturn->image = 'uploads/purchase_returns/' . $filename;
            }

            // ===== 4) Update header PurchaseReturn
            $purchaseReturn->update([
                'purchase_number'        => $request->purchase_number,
                'return_date'            => $request->return_date,
                'supplier_id'            => $request->suppliers,
                'status'                 => $request->status,
                'note'                   => $request->note,
                'payment_status'         => $paymentStatus,
                'total_amount_product'   => $totalProduct,
                'total_amount_freight'   => $totalFreight,
                'total_amount'           => $grandTotal,
                'refund_amount'          => $paidAmount,
                'remaining_amount'       => $remainingAmount,
            ]);

            if (empty($purchaseReturn->transaction_group_id)) {
                $purchaseReturn->update(['transaction_group_id' => Str::uuid()]);
            }

            // ===== 5) INVENTORY HEADER (belum stock out)
            $inventory = Inventory::firstOrCreate(
                ['purchase_return_id' => $purchaseReturn->id],
                [
                    'purchase_number' => $request->purchase_number,
                    'date'            => $request->return_date,
                    'status'          => 'Draft',
                    'note'            => 'Purchase Return (Draft)',
                ]
            );
            $inventory->update([
                'purchase_number' => $request->purchase_number,
                'date'            => $request->return_date,
                'status'          => 'Draft',
                'note'            => 'Purchase Return (Draft)',
            ]);

            // ===== 6) Update Items
            $existingItems     = $purchaseReturn->items->keyBy('product_id');
            $requestProductIds = [];
            $touchedProducts   = [];

            foreach ($request->product as $idx => $productId) {
                $qty      = (int) $request->qty[$idx];
                $price    = (float) $request->price[$idx];
                $freight  = (float) $request->freight[$idx];
                $subtotal = (float) $request->total[$idx];
                $product  = Products::findOrFail($productId);

                $requestProductIds[] = $productId;
                $touchedProducts[]   = $productId;

                $existingItem = $purchaseReturn->items->firstWhere('product_id', $productId);

                if ($existingItem) {
                    $invItem = \App\Models\InventoryItem::where('purchase_return_item_id', $existingItem->id)->first();

                    if ($invItem && $qty < $invItem->stock_out) {
                        DB::rollBack();
                        return back()->with(
                            'error',
                            "Gagal mengupdate Purchase Return {$purchaseReturn->purchase_number}: Quantity untuk produk {$product->name} (" . number_format($qty) . ") tidak boleh lebih kecil dari jumlah stock_out (" . number_format($invItem->stock_out) . ")."
                        );
                    }
                }

                if ($existingItems->has($productId)) {
                    $item = $existingItems[$productId];
                    $item->update([
                        'quantity'               => $qty,
                        'price'                  => $price,
                        'freight'                => $freight,
                        'total'                  => $subtotal,
                        'inventory_warehouse_id' => $request->inventory_warehouse_id,
                    ]);

                    InventoryItem::updateOrCreate(
                        ['purchase_return_item_id' => $item->id],
                        [
                            'inventory_id'            => $inventory->id,
                            'product_id'              => $productId,
                            'inventory_warehouse_id'  => $request->inventory_warehouse_id,
                            'quantity'                => $qty,
                            'price'                   => $price,
                            'stock_in'                => 0,
                            'stock_out'               => 0,
                            'remaining_stock_in'      => $qty,
                        ]
                    );
                } else {
                    $newItem = PurchaseReturnItem::create([
                        'purchase_return_id'     => $purchaseReturn->id,
                        'product_id'             => $productId,
                        'inventory_warehouse_id' => $request->inventory_warehouse_id,
                        'product_name'           => $product->name,
                        'status'                 => 'Purchase Return',
                        'quantity'               => $qty,
                        'price'                  => $price,
                        'freight'                => $freight,
                        'total'                  => $subtotal,
                    ]);

                    InventoryItem::create([
                        'inventory_id'            => $inventory->id,
                        'product_id'              => $productId,
                        'inventory_warehouse_id'  => $newItem->inventory_warehouse_id,
                        'purchase_return_item_id' => $newItem->id,
                        'quantity'                => $qty,
                        'price'                   => $price,
                        'stock_in'                => 0,
                        'stock_out'               => 0,
                        'remaining_stock_in'      => $qty,
                    ]);
                }
            }

            // ===== 7) Hapus item yang tidak ada di request
            foreach ($existingItems as $pid => $item) {
                if (!in_array($pid, $requestProductIds)) {
                    InventoryItem::where('purchase_return_item_id', $item->id)->delete();
                    $item->delete();
                    $touchedProducts[] = $pid;
                }
            }

            // ===== 8) BUAT DIFF SNAPSHOT BARU
            $purchaseReturn->load('items');
            $newReturn = $purchaseReturn->only([
                'purchase_number',
                'return_date',
                'supplier_id',
                'status',
                'total_amount',
                'refund_amount',
                'remaining_amount',
                'payment_status',
                'total_amount_product',
                'total_amount_freight',
            ]);
            $newItems = $purchaseReturn->items->mapWithKeys(fn($i) => [
                $i->product_id => [
                    'product'  => $i->product_name ?? $i->product->name ?? 'Unknown Product',
                    'quantity' => $i->quantity,
                    'price'    => $i->price,
                    'freight'  => $i->freight,
                    'total'    => $i->total,
                ]
            ]);

            $returnDiff = ['old' => [], 'new' => []];
            foreach ($newReturn as $field => $newVal) {
                $oldVal = $oldReturn[$field] ?? null;
                if ($oldVal != $newVal) {
                    $returnDiff['old'][$field] = $oldVal;
                    $returnDiff['new'][$field] = $newVal;
                }
            }

            $itemsDiff = [];
            $allKeys = array_unique(array_merge(array_keys($oldItems->toArray()), array_keys($newItems->toArray())));
            foreach ($allKeys as $pid) {
                $old = $oldItems[$pid] ?? null;
                $new = $newItems[$pid] ?? null;

                if ($old && !$new) {
                    $itemsDiff[] = ['product' => $old['product'], 'action' => 'removed'];
                } elseif (!$old && $new) {
                    $itemsDiff[] = ['product' => $new['product'], 'action' => 'added'];
                } elseif ($old && $new) {
                    $changed = [];
                    foreach (['quantity', 'price', 'freight', 'total'] as $f) {
                        if ($old[$f] != $new[$f]) {
                            $changed[$f] = ['old' => $old[$f], 'new' => $new[$f]];
                        }
                    }
                    if ($changed) {
                        $itemsDiff[] = ['product' => $new['product'], 'action' => 'updated', 'fields' => $changed];
                    }
                }
            }

            $changes = ['purchase_return' => $returnDiff, 'items' => $itemsDiff];

            // ===== 9) Update transaksi akun (Purchase Return)
            $purchaseReturnAccount = Account::where('type', 'Purchase Return')->firstOrFail();
            $existingTx = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                ->where('account_id', $purchaseReturnAccount->id)
                ->first();

            if (!$existingTx) {
                AccountTransaction::create([
                    'purchase_return_id'   => $purchaseReturn->id,
                    'purchase_number'      => $request->purchase_number,
                    'transaction_date'     => $request->return_date,
                    'account_id'           => $purchaseReturnAccount->id,
                    'credit'               => $grandTotal,
                    'debit'                => 0,
                    'note'                 => $request->notes ?? '',
                    'particular'           => 'Purchase Return',
                    'transaction_group_id' => $purchaseReturn->transaction_group_id,
                ]);
                $purchaseReturnAccount->increment('closing_balance', $grandTotal);
            } else {
                $diff = $grandTotal - $existingTx->credit;
                $existingTx->update([
                    'transaction_date' => $request->return_date,
                    'credit'           => $grandTotal,
                    'note'             => $request->notes ?? '',
                ]);
                if ($diff != 0) {
                    $purchaseReturnAccount->increment('closing_balance', $diff);
                }
            }

            // ===== 10) Simpan edit history
            PurchaseReturnEditHistory::create([
                'purchase_return_id' => $purchaseReturn->id,
                'edited_by'          => Auth::id(),
                'changes'            => $changes,
                'text'               => $request->edit_note,
                'edited_at'          => now(),
            ]);

            $purchaseReturn->update(['status_edited' => true]);

            DB::commit();
            return redirect('/erp/purchases/purchase-returns')->with('success', 'Purchase Return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Return Update Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
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

    public function delete($id, Request $request)
    {
        $request->validate([
            'delete_notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $purchaseReturn = PurchaseReturn::with('items')->findOrFail($id);

            // 🚫 Cek apakah sudah ada Stock Out
            if ($purchaseReturn->hasStockOut()) {
                DB::rollBack();
                $msg = 'Gagal menghapus Purchase Return karena sudah memiliki Stock Out.';
                return $this->deleteResponse($request, false, $msg);
            }

            // 🚫 Cek dulu apakah ada constraint lain kalau perlu
            // (misal: tidak boleh hapus kalau sudah dipakai di settlement, dll)

            // 🔁 Rollback stok keluar (stock_out) dari Purchase Return
            foreach ($purchaseReturn->items as $item) {
                $inventoryStock = InventoryStock::where('product_id', $item->product_id)->first();
                if ($inventoryStock) {
                    $stockOutQty = InventoryItem::where('purchase_return_item_id', $item->id)
                        ->where('stock_out', '>', 0)
                        ->sum('stock_out');

                    if ($stockOutQty > 0) {
                        // Kembalikan stok yang sempat dikurangi
                        $inventoryStock->increment('stock_after_sales', $stockOutQty);

                        // Hapus log stock out
                        InventoryItem::where('purchase_return_item_id', $item->id)->delete();
                    }
                }
            }

            $productIds = $purchaseReturn->items->pluck('product_id')->filter()->unique()->toArray();

            // 🔁 Handle account transactions (persis dengan Purchase)
            $transactions = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Purchase Return') {
                    // Hapus transaksi Purchase Return Account
                    $account->closing_balance -= $trx->debit;
                    $account->closing_balance += $trx->credit;
                    $trx->delete();
                } else {
                    // Cash / Bank → jangan dihapus, hanya unlink
                    $trx->purchase_return_id = null;
                    $trx->note = trim(($trx->note ?? '') . ' [Purchase Return deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // 🔁 Hapus items return
            PurchaseReturnItem::where('purchase_return_id', $purchaseReturn->id)->delete();

            // 🔁 Hapus inventory header kalau ada
            if ($inventory = Inventory::where('purchase_return_id', $purchaseReturn->id)->first()) {
                InventoryItem::where('inventory_id', $inventory->id)->delete();
                $inventory->delete();
            }

            // Simpan delete_notes & deleted_by
            $purchaseReturn->delete_notes = $request->input('delete_notes');
            $purchaseReturn->deleted_by   = Auth::id();
            $purchaseReturn->save();

            // Soft delete purchase return
            $purchaseReturn->delete();

            DB::commit();
            return back()->with('success', 'Purchase Return berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase return delete failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus purchase return: ' . $e->getMessage());
        }
    }

    // public function markAsRefund($id, Request $request)
    // {
    //     $request->merge([
    //         'paid_amount' => str_replace('.', '', $request->paid_amount),
    //     ]);

    //     $request->validate([
    //         'purchase_return_id'    => 'required|exists:purchase_returns,id',
    //         'paid_amount'           => 'required|numeric|min:0',
    //         'cash_bank_account_id'  => 'required|exists:accounts,id',
    //         'transaction_date'      => 'required|date',
    //         'transaction_type'      => 'required|exists:accounts,id',
    //         'note'                  => 'nullable|string',
    //         'particular'            => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $purchaseReturn = PurchaseReturn::findOrFail($request->purchase_return_id);

    //         // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
    //         $groupId = Str::uuid();

    //         $purchaseAccount  = Account::findOrFail($request->transaction_type);    // akun pembelian (kredit)
    //         $cashBankAccount  = Account::findOrFail($request->cash_bank_account_id); // akun kas/bank (debit)

    //         // Transaksi DEBIT (kas/bank bertambah karena refund dari supplier)
    //         AccountTransaction::create([
    //             'purchase_return_id'   => $purchaseReturn->id,
    //             'purchase_number'      => $purchaseReturn->purchase_number,
    //             'transaction_date'     => $request->transaction_date,
    //             'account_id'           => $cashBankAccount->id,
    //             'debit'                => $request->paid_amount,
    //             'credit'               => 0,
    //             'note'                 => $request->note ?? '',
    //             'particular'           => $purchaseAccount->name . ' - ' . $purchaseAccount->type,
    //             'transaction_group_id' => $groupId,
    //         ]);

    //         $cashBankAccount->closing_balance += $request->paid_amount;
    //         $cashBankAccount->save();

    //         // Update refund_amount di purchase_returns
    //         $purchaseReturn->refund_amount += $request->paid_amount;
    //         $purchaseReturn->remaining_amount = $purchaseReturn->total_amount - $purchaseReturn->refund_amount;

    //         // Update payment_status
    // if ($purchaseReturn->refund_amount == $purchaseReturn->total_amount) {
    //     $purchaseReturn->payment_status = 'Refunded';
    // } elseif ($purchaseReturn->refund_amount > $purchaseReturn->total_amount) {
    //     $purchaseReturn->payment_status = 'Over Refunded';
    // } else {
    //     $purchaseReturn->payment_status = 'Partially Refunded';
    // }

    // if (!$purchaseReturn->transaction_group_id) {
    //     $purchaseReturn->transaction_group_id = $groupId;
    // }

    //         $purchaseReturn->save();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Refund berhasil disimpan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal menyimpan refund: ' . $e->getMessage());
    //     }
    // }

    public function markAsRefundProduct($id, Request $request)
    {
        // ubah titik jadi kosong (misal "1.000" → "1000")
        $request->merge([
            'refund_amount' => str_replace('.', '', $request->refund_amount),
        ]);

        $request->validate([
            'purchase_return_id'     => 'required|exists:purchase_returns,id',
            'refund_amount'          => 'required|numeric|min:0',
            'cash_bank_account_id'   => 'required|exists:accounts,id',
            'transaction_date'       => 'required|date',
            'transaction_type'       => 'required|exists:accounts,id',
            'note'                   => 'nullable|string',
            'particular'             => 'nullable|string',
            'payment_proof'          => 'nullable|array',
            'payment_proof.*'        => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'         => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::findOrFail($request->purchase_return_id);
            $groupId = Str::uuid();

            $purchaseReturnAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

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
                        'file' => 'uploads/refund_proofs/' . $fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // 1️⃣ Cash/Bank → DEBIT (uang keluar untuk refund)
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => $request->refund_amount,
                'credit'                => 0,
                'note'                  => 'Refund Product',
                'particular'            => 'Refund Product - ' . $purchaseReturnAccount->name,
                'transaction_group_id'  => $groupId,
                'proof'                 => $proofJson,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->refund_amount);

            // 2️⃣ Purchase Return Account → CREDIT
            // AccountTransaction::create([
            //     'purchase_return_id'    => $purchaseReturn->id,
            //     'purchase_number'       => $purchaseReturn->purchase_number,
            //     'transaction_date'      => $request->transaction_date,
            //     'account_id'            => $purchaseReturnAccount->id,
            //     'debit'                 => 0,
            //     'credit'                => $request->refund_amount,
            //     'note'                  => $request->note ?? '',
            //     'particular'            => 'Refund Product - ' . $cashBankAccount->name,
            //     'transaction_group_id'  => $groupId,
            //     'proof'         => $proofPath,
            // ]);
            // $purchaseReturnAccount->decrement('closing_balance', $request->refund_amount);

            // 3️⃣ Update refund field (akumulatif)
            $purchaseReturn->refund_amount_product += $request->refund_amount;
            $purchaseReturn->remaining_amount_product = $purchaseReturn->total_amount_product - $purchaseReturn->refund_amount_product;

            $totalRefunded = $purchaseReturn->refund_amount_product + $purchaseReturn->refund_amount_freight;
            $totalAll = $purchaseReturn->total_amount_product + $purchaseReturn->total_amount_freight;

            if ($totalRefunded == $totalAll) {
                $purchaseReturn->payment_status = 'Refunded';
            } elseif ($totalRefunded > $totalAll) {
                $purchaseReturn->payment_status = 'Over Refunded';
            } elseif ($totalRefunded > 0) {
                $purchaseReturn->payment_status = 'Partially Refunded';
            } else {
                $purchaseReturn->payment_status = 'Unrefunded';
            }

            $purchaseReturn->transaction_group_id = $groupId;
            $purchaseReturn->verified = false;
            $purchaseReturn->save();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return back()->with('success', 'Refund produk berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return back()->with('error', 'Gagal menyimpan refund produk: ' . $e->getMessage());
        }
    }

    public function markAsRefundFreight($id, Request $request)
    {
        $request->merge([
            'refund_amount' => str_replace('.', '', $request->refund_amount),
        ]);

        $request->validate([
            'purchase_return_id'     => 'required|exists:purchase_returns,id',
            'refund_amount'          => 'required|numeric|min:0',
            'cash_bank_account_id'   => 'required|exists:accounts,id',
            'transaction_date'       => 'required|date',
            'transaction_type'       => 'required|exists:accounts,id',
            'note'                   => 'nullable|string',
            'particular'             => 'nullable|string',
            'payment_proof'          => 'nullable|array',
            'payment_proof.*'        => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'         => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::findOrFail($request->purchase_return_id);
            $groupId = Str::uuid();

            $purchaseReturnAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // =====================================================
            // 🔹 Upload bukti + note
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
                        'file' => 'uploads/refund_proofs/' . $fileName,
                        'note' => $notes[$index] ?? '',
                    ];
                }
            }

            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // 1️⃣ Cash/Bank → DEBIT (uang keluar)
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => $request->refund_amount,
                'credit'                => 0,
                'note'                  => 'Refund Freight',
                'particular'            => 'Refund Freight - ' . $purchaseReturnAccount->name,
                'transaction_group_id'  => $groupId,
                'proof'                 => $proofJson,
            ]);
            $cashBankAccount->decrement('closing_balance', $request->refund_amount);

            // 2️⃣ Purchase Return Account → CREDIT
            // AccountTransaction::create([
            //     'purchase_return_id'    => $purchaseReturn->id,
            //     'purchase_number'       => $purchaseReturn->purchase_number,
            //     'transaction_date'      => $request->transaction_date,
            //     'account_id'            => $purchaseReturnAccount->id,
            //     'debit'                 => 0,
            //     'credit'                => $request->refund_amount,
            //     'note'                  => $request->note ?? '',
            //     'particular'            => 'Refund Freight - ' . $cashBankAccount->name,
            //     'transaction_group_id'  => $groupId,
            //     'proof'         => $proofPath,
            // ]);
            // $purchaseReturnAccount->decrement('closing_balance', $request->refund_amount);

            // 3️⃣ Update refund field (akumulatif)
            $purchaseReturn->refund_amount_freight += $request->refund_amount;
            $purchaseReturn->remaining_amount_freight = $purchaseReturn->total_amount_freight - $purchaseReturn->refund_amount_freight;

            $totalRefunded = $purchaseReturn->refund_amount_product + $purchaseReturn->refund_amount_freight;
            $totalAll = $purchaseReturn->total_amount_product + $purchaseReturn->total_amount_freight;

            if ($totalRefunded == $totalAll) {
                $purchaseReturn->payment_status = 'Refunded';
            } elseif ($totalRefunded > $totalAll) {
                $purchaseReturn->payment_status = 'Over Refunded';
            } elseif ($totalRefunded > 0) {
                $purchaseReturn->payment_status = 'Partially Refunded';
            } else {
                $purchaseReturn->payment_status = 'Unrefunded';
            }

            $purchaseReturn->transaction_group_id = $groupId;
            $purchaseReturn->verified = false;
            $purchaseReturn->save();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return back()->with('success', 'Refund freight berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil disimpan.',
                ]);
            }
            return back()->with('error', 'Gagal menyimpan refund freight: ' . $e->getMessage());
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

            // ✅ Ambil purchase_return_id dari transaksi pertama
            $purchaseReturnId = $transactions->first()->purchase_return_id;

            if ($purchaseReturnId) {
                // 🔍 Ambil semua transaksi dengan purchase_return_id sama
                $purchaseReturnTransactions = AccountTransaction::where('purchase_return_id', $purchaseReturnId)->get();

                // 🔎 Hitung total transaksi & yang sudah verified
                $verifiedCount = $purchaseReturnTransactions->where('verified', true)->count();
                $totalCount = $purchaseReturnTransactions->count();

                // ✅ Kalau semua transaksi verified → PurchaseReturn verified = true
                if ($totalCount > 0 && $verifiedCount === $totalCount) {
                    \App\Models\PurchaseReturn::where('id', $purchaseReturnId)->update(['verified' => true]);
                } else {
                    // ❌ Kalau masih ada transaksi belum verified → tetap false
                    \App\Models\PurchaseReturn::where('id', $purchaseReturnId)->update(['verified' => false]);
                }
            }

            return response()->json([
                'message' => 'Refund berhasil diverifikasi.',
                'group_id' => $groupId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal verifikasi refund: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentHistory($id)
    {
        $purchaseReturn = PurchaseReturn::with('supplier')->findOrFail($id);

        $transactions = AccountTransaction::with('account')
            ->where('purchase_return_id', $purchaseReturn->id)
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy('transaction_group_id');

        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-returns.payment-history', [
            'purchaseReturn' => $purchaseReturn,
            'transactions'   => $transactions,
            'cashAccounts'   => $cashAccounts,
            'bankAccounts'   => $bankAccounts,
        ]);
    }

    public function updatePayment(Request $request, $groupId)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'transaction_date'      => 'required|date',
            'paid_amount'           => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'payment_proof'         => 'nullable|array',
            'payment_proof.*'       => 'file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'note_per_image'        => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Refund not found");
            }

            $purchaseReturnId = $transactions->first()->purchase_return_id;
            $purchaseReturn   = PurchaseReturn::findOrFail($purchaseReturnId);

            foreach ($transactions as $trx) {
                $trx->update(['verified' => false]);
            }
            $purchaseReturn->update(['verified' => false]);

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
                $uploadPath = public_path('uploads/payment_proofs');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($request->file('payment_proof') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($uploadPath, $fileName);

                    $uploadedProofs[] = [
                        'file' => 'uploads/refund_proofs/' . $fileName,
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

            $proofJson = !empty($uploadedProofs) ? json_encode($uploadedProofs) : null;

            // =====================================================
            // 🔥 Jika paid_amount = 0 → hapus semua transaksi dalam group
            // =====================================================
            if ($request->paid_amount == 0) {

                // tentukan jenis group sebelum delete
                $firstTrx = $transactions->first();
                $type = str_contains(strtolower($firstTrx->particular), 'freight')
                    ? 'freight'
                    : 'product';

                // rollback saldo semua trx
                foreach ($transactions as $trx) {
                    $account = $trx->account;

                    if ($trx->debit > 0) {
                        $account->increment('closing_balance', $trx->debit);
                    } elseif ($trx->credit > 0) {
                        $account->decrement('closing_balance', $trx->credit);
                    }

                    $trx->delete();
                }

                // ============ FIX DI SINI =============

                if ($type === 'product') {
                    // HANYA reset product
                    $totalRefundProduct = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                        ->where('particular', 'like', '%product%')
                        ->sum('debit');

                    $purchaseReturn->refund_amount_product = $totalRefundProduct;
                    $purchaseReturn->remaining_amount_product = max(0, $purchaseReturn->total_amount_product - $totalRefundProduct);

                    // freight tidak disentuh
                }

                if ($type === 'freight') {
                    // HANYA reset freight
                    $totalRefundFreight = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                        ->where('particular', 'like', '%freight%')
                        ->sum('debit');

                    $purchaseReturn->refund_amount_freight = $totalRefundFreight;
                    $purchaseReturn->remaining_amount_freight = max(0, $purchaseReturn->total_amount_freight - $totalRefundFreight);

                    // product tidak disentuh
                }

                // hitung status gabungan
                $totalRefunded = ($purchaseReturn->refund_amount_product ?? 0)
                    + ($purchaseReturn->refund_amount_freight ?? 0);

                $totalAll = $purchaseReturn->total_amount_product + $purchaseReturn->total_amount_freight;

                if ($totalRefunded == 0) {
                    $purchaseReturn->payment_status = 'Unrefunded';
                } elseif ($totalRefunded < $totalAll) {
                    $purchaseReturn->payment_status = 'Partially Refunded';
                } elseif ($totalRefunded == $totalAll) {
                    $purchaseReturn->payment_status = 'Refunded';
                } else {
                    $purchaseReturn->payment_status = 'Over Refunded';
                }

                $purchaseReturn->save();

                DB::commit();

                // =====================================================
                // 🔹 AJAX Return → Frontend hapus card payment
                // =====================================================
                if ($request->ajax()) {
                    return response()->json([
                        'status'   => 'deleted',
                        'message'  => 'Refund berhasil dihapus.',
                        'group_id' => $groupId,
                    ]);
                }

                return back()->with('success', 'Refund berhasil dihapus.');
            }

            // =====================================================
            // 🔹 Identify Refund Type: Product vs Freight
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
            // 🔹 Refund Process
            // =====================================================
            $oldDebit = $transactions->firstWhere('debit', '>', 0);
            if (!$oldDebit) throw new \Exception("Debit transaction (Cash/Bank) not found in this group");

            $oldAccount = $oldDebit->account;
            $oldAmount  = $oldDebit->debit;

            // rollback saldo lama (refund = uang keluar → tambahkan kembali)
            $oldAccount->increment('closing_balance', $oldAmount);

            // update transaksi debit lama → akun/amount/date/note + proof
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldDebit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => $request->paid_amount,
                'note'             => $request->note ?? '',
                'proof'            => $proofJson,
            ]);

            // update saldo akun baru (refund = uang keluar → kurangi saldo)
            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // update juga baris kredit (Purchase Return) biar sinkron
            $returnTrx = $transactions->firstWhere('credit', '>', 0);
            if ($returnTrx) {
                $returnTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note'             => $request->note ?? '',
                ]);
            }

            // =====================================================
            // 🔹 Hitung ulang refund_product & refund_freight
            // =====================================================
            $totalRefundProduct = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                ->where('debit', '>', 0)
                ->where('particular', 'like', '%product%')
                ->sum('debit');

            $totalRefundFreight = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                ->where('debit', '>', 0)
                ->where('particular', 'like', '%freight%')
                ->sum('debit');

            $purchaseReturn->refund_amount_product = $totalRefundProduct;
            $purchaseReturn->remaining_amount_product = max(0, $purchaseReturn->total_amount_product - $totalRefundProduct);

            $purchaseReturn->refund_amount_freight = $totalRefundFreight;
            $purchaseReturn->remaining_amount_freight = max(0, $purchaseReturn->total_amount_freight - $totalRefundFreight);

            // Status gabungan
            $totalRefunded = $totalRefundProduct + $totalRefundFreight;
            $totalAll = $purchaseReturn->total_amount_product + $purchaseReturn->total_amount_freight;

            if ($totalRefunded == 0) {
                $purchaseReturn->payment_status = 'Unrefunded';
            } elseif ($totalRefunded < $totalAll) {
                $purchaseReturn->payment_status = 'Partially Refunded';
            } elseif ($totalRefunded == $totalAll) {
                $purchaseReturn->payment_status = 'Refunded';
            } elseif ($totalRefunded > $totalAll) {
                $purchaseReturn->payment_status = 'Over Refunded';
            }

            $purchaseReturn->save();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Refund berhasil diperbarui.',
                    'data'    => [
                        'transaction_group_id' => $groupId,
                        'transaction_date'     => \Carbon\Carbon::parse($request->transaction_date)->format('d-m-Y'),
                        'paid_amount'          => number_format($request->paid_amount, 0, ',', '.'),
                        'account_id'           => $cashBankAccount->id,
                        'account_name'         => $cashBankAccount->name,
                        'account_type'         => $cashBankAccount->type,
                        'note'                 => $request->note ?? '',
                        'proofs'               => $uploadedProofs,
                        'verified'             => false,
                    ],
                ]);
            }

            return back()->with('success', 'Refund berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update refund: ' . $e->getMessage());
        }
    }

    public function getEditHistory($id)
    {
        // Ambil purchase return
        $purchaseReturn = PurchaseReturn::findOrFail($id);

        // Ambil semua edit histories untuk purchase return ini
        $histories = PurchaseReturnEditHistory::with('user')
            ->where('purchase_return_id', $id)
            ->orderBy('edited_at', 'desc')
            ->get();

        // dd($histories->toArray());

        return view('erp.pages.purchases.purchase-returns.edit-purchase-histories', compact('purchaseReturn', 'histories'));
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();
        try {
            $return = PurchaseReturn::onlyTrashed()->findOrFail($id);
            $return->forceDelete(); // booted() di model sudah handle cascade

            DB::commit();
            return redirect()->back()->with('success', 'Purchase Return berhasil dihapus permanen!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete purchase return gagal', [
                'purchase_return_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus permanen purchase return!');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $purchaseReturn = PurchaseReturn::onlyTrashed()->with('items')->findOrFail($id);

            // ✅ Restore purchase return
            $purchaseReturn->restore();

            // ✅ Restore purchase return items
            if (method_exists($purchaseReturn, 'items')) {
                $purchaseReturn->items()->withTrashed()->restore();
            }

            // ✅ Restore transaksi akun
            $transactions = AccountTransaction::withTrashed()
                ->where(function ($q) use ($purchaseReturn) {
                    $q->where('purchase_return_id', $purchaseReturn->id)
                        ->orWhere('note', 'like', '%[Purchase Return deleted]%');
                })
                ->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Purchase Return') {
                    // restore transaksi Purchase Return Account
                    if ($trx->trashed()) {
                        $trx->restore();
                    }

                    // hitung ulang closing balance
                    if ($trx->debit > 0) {
                        $account->closing_balance += $trx->debit;
                    }
                    if ($trx->credit > 0) {
                        $account->closing_balance -= $trx->credit;
                    }
                } else {
                    // Cash / Bank → hubungkan kembali
                    $trx->purchase_return_id = $purchaseReturn->id;
                    $trx->note = str_replace('[Purchase Return deleted]', '', $trx->note ?? '');
                    $trx->save();
                }

                $account->save();
            }

            // ✅ Update ulang avg cost produk
            $productIds = $purchaseReturn->items->pluck('product_id')->filter()->unique()->toArray();
            foreach ($productIds as $productId) {
                $product = Products::find($productId);
                if ($product) {
                    ProductCostService::updateCostAndStock($product);
                    $product->stock_after_sales = $product->inventory_stock;
                    $product->save();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Purchase Return berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore purchase return gagal', [
                'purchase_return_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan purchase return!');
        }
    }
}
