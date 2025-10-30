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
        $purchases = PurchaseReturn::with('supplier')
            ->where('status', 'Purchase Returns');

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
                default:
                    // all time -> no filter
                    break;
            }
        }

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

        $purchases = $purchases->latest()->get();

        return DataTables::of($purchases)
            ->addIndexColumn()
            ->addColumn('purchase_number', function ($purchase) {
                $date = Carbon::parse($purchase->return_date)->format('j M y');

                $html = '';

                // ✅ Tambahkan badge Edited kalau status_edited = 1
                if ((int)($purchase->status_edited ?? 0) === 1) {
                    $html .= '<div class="mb-1">
                        <span class="badge bg-soft-primary text-primary">Edited</span>
                    </div>';
                }

                $html .= '
                    <div>
                        <div>' . e($purchase->purchase_number) . '</div>
                        <small class="text-muted">' . $date . '</small>
                    </div>
                ';

                return $html;
            })
            ->addColumn('return_date', function ($purchase) {
                return $purchase->return_date;
            })
            ->addColumn('supplier', function ($purchase) {
                return $purchase->supplier->name;
            })
            ->addColumn('total_amount', function ($purchase) {
                return 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
            })
            ->addColumn('refund_amount', function ($purchase) {
                $refundTotal = ($purchase->refund_amount_product ?? 0) + ($purchase->refund_amount_freight ?? 0);
                return '<span class="text-success">Rp ' . number_format($refundTotal, 0, ',', '.') . '</span>';
            })
            ->addColumn('remaining_amount', function ($purchase) {
                $remainingTotal = ($purchase->remaining_amount_product ?? 0) + ($purchase->remaining_amount_freight ?? 0);
                return '<span class="text-danger">Rp ' . number_format($remainingTotal, 0, ',', '.') . '</span>';
            })
            ->addColumn('payment_status', function ($purchase) {
                $payment_status = strtolower($purchase->payment_status);

                switch ($payment_status) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->payment_status . '</div>';
                        break;
                    case 'overpaid':
                        return '<div class="badge bg-soft-primary text-primary">' . $purchase->payment_status . '</div>';
                        break;
                    case 'unpaid':
                        return '<div class="badge bg-soft-danger text-danger">' . $purchase->payment_status . '</div>';
                        break;
                    default:
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->payment_status . '</div>';
                        break;
                }
            })
            ->addColumn('account', function ($purchase) {
                return $purchase->account;
            })
            ->addColumn('products', function ($purchase) {
                return $purchase->items->map(function ($item) {
                    return [
                        'name'  => $item->product ? $item->product->name : '-',
                        'sku'   => $item->product ? $item->product->sku : '-',
                        'qty'   => $item->quantity,
                        'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 0, ',', '.')
                    ];
                })->toArray();
            })
            ->addColumn('status', function ($purchase) {
                $status = strtolower($purchase->status);

                switch ($status) {
                    case 'purchase orders':
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->status . '</div>';
                        break;
                    case 'purchase list':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->status . '</div>';
                        break;
                }
            })
            ->addColumn('action', function ($purchase) {
                return view('erp.pages.purchases.purchase-returns.partials.action-button', compact('purchase'))->render();
            })
            ->rawColumns(['purchase_number', 'total_amount', 'refund_amount', 'remaining_amount', 'payment_status', 'action', 'products'])
            ->make(true);
    }

    public function dataDeletedPurchaseReturns(Request $request)
    {
        $returns = PurchaseReturn::onlyTrashed()
            ->with(['supplier', 'items.product'])
            ->where('status', 'Purchase Returns')
            ->latest()
            ->get();

        return DataTables::of($returns)
            ->addIndexColumn()
            ->addColumn('purchase_number', function ($return) {
                $date = $return->return_date ? Carbon::parse($return->return_date)->format('j M y') : '-';
                return '<div>
                <div>' . $return->purchase_number . '</div>
                <small class="text-muted">' . $date . '</small>
            </div>';
            })
            ->addColumn('supplier', fn($return) => $return->supplier->name ?? '-')
            ->addColumn('grand_total', fn($return) => '<span class="text-primary">Rp ' . number_format($return->total_amount, 0, ',', '.') . '</span>')
            ->addColumn('deleted_at', fn($return) => $return->deleted_at ? $return->deleted_at->format('j M y H:i') : '-')
            ->addColumn('products', function ($row) {
                return $row->items->map(function ($item) {
                    return [
                        'name'  => $item->product?->name ?? '-',
                        'sku'   => $item->product?->sku ?? '-',
                        'qty'   => $item->quantity,
                        'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 0, ',', '.')
                    ];
                })->toArray();
            })
            ->addColumn('delete_notes', fn($return) => $return->delete_notes ?? '-')
            ->addColumn('deleted_by', fn($return) => $return->deletedByUser->name ?? '-')
            ->addColumn('action', function ($return) {
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    return '
                        <div class="d-flex gap-2">
                            <button type="button" 
                                class="btn btn-success btn-sm me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRestoreOrder"
                                data-id="' . $return->id . '" 
                                data-name="' . $return->purchase_number . '"
                                data-url="' . route('purchase-returns.restore', $return->id) . '">
                                    Restore
                            </button>
                            <button type="button" 
                                class="btn btn-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modalForceDeleteOrder"
                                data-id="' . $return->id . '" 
                                data-name="' . $return->purchase_number . '"
                                data-url="' . route('purchase-returns.forceDelete', $return->id) . '">
                                    Hapus Permanen
                            </button>
                        </div>
                    ';
                }

                return ''; // kalau bukan Owner → kosong
            })
            ->rawColumns(['purchase_number', 'grand_total', 'action', 'products'])
            ->make(true);
    }

    public function create($id)
    {
        $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

        if (!$purchase->hasStockIn()) {
            return redirect()->back()->with('error', 'Tidak bisa membuat Purchase Return karena barang belum masuk ke warehouse.');
        }

        $products = Products::orderBy('name', 'asc')->get();

        // Hitung sisa qty return per item
        $remainingItems = $purchase->purchaseItems->map(function ($item) use ($purchase) {
            $returnedQty = PurchaseReturnItem::where('product_id', $item->product_id)
                ->whereHas('purchaseReturn', function ($q) use ($purchase) {
                    $q->where('purchase_id', $purchase->id);
                })->sum('quantity');

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

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'purchase_id'             => 'required|exists:purchases,id',
    //         'purchase_number'         => 'required|string',
    //         'return_date'             => 'required|date',
    //         'status'                  => 'required|string',
    //         'suppliers'               => 'required|exists:suppliers,id',
    //         'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
    //         'product'                 => 'required|array',
    //         'product.*'               => 'exists:products,id',
    //         'qty'                     => 'required|array',
    //         'qty.*'                   => 'numeric|min:1',
    //         'price'                   => 'required|array',
    //         'price.*'                 => 'numeric|min:0',
    //         'freight'                 => 'required|array',
    //         'freight.*'               => 'numeric|min:0',
    //         'total'                   => 'required|array',
    //         'total.*'                 => 'numeric|min:0',
    //         'sub_total'               => 'required|numeric|min:0',
    //         'total_amount'            => 'required|numeric|min:0',
    //         // optional:
    //         // 'paid_amount'          => 'nullable|numeric|min:0',
    //         // 'transaction_type'     => 'required|exists:accounts,id',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $groupId      = Str::uuid();
    //         $totalAmount  = array_sum($request->total);
    //         $paidAmount   = $request->paid_amount ?? 0;
    //         $remaining    = $totalAmount - $paidAmount;

    //         $paymentStatus = $paidAmount <= 0
    //             ? 'Unpaid'
    //             : ($paidAmount < $totalAmount ? 'Partially Paid' : 'Paid');

    //         // 1) Header Purchase Return
    //         $purchaseReturn = PurchaseReturn::create([
    //             'purchase_id'          => $request->purchase_id,
    //             'supplier_id'          => $request->suppliers,
    //             'purchase_number'      => $request->purchase_number,
    //             'return_date'          => $request->return_date,
    //             'status'               => $request->status,
    //             'account'              => 'Purchase Return',
    //             'payment_status'       => $paymentStatus,
    //             'total_amount'         => $totalAmount,
    //             'refund_amount'        => $paidAmount,
    //             'remaining_amount'     => $remaining,
    //             'transaction_type'     => $request->transaction_type,
    //             'transaction_group_id' => $groupId,
    //             'note'                 => $request->note ?? null,
    //             'reason'               => $request->reason ?? null,
    //         ]);

    //         // 2) Inventory header (Stock Out)
    //         $inventory = Inventory::create([
    //             'purchase_return_id' => $purchaseReturn->id,
    //             'purchase_number'    => $purchaseReturn->purchase_number,
    //             'date'               => $purchaseReturn->return_date,
    //             'status'             => 'Stock Out',
    //             'note'               => 'Purchase Returns',
    //         ]);

    //         // 3) Items + InventoryItems (PASTIKAN STOCK_OUT = QTY)
    //         $touchedProductIds = [];
    //         foreach ($request->product as $i => $productId) {
    //             $qty   = (int) $request->qty[$i];
    //             if ($qty <= 0) continue;

    //             $price = (float) $request->price[$i];
    //             $freight = (float) $request->freight[$i];
    //             $total = (float) $request->total[$i];

    //             $item = PurchaseReturnItem::create([
    //                 'purchase_return_id'     => $purchaseReturn->id,
    //                 'product_id'             => $productId,
    //                 'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                 'purchase_item_id'       => $request->purchase_item_ids[$i] ?? null,
    //                 'status'                 => 'Purchase Return',
    //                 'quantity'               => $qty,
    //                 'price'                  => $price,
    //                 'freight'                => $freight,
    //                 'total'                  => $total,
    //             ]);

    //             // Catat sebagai STOCK OUT (ini yang bikin inventory_stock & stock_after_sales turun)
    //             InventoryItem::create([
    //                 'inventory_id'            => $inventory->id,
    //                 'product_id'              => $productId,
    //                 'inventory_warehouse_id'  => $request->inventory_warehouse_id,
    //                 'purchase_return_item_id' => $item->id,
    //                 'quantity'                => $qty,
    //                 'price'                   => $price,
    //                 'stock_in'                => 0,
    //                 'stock_out'               => 0,       // <-- penting
    //                 'remaining_stock_in'      => $qty,          // <-- jangan diisi qty
    //             ]);

    //             $touchedProductIds[] = $productId;
    //         }

    //         // 4) Recalc cost & stock (akan update tabel inventory_stocks termasuk inventory_stock & stock_after_sales => turun)
    //         $touchedProductIds = array_unique($touchedProductIds);
    //         foreach ($touchedProductIds as $pid) {
    //             if ($product = Products::find($pid)) {
    //                 ProductCostService::updateCostAndStock($product);
    //             }
    //         }

    //         // 5) Accounting (contoh: kredit ke akun hutang/akun yang dipilih)
    //         if (!empty($request->transaction_type)) {
    //             $purchaseAccount = Account::findOrFail($request->transaction_type);

    //             AccountTransaction::create([
    //                 'purchase_return_id'   => $purchaseReturn->id,
    //                 'purchase_number'      => $purchaseReturn->purchase_number,
    //                 'transaction_date'     => $request->return_date,
    //                 'account_id'           => $purchaseAccount->id,
    //                 'credit'               => $totalAmount,  // hutang berkurang
    //                 'debit'                => 0,
    //                 'note'                 => $request->note ?? '',
    //                 'particular'           => 'Purchase Return',
    //                 'transaction_group_id' => $groupId,
    //             ]);

    //             // Sesuaikan kebijakanmu soal closing_balance:
    //             // kalau akun hutang tipe "liability", "credit" menambah saldo. Di sistemmu sebelumnya di-increment.
    //             $purchaseAccount->increment('closing_balance', $totalAmount);
    //         }

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-returns')->with('success', 'Purchase return berhasil disimpan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase Return Store Failed', [
    //             'message' => $e->getMessage(),
    //             'line'    => $e->getLine(),
    //             'file'    => $e->getFile(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);
    //         return redirect()->back()->with('error', 'Gagal menyimpan purchase return: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_id'             => 'required|exists:purchases,id',
            'purchase_number'         => 'required|string',
            'return_date'             => 'required|date',
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

        $expandedItems = collect();

        foreach ($purchase->purchaseItems as $item) {
            // Qty yang sudah direturn kecuali purchaseReturn ini
            $returnedQty = PurchaseReturnItem::where('purchase_item_id', $item->id)
                ->where('purchase_return_id', '!=', $purchaseReturn->id)
                ->sum('quantity');

            // hitung sisa qty
            $item->remaining_qty = max(0, $item->quantity - $returnedQty);

            // cek apakah sudah ada di purchaseReturn ini
            $existingItem = $purchaseReturn->items->where('purchase_item_id', $item->id)->first();

            $item->return_qty   = $existingItem->quantity ?? 0;
            $item->return_price = $existingItem->price ?? $item->product->price;

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

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'return_date'             => 'required|date',
    //         'suppliers'               => 'required|exists:suppliers,id',
    //         'purchase_number'         => 'required|string',
    //         'status'                  => 'required|string',
    //         'notes'                   => 'nullable|string',
    //         'inventory_warehouse_id'  => 'required|exists:inventory_warehouses,id',
    //         'product'                 => 'required|array',
    //         'product.*'               => 'exists:products,id',
    //         'qty'                     => 'required|array',
    //         'qty.*'                   => 'numeric|min:1',
    //         'price'                   => 'required|array',
    //         'price.*'                 => 'numeric|min:0',
    //         'freight'                 => 'required|array',
    //         'freight.*'               => 'numeric|min:0',
    //         'total'                   => 'required|array',
    //         'total.*'                 => 'numeric|min:0',
    //         'sub_total'               => 'required|numeric|min:0',
    //         'total_amount'            => 'required|numeric|min:0',
    //         'image'                   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'note'                    => 'nullable|string',
    //         'edit_note'               => 'required|string|max:500',
    //         // optional:
    //         // 'purchase_item_ids'     => 'nullable|array',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $purchaseReturn = PurchaseReturn::with(['items', 'accountTransactions'])->findOrFail($id);

    //         // 🚫 Blokir kalau sudah ada stock out
    //         if ($purchaseReturn->hasStockOut()) {
    //             DB::rollBack();
    //             return back()->with('error', 'Purchase Return ini sudah memiliki Stock Out dan tidak bisa diedit lagi.');
    //         }

    //         // ===== 1) SNAPSHOT LAMA
    //         $oldReturn = $purchaseReturn->only([
    //             'purchase_number',
    //             'return_date',
    //             'supplier_id',
    //             'status',
    //             'total_amount',
    //             'refund_amount',
    //             'remaining_amount',
    //             'payment_status'
    //         ]);
    //         $oldItems = $purchaseReturn->items->mapWithKeys(fn($i) => [
    //             $i->product_id => [
    //                 'product'  => $i->product_name,
    //                 'quantity' => $i->quantity,
    //                 'price'    => $i->price,
    //                 'total'    => $i->total,
    //             ]
    //         ]);

    //         $grandTotal      = array_sum($request->total);
    //         $paidAmount      = $request->refund_amount ?? $purchaseReturn->refund_amount ?? 0;
    //         $remainingAmount = $grandTotal - $paidAmount;
    //         $paymentStatus   = $paidAmount <= 0 ? 'Unpaid' : ($paidAmount < $grandTotal ? 'Partially Paid' : 'Refunded');

    //         // === Handle image (opsional)
    //         if ($request->hasFile('image')) {
    //             if ($purchaseReturn->image && file_exists(public_path('storage/' . $purchaseReturn->image))) {
    //                 @unlink(public_path('storage/' . $purchaseReturn->image));
    //             }
    //             $image    = $request->file('image');
    //             $filename = time() . '_' . $image->getClientOriginalName();
    //             $image->move(public_path('storage/uploads/purchase_returns'), $filename);
    //             $purchaseReturn->image = 'uploads/purchase_returns/' . $filename;
    //         }

    //         // Update header PurchaseReturn
    //         $purchaseReturn->update([
    //             'purchase_number'  => $request->purchase_number,
    //             'return_date'      => $request->return_date,
    //             'supplier_id'      => $request->suppliers,
    //             'payment_status'   => $paymentStatus,
    //             'total_amount'     => $grandTotal,
    //             'refund_amount'    => $paidAmount,
    //             'remaining_amount' => $remainingAmount,
    //             'status'           => $request->status,
    //             'note'             => $request->notes,
    //         ]);

    //         // Pastikan ada transaction_group_id
    //         if (empty($purchaseReturn->transaction_group_id)) {
    //             $purchaseReturn->transaction_group_id = \Illuminate\Support\Str::uuid();
    //             $purchaseReturn->save();
    //         }

    //         // ===== 2) INVENTORY HEADER (BUAT/UPDATE DULU)
    //         $inventory = Inventory::where('purchase_return_id', $purchaseReturn->id)->first();
    //         if ($inventory) {
    //             $inventory->update([
    //                 'purchase_number' => $request->purchase_number,
    //                 'date'            => $request->return_date,
    //                 'status'          => 'Stock Out',
    //                 'note'            => 'Purchase Returns',
    //             ]);
    //         } else {
    //             $inventory = Inventory::create([
    //                 'purchase_return_id' => $purchaseReturn->id,
    //                 'purchase_number'    => $request->purchase_number,
    //                 'date'               => $request->return_date,
    //                 'status'             => 'Stock Out',
    //                 'note'               => 'Purchase Returns',
    //             ]);
    //         }

    //         // ===== 3) ITEMS (DELTA-BASED) + INVENTORY ITEMS (STOCK OUT = QTY)
    //         $existingItems      = $purchaseReturn->items->keyBy('product_id');
    //         $requestKeys        = [];
    //         $touchedProductIds  = [];

    //         foreach ($request->product as $idx => $productId) {
    //             $qty = (int) $request->qty[$idx];
    //             if ($qty <= 0) continue;

    //             $price    = (float) $request->price[$idx];
    //             $freight  = (float) $request->freight[$idx];
    //             $subtotal = (float) ($request->total[$idx] ?? ($qty * $price));
    //             $product  = Products::findOrFail($productId);

    //             $requestKeys[]       = $productId;
    //             $touchedProductIds[] = $productId;

    //             if ($existingItems->has($productId)) {
    //                 // UPDATE ITEM LAMA
    //                 $item = $existingItems[$productId];
    //                 $item->update([
    //                     'quantity'               => $qty,
    //                     'price'                  => $price,
    //                     'freight'                => $freight,
    //                     'total'                  => $subtotal,
    //                     // kalau mau dukung pindah gudang
    //                     'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                     // optional kalau purchase_item_ids berubah:
    //                     'purchase_item_id'       => $request->purchase_item_ids[$idx] ?? $item->purchase_item_id,
    //                 ]);

    //                 // Upsert InventoryItem (PASTI stock_out = qty agar stok berkurang)
    //                 InventoryItem::updateOrCreate(
    //                     ['purchase_return_item_id' => $item->id],
    //                     [
    //                         'inventory_id'            => $inventory->id,
    //                         'product_id'              => $productId,
    //                         'inventory_warehouse_id'  => $request->inventory_warehouse_id,
    //                         'quantity'                => $qty,
    //                         'price'                   => $price,
    //                         'stock_in'                => 0,
    //                         'stock_out'               => 0,   // <-- kunci decrement
    //                         'remaining_stock_in'      => $qty,
    //                     ]
    //                 );
    //             } else {
    //                 // INSERT ITEM BARU
    //                 $newItem = PurchaseReturnItem::create([
    //                     'purchase_return_id'     => $purchaseReturn->id,
    //                     'product_id'             => $productId,
    //                     'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                     'purchase_item_id'       => $request->purchase_item_ids[$idx] ?? null,
    //                     'product_name'           => $product->name,
    //                     'status'                 => 'Purchase Return',
    //                     'quantity'               => $qty,
    //                     'price'                  => $price,
    //                     'freight'                => $freight,
    //                     'total'                  => $subtotal,
    //                 ]);

    //                 // InventoryItem: STOCK OUT = QTY
    //                 InventoryItem::create([
    //                     'inventory_id'            => $inventory->id,
    //                     'product_id'              => $productId,
    //                     'inventory_warehouse_id'  => $newItem->inventory_warehouse_id,
    //                     'purchase_return_item_id' => $newItem->id,
    //                     'quantity'                => $qty,
    //                     'price'                   => $price,
    //                     'stock_in'                => 0,
    //                     'stock_out'               => $qty,   // <-- kunci decrement
    //                     'remaining_stock_in'      => 0,
    //                 ]);
    //             }
    //         }

    //         // HAPUS ITEM YANG TIDAK ADA DI REQUEST
    //         foreach ($existingItems as $pid => $item) {
    //             if (!in_array($pid, $requestKeys)) {
    //                 $touchedProductIds[] = $pid;
    //                 InventoryItem::where('purchase_return_item_id', $item->id)->delete();
    //                 $item->delete();
    //             }
    //         }

    //         // ===== 4) SNAPSHOT BARU + DIFF
    //         $purchaseReturn->load('items');
    //         $newReturn = $purchaseReturn->only([
    //             'purchase_number',
    //             'return_date',
    //             'supplier_id',
    //             'status',
    //             'total_amount',
    //             'refund_amount',
    //             'remaining_amount',
    //             'payment_status'
    //         ]);
    //         $newItems = $purchaseReturn->items->mapWithKeys(fn($i) => [
    //             $i->product_id => [
    //                 'product'  => $i->product_name,
    //                 'quantity' => $i->quantity,
    //                 'price'    => $i->price,
    //                 'total'    => $i->total,
    //             ]
    //         ]);

    //         $returnDiff = ['old' => [], 'new' => []];
    //         foreach ($newReturn as $field => $newVal) {
    //             $oldVal = $oldReturn[$field] ?? null;
    //             if ($oldVal != $newVal) {
    //                 $returnDiff['old'][$field] = $oldVal;
    //                 $returnDiff['new'][$field] = $newVal;
    //             }
    //         }

    //         $itemsDiff = [];
    //         $allKeys = array_unique(array_merge(array_keys($oldItems->toArray()), array_keys($newItems->toArray())));
    //         foreach ($allKeys as $pid) {
    //             $old = $oldItems[$pid] ?? null;
    //             $new = $newItems[$pid] ?? null;

    //             if ($old && !$new) {
    //                 $itemsDiff[] = ['product' => $old['product'], 'action' => 'removed'];
    //             } elseif (!$old && $new) {
    //                 $itemsDiff[] = ['product' => $new['product'], 'action' => 'added'];
    //             } elseif ($old && $new) {
    //                 $changed = [];
    //                 foreach (['quantity', 'price', 'total'] as $f) {
    //                     if ($old[$f] != $new[$f]) {
    //                         $changed[$f] = ['old' => $old[$f], 'new' => $new[$f]];
    //                     }
    //                 }
    //                 if ($changed) {
    //                     $itemsDiff[] = ['product' => $new['product'], 'action' => 'updated', 'fields' => $changed];
    //                 }
    //             }
    //         }

    //         $changes = ['purchase_return' => $returnDiff, 'items' => $itemsDiff];

    //         // ===== 5) RECALC COST & STOCK (decrement terjadi karena stock_out di atas)
    //         $touchedProductIds = array_values(array_unique($touchedProductIds));
    //         foreach ($touchedProductIds as $productId) {
    //             if ($product = Products::find($productId)) {
    //                 ProductCostService::updateCostAndStock($product);
    //             }
    //         }

    //         // ===== 6) ACCOUNT TRANSACTIONS (akun type: Purchase Return)
    //         $purchaseReturnAccount = Account::where('type', 'Purchase Return')->firstOrFail();
    //         $existingTx = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
    //             ->where('account_id', $purchaseReturnAccount->id)
    //             ->where('credit', '>', 0)
    //             ->first();

    //         if (!$existingTx) {
    //             AccountTransaction::create([
    //                 'purchase_return_id'   => $purchaseReturn->id,
    //                 'purchase_number'      => $request->purchase_number,
    //                 'transaction_date'     => $request->return_date,
    //                 'account_id'           => $purchaseReturnAccount->id,
    //                 'credit'               => $grandTotal,
    //                 'debit'                => 0,
    //                 'note'                 => $request->note ?? '',
    //                 'particular'           => 'Purchase Return',
    //                 'transaction_group_id' => $purchaseReturn->transaction_group_id,
    //             ]);
    //             $purchaseReturnAccount->increment('closing_balance', $grandTotal);
    //         } else {
    //             $diff = $grandTotal - $existingTx->credit;
    //             $existingTx->update([
    //                 'transaction_date' => $request->return_date,
    //                 'credit'           => $grandTotal,
    //                 'note'             => $request->note ?? '',
    //             ]);
    //             if ($diff != 0) {
    //                 $purchaseReturnAccount->increment('closing_balance', $diff);
    //             }
    //         }

    //         // ===== 7) SAVE EDIT HISTORY
    //         PurchaseReturnEditHistory::create([
    //             'purchase_return_id' => $purchaseReturn->id,
    //             'edited_by'          => Auth::id(),
    //             'changes'            => $changes,
    //             'text'               => $request->edit_note,
    //             'edited_at'          => now(),
    //         ]);

    //         $purchaseReturn->update([
    //             'status_edited' => true,
    //         ]);

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-returns')->with('success', 'Purchase Return updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase Return Update Error', [
    //             'message' => $e->getMessage(),
    //             'line'    => $e->getLine(),
    //             'file'    => $e->getFile(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);
    //         return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'return_date'             => 'required|date',
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
                'note'                   => $request->notes,
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
                return back()->with('error', 'Purchase Return ini sudah memiliki Stock Out dan tidak bisa dihapus.');
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

            // 🔁 Update avg cost per produk
            foreach ($productIds as $productId) {
                $product = Products::find($productId);
                if ($product) {
                    ProductCostService::updateCostAndStock($product);
                    $product->stock_after_sales = $product->inventory_stock;
                    $product->save();
                }
            }

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
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::findOrFail($request->purchase_return_id);
            $groupId = $purchaseReturn->transaction_group_id ?? Str::uuid();

            $purchaseReturnAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // 1️⃣ Cash/Bank → DEBIT (uang keluar untuk refund)
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => $request->refund_amount,
                'credit'                => 0,
                'note'                  => $request->note ?? '',
                'particular'            => 'Refund Product - ' . $purchaseReturnAccount->name,
                'transaction_group_id'  => $groupId,
            ]);
            $cashBankAccount->decrement('closing_balance', $request->refund_amount);

            // 2️⃣ Purchase Return Account → CREDIT
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $purchaseReturnAccount->id,
                'debit'                 => 0,
                'credit'                => $request->refund_amount,
                'note'                  => $request->note ?? '',
                'particular'            => 'Refund Product - ' . $cashBankAccount->name,
                'transaction_group_id'  => $groupId,
            ]);
            $purchaseReturnAccount->decrement('closing_balance', $request->refund_amount);

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
            $purchaseReturn->save();

            DB::commit();
            return back()->with('success', 'Refund produk berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
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
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::findOrFail($request->purchase_return_id);
            $groupId = $purchaseReturn->transaction_group_id ?? Str::uuid();

            $purchaseReturnAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // 1️⃣ Cash/Bank → DEBIT (uang keluar)
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => $request->refund_amount,
                'credit'                => 0,
                'note'                  => $request->note ?? '',
                'particular'            => 'Refund Freight - ' . $purchaseReturnAccount->name,
                'transaction_group_id'  => $groupId,
            ]);
            $cashBankAccount->decrement('closing_balance', $request->refund_amount);

            // 2️⃣ Purchase Return Account → CREDIT
            AccountTransaction::create([
                'purchase_return_id'    => $purchaseReturn->id,
                'purchase_number'       => $purchaseReturn->purchase_number,
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $purchaseReturnAccount->id,
                'debit'                 => 0,
                'credit'                => $request->refund_amount,
                'note'                  => $request->note ?? '',
                'particular'            => 'Refund Freight - ' . $cashBankAccount->name,
                'transaction_group_id'  => $groupId,
            ]);
            $purchaseReturnAccount->decrement('closing_balance', $request->refund_amount);

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
            $purchaseReturn->save();

            DB::commit();
            return back()->with('success', 'Refund freight berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan refund freight: ' . $e->getMessage());
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
            'paid_amount'           => 'required|numeric|min:1',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Refund not found");
            }

            $purchaseReturnId = $transactions->first()->purchase_return_id;
            $purchaseReturn   = PurchaseReturn::findOrFail($purchaseReturnId);

            // cari transaksi debit lama (Cash/Bank masuk)
            $oldDebit = $transactions->firstWhere('debit', '>', 0);
            if (!$oldDebit) {
                throw new \Exception("Debit transaction (Cash/Bank) not found in this group");
            }

            $oldAccount = $oldDebit->account;
            $oldAmount  = $oldDebit->debit;

            // rollback saldo akun lama (Cash/Bank)
            $oldAccount->closing_balance -= $oldAmount;
            $oldAccount->save();

            // update transaksi debit lama → ganti akun/amount/date/note
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldDebit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => $request->paid_amount,
                'note'             => $request->note ?? '',
            ]);

            // update saldo akun baru (Cash/Bank)
            $cashBankAccount->closing_balance += $request->paid_amount;
            $cashBankAccount->save();

            // update juga baris kredit Purchase Return biar sinkron
            $returnTrx = $transactions->firstWhere('credit', '>', 0);
            if ($returnTrx) {
                $returnTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note'             => $request->note ?? '',
                ]);
            }

            // hitung ulang total refund
            $totalRefund = AccountTransaction::where('purchase_return_id', $purchaseReturn->id)
                ->where('debit', '>', 0) // ambil refund Cash/Bank masuk
                ->sum('debit');

            $purchaseReturn->refund_amount     = $totalRefund;
            $purchaseReturn->remaining_amount  = max(0, $purchaseReturn->total_amount - $totalRefund);

            if ($purchaseReturn->refund_amount == 0) {
                $purchaseReturn->payment_status = 'Unpaid';
            } elseif ($purchaseReturn->refund_amount < $purchaseReturn->total_amount) {
                $purchaseReturn->payment_status = 'Partially Refunded';
            } elseif ($purchaseReturn->refund_amount == $purchaseReturn->total_amount) {
                $purchaseReturn->payment_status = 'Refunded';
            } else {
                $purchaseReturn->payment_status = 'Over Refunded';
            }

            $purchaseReturn->save();

            DB::commit();
            return redirect()->back()->with('success', 'Refund berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update refund: ' . $e->getMessage());
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
