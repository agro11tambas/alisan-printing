<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Products;
use App\Models\PurchaseEditHistory;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ProductCostService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class PurchaseListController extends Controller
{
    public function getPurchaseList()
    {
        $purchase_number = Purchase::first();
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-list.purchase-list', compact('purchase_number', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function dataPurchaseList(Request $request)
    {
        $purchases = Purchase::with('supplier')
            ->where('status', 'Purchase List');

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

        if ($request->search_type === 'payment_status' && $request->filled('payment_status')) {
            if ($request->payment_status === 'Paid') {
                $purchases->whereIn('payment_status', ['Paid', 'Overpaid']);
            } else {
                $purchases->where('payment_status', $request->payment_status);
            }
        } elseif ($request->search_type === 'due_date') {
            $direction = strtolower($request->due_date_order ?? 'asc');

            if ($direction === 'asc') {
                $purchases->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('due_date', 'asc');
            } else {
                $purchases->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                    ->orderBy('due_date', 'desc');
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
                $date = Carbon::parse($purchase->purchase_date)->format('j M y');
                $dueDate = $purchase->due_date ? Carbon::parse($purchase->due_date)->format('j M y') : '-';

                $editedBadge = $purchase->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                // 🔎 Tambahkan badge kalau ada purchase return
                $returnBadge = $purchase->purchaseReturn()->exists()
                    ? '<div><span class="badge bg-soft-danger text-danger mb-1">Has Purchase Return</span></div>'
                    : '';

                return $returnBadge . '
                    <div>
                        <div>' . e($purchase->purchase_number) . $editedBadge . '</div>
                        <small class="text-muted">' . $date . '</small>,
                        <small class="text-danger">Due: ' . $dueDate . '</small>
                    </div>';
            })
            ->addColumn('purchase_date', function ($purchase) {
                return $purchase->purchase_date;
            })
            ->addColumn('supplier', function ($purchase) {
                return $purchase->supplier->name;
            })
            ->addColumn('total_amount', function ($purchase) {
                return 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
            })
            ->addColumn('paid_amount', function ($purchase) {
                $paidTotal = ($purchase->paid_amount_product ?? 0) + ($purchase->paid_amount_freight ?? 0);
                return '<span class="text-success">Rp ' . number_format($paidTotal, 0, ',', '.') . '</span>';
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
            ->addColumn('payment_method', function ($purchase) {
                return $purchase->payment_method;
            })
            ->addColumn('products', function ($purchase) {
                return $purchase->purchaseItems->map(function ($item) {
                    return [
                        'name'  => $item->purchaseProduct ? $item->purchaseProduct->name : '-',
                        'sku'   => $item->purchaseProduct ? $item->purchaseProduct->sku : '-',
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
                $purchase->is_fully_returned = $purchase->purchaseItems->every(function ($item) use ($purchase) {
                    $returnedQty = \App\Models\PurchaseReturnItem::where('product_id', $item->product_id)
                        ->whereHas('purchaseReturn', function ($q) use ($purchase) {
                            $q->where('purchase_id', $purchase->id);
                        })->sum('quantity');
                    return $returnedQty >= $item->quantity;
                });

                return view('erp.pages.purchases.purchase-list.partials.action-button', compact('purchase'))->render();
            })
            ->rawColumns(['purchase_number', 'purchase_date', 'supplier', 'total_amount', 'paid_amount', 'remaining_amount', 'payment_status', 'action', 'products'])
            ->make(true);
    }

    public function dataDeletedPurchaseList(Request $request)
    {
        $purchases = Purchase::onlyTrashed()
            ->with(['supplier', 'purchaseItems.purchaseProduct'])
            ->where('status', 'Purchase List')
            ->latest()
            ->get();

        return DataTables::of($purchases)
            ->addIndexColumn()
            ->addColumn('purchase_number', function ($purchase) {
                $date = Carbon::parse($purchase->purchase_date)->format('j M y');
                return '<div>
                <div>' . $purchase->purchase_number . '</div>
                <small class="text-muted">' . $date . '</small>
            </div>';
            })
            ->addColumn('supplier', fn($purchase) => $purchase->supplier->name ?? '-')
            ->addColumn('grand_total', fn($purchase) => '<span class="text-primary">Rp ' . number_format($purchase->grand_total, 0, ',', '.') . '</span>')
            ->addColumn('deleted_at', fn($purchase) => $purchase->deleted_at ? $purchase->deleted_at->format('j M y H:i') : '-')
            ->addColumn('products', function ($row) {
                return $row->purchaseItems->map(function ($item) {
                    return [
                        'name'  => $item->purchaseProduct?->name ?? '-',
                        'sku'   => $item->purchaseProduct?->sku ?? '-',
                        'qty'   => $item->quantity,
                        'price' => number_format($item->price ?? 0, 0, ',', '.'),
                        'freight' => number_format($item->freight ?? 0, 0, ',', '.')
                    ];
                })->toArray();
            })
            ->addColumn('delete_notes', fn($purchase) => $purchase->delete_notes ?? '-')
            ->addColumn('deleted_by', fn($purchase) => $purchase->deletedByUser->name ?? '-')
            ->addColumn('action', function ($purchase) {
                if (Auth::check() && Auth::user()->role === 'Owner') {
                    return '
                    <div class="d-flex gap-2">
                        <button type="button" 
                            class="btn btn-success btn-sm me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRestoreOrder"
                            data-id="' . $purchase->id . '" 
                            data-name="' . $purchase->purchase_number . '"
                            data-url="' . route('purchases.restore', $purchase->id) . '">
                                Restore
                        </button>
                        <button type="button" 
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalForceDeleteOrder"
                            data-id="' . $purchase->id . '" 
                            data-name="' . $purchase->purchase_number . '"
                            data-url="' . route('purchases.forceDelete', $purchase->id) . '">
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

    public function create()
    {
        $products = Products::all();
        $suppliers = Supplier::all();

        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-list.create-purchase', compact('products', 'suppliers', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    // public function store(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'purchase_number'   => 'required|string|unique:purchases,purchase_number',
    //         'purchase_date'     => 'required|date',
    //         'due_date_option'   => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
    //         'custom_due_date'   => 'nullable|date',
    //         'suppliers' => 'required|exists:suppliers,id',
    //         'product'           => 'required|array',
    //         'product.*'         => 'exists:products,id',
    //         'qty'               => 'required|array',
    //         'qty.*'             => 'numeric|min:1',
    //         'price'             => 'required|array',
    //         'price.*'           => 'numeric|min:0',
    //         'freight'           => 'required|array',
    //         'freight.*'         => 'numeric|min:0',
    //         'total'             => 'required|array',
    //         'total.*'           => 'numeric|min:0',
    //         'sub_total'         => 'required|numeric|min:0',
    //         'tax_percent'       => 'nullable|numeric|min:0',
    //         'tax_amount'        => 'nullable|numeric|min:0',
    //         'total_amount'      => 'required|numeric|min:0',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $calculatedTotalAmount = array_sum($request->total);
    //         $taxAmount = $request->tax_amount ?? 0;

    //         $grandTotal = $calculatedTotalAmount + $taxAmount;

    //         $paidAmount = $request->payment_status === 'Paid' ? $calculatedTotalAmount : $request->paid_amount;
    //         $remainingAmount = $grandTotal - $paidAmount;

    //         $paidAmount = 0;

    //         $status = 'Purchase List';
    //         $paymentStatus = 'Unpaid';

    //         $purchaseDate = Carbon::parse($request->purchase_date);

    //         // ====== HITUNG DUE DATE ======
    //         $dueDate = null;
    //         switch ($request->due_date_option) {
    //             case 'today':
    //                 $dueDate = $purchaseDate;
    //                 break;
    //             case '1_week':
    //                 $dueDate = $purchaseDate->copy()->addWeek();
    //                 break;
    //             case '1_month':
    //                 $dueDate = $purchaseDate->copy()->addMonth();
    //                 break;
    //             case '3_months':
    //                 $dueDate = $purchaseDate->copy()->addMonths(3);
    //                 break;
    //             case 'custom':
    //                 $dueDate = $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null;
    //                 break;
    //             default:
    //                 $dueDate = null; // none
    //         }

    //         $purchase = Purchase::create([
    //             'purchase_number' => $request->purchase_number,
    //             'purchase_date'   => $request->purchase_date,
    //             'due_date'        => $dueDate,
    //             'supplier_id'     => $request->suppliers,
    //             'payment_status'  => $paymentStatus,
    //             'paid_amount'     => $paidAmount,
    //             'sub_total'        => $request->sub_total,
    //             'tax_percent'      => $request->tax_percent,
    //             'tax_amount'       => $taxAmount,
    //             'total_amount'    => $grandTotal,
    //             'remaining_amount' => $remainingAmount,
    //             'status'          => $status,
    //         ]);

    //         foreach ($request->product as $index => $productId) {
    //             $qty   = $request->qty[$index];
    //             $price = $request->price[$index];
    //             $freight = $request->freight[$index];
    //             $total = $request->total[$index];

    //             $product = Products::findOrFail($productId);

    //             // Simpan PurchaseItem dan ambil instance-nya
    //             $purchaseItem = PurchaseItem::create([
    //                 'purchase_id'   => $purchase->id,
    //                 'product_id'    => $productId,
    //                 'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                 'product_name'  => $product->name,
    //                 'status'        => 'Purchase Account',
    //                 'quantity'      => $qty,
    //                 'price'         => $price,
    //                 'freight'       => $freight,
    //                 'subtotal'      => $total,
    //             ]);

    //             // ProductCostService::updateCostAndStock($product);

    //             // Kalau status Purchase List → bikin InventoryItem sekaligus
    //             if ($purchase->status === 'Purchase List') {
    //                 $inventory = Inventory::firstOrCreate(
    //                     [
    //                         'purchase_id' => $purchase->id,
    //                     ],
    //                     [
    //                         'purchase_number' => $purchase->purchase_number,
    //                         'supplier_id'     => $purchase->supplier_id,
    //                         'date'            => $purchase->purchase_date,
    //                         'status'          => 'Stock In',
    //                         'note'            => 'Purchase Account',
    //                     ]
    //                 );

    //                 InventoryItem::create([
    //                     'inventory_id'       => $inventory->id,
    //                     'purchase_item_id'   => $purchaseItem->id, // ← foreign key ke purchase_items
    //                     'product_id'         => $productId,
    //                     'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                     'quantity'           => $qty,
    //                     'price'              => $price,
    //                     'stock_in'           => 0,
    //                     'remaining_stock_in' => $qty,
    //                     'stock_out'          => 0,
    //                 ]);

    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     [
    //                         'product_id' => $productId,
    //                         'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 2,
    //                     ],
    //                     [
    //                         'incoming_stock'    => 0,
    //                     ]
    //                 );

    //                 // update stok sesuai purchase
    //                 $inventoryStock->increment('incoming_stock', $qty);
    //             }
    //         }

    //         $groupId = Str::uuid();

    //         // ================== HANDLE ACCOUNT TRANSACTIONS ==================
    //         $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

    //         // Buat transaksi utama (debit ke purchase account)
    //         AccountTransaction::create([
    //             'purchase_id'        => $purchase->id,
    //             'purchase_number'    => $purchase->purchase_number,
    //             'transaction_date'   => $purchase->purchase_date,
    //             'account_id'         => $purchaseAccount->id,
    //             'debit'              => $purchase->total_amount,
    //             'credit'             => 0,
    //             'note'               => $request->note ?? '',
    //             'particular'         => 'Purchase Invoice',
    //             'transaction_group_id' => $groupId,
    //         ]);

    //         $purchaseAccount->increment('closing_balance', $purchase->total_amount);

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase order created successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase store failed: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Purchase order failed to create');
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_number'   => 'required|string|unique:purchases,purchase_number',
            'purchase_date'     => 'required|date',
            'due_date_option'   => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date'   => 'nullable|date',
            'suppliers'         => 'required|exists:suppliers,id',
            'product'           => 'required|array',
            'product.*'         => 'exists:products,id',
            'qty'               => 'required|array',
            'qty.*'             => 'numeric|min:1',
            'price'             => 'required|array',
            'price.*'           => 'numeric|min:0',
            'freight'           => 'required|array',
            'freight.*'         => 'numeric|min:0',
            'total'             => 'required|array',
            'total.*'           => 'numeric|min:0',
            'sub_total'         => 'required|numeric|min:0',
            'tax_percent'       => 'nullable|numeric|min:0',
            'tax_amount'        => 'nullable|numeric|min:0',
            'total_amount_product'  => 'required|numeric|min:0',
            'total_amount_freight'  => 'required|numeric|min:0',
            'total_amount'          => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $purchaseDate = Carbon::parse($request->purchase_date);

            // ====== DUE DATE ======
            $dueDate = match ($request->due_date_option) {
                'today'     => $purchaseDate,
                '1_week'    => $purchaseDate->copy()->addWeek(),
                '1_month'   => $purchaseDate->copy()->addMonth(),
                '3_months'  => $purchaseDate->copy()->addMonths(3),
                'custom'    => $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null,
                default     => null
            };

            $status = 'Purchase List';
            $paymentStatus = 'Unpaid';

            // =============== HITUNG TOTAL ===============
            $taxAmount        = $request->tax_amount ?? 0;
            $totalProduct     = $request->total_amount_product;
            $totalFreight     = $request->total_amount_freight;
            $grandTotal       = $totalProduct + $totalFreight;

            // otomatis semua unpaid
            $paidProduct       = 0;
            $remainingProduct  = $totalProduct;

            $paidFreight       = 0;
            $remainingFreight  = $totalFreight;

            $purchase = Purchase::create([
                'purchase_number'           => $request->purchase_number,
                'purchase_date'             => $purchaseDate,
                'due_date'                  => $dueDate,
                'supplier_id'               => $request->suppliers,
                'payment_status'            => $paymentStatus,
                'sub_total'                 => $request->sub_total,
                'tax_percent'               => $request->tax_percent,
                'tax_amount'                => $taxAmount,
                'freight_total'             => $totalFreight,

                // Produk
                'total_amount_product'      => $totalProduct,
                'paid_amount_product'       => $paidProduct,
                'remaining_amount_product'  => $remainingProduct,

                // Freight
                'total_amount_freight'      => $totalFreight,
                'paid_amount_freight'       => $paidFreight,
                'remaining_amount_freight'  => $remainingFreight,

                // Grand total untuk summary
                'total_amount'              => $grandTotal,
                'paid_amount'               => 0,
                'remaining_amount'          => $grandTotal,
                'status'                    => $status,
            ]);

            // =============== SIMPAN ITEM ===============
            foreach ($request->product as $index => $productId) {
                $qty     = $request->qty[$index];
                $price   = $request->price[$index];
                $freight = $request->freight[$index];
                $total   = $request->total[$index];

                $product = Products::findOrFail($productId);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'product_id'    => $productId,
                    'inventory_warehouse_id' => $request->inventory_warehouse_id,
                    'status'        => 'Purchase Account',
                    'quantity'      => $qty,
                    'price'         => $price,
                    'freight'       => $freight,
                    'subtotal'      => $total,
                ]);

                // Jika Purchase List → buat stock in
                if ($purchase->status === 'Purchase List') {
                    $inventory = Inventory::firstOrCreate(
                        ['purchase_id' => $purchase->id],
                        [
                            'purchase_number' => $purchase->purchase_number,
                            'supplier_id'     => $purchase->supplier_id,
                            'date'            => $purchase->purchase_date,
                            'status'          => 'Stock In',
                            'note'            => 'Purchase Account',
                        ]
                    );

                    InventoryItem::create([
                        'inventory_id'       => $inventory->id,
                        'purchase_item_id'   => $purchaseItem->id,
                        'product_id'         => $productId,
                        'inventory_warehouse_id' => $request->inventory_warehouse_id,
                        'quantity'           => $qty,
                        'price'              => $price,
                        'stock_in'           => 0,
                        'remaining_stock_in' => $qty,
                        'stock_out'          => 0,
                    ]);

                    $inventoryStock = InventoryStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $inventoryStock->increment('incoming_stock', $qty);
                }
            }

            // =============== ACCOUNT TRANSACTION ===============
            $groupId = Str::uuid();
            $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

            AccountTransaction::create([
                'purchase_id'          => $purchase->id,
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $purchase->purchase_date,
                'account_id'           => $purchaseAccount->id,
                'debit'                => $purchase->total_amount_product, // hanya product
                'credit'               => 0,
                'note'                 => 'Purchase Account Transaction',
                'particular'           => 'Purchase Invoice',
                'transaction_group_id' => $groupId,
            ]);

            $purchaseAccount->increment('closing_balance', $purchase->total_amount_product);

            DB::commit();
            return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase store failed: ' . $e->getMessage());
            return back()->with('error', 'Purchase order failed to create');
        }
    }

    public function edit($id)
    {
        $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

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

        $products = Products::all();
        $suppliers = Supplier::all();

        return view('erp.pages.purchases.purchase-list.edit-purchase', compact(
            'purchase',
            'products',
            'suppliers',
            'dueDateOption',
            'customDueDate'
        ));
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'purchase_date'   => 'required|date',
    //         'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
    //         'custom_due_date' => 'nullable|date',
    //         'suppliers'       => 'required|exists:suppliers,id',
    //         'purchase_number' => 'required|string|unique:purchases,purchase_number,' . $id,
    //         'status'          => 'required|string',
    //         'product'         => 'required|array',
    //         'product.*'       => 'exists:products,id',
    //         'qty'             => 'required|array',
    //         'qty.*'           => 'numeric|min:1',
    //         'price'           => 'required|array',
    //         'price.*'         => 'numeric|min:0',
    //         'freight'         => 'required|array',
    //         'freight.*'       => 'numeric|min:0',
    //         'total'           => 'required|array',
    //         'total.*'         => 'numeric|min:0',
    //         'sub_total'       => 'required|numeric|min:0',
    //         'tax_percent'     => 'nullable|numeric|min:0',
    //         'tax_amount'      => 'nullable|numeric|min:0',
    //         'total_amount'    => 'required|numeric|min:0',
    //         'note'            => 'nullable|string',
    //         'image'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'edit_note'       => 'required|string|max:500',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $purchase = Purchase::findOrFail($id);

    //         // 🚫 Cek apakah sudah ada Purchase Return
    //         if ($purchase->purchaseReturn()->exists()) {
    //             DB::rollBack();
    //             return back()->with('error', 'Purchase ini memiliki Purchase Return dan tidak bisa diedit lagi.');
    //         }

    //         // 🚫 Cek apakah sudah ada Stock In
    //         if ($purchase->hasStockIn()) {
    //             DB::rollBack();
    //             return back()->with('error', 'Purchase ini sudah memiliki Stock In dan tidak bisa diedit lagi.');
    //         }

    //         // ===== 1) SNAPSHOT LAMA
    //         $oldPurchase = $purchase->only(['purchase_number', 'due_date', 'purchase_date', 'supplier_id', 'status', 'total_amount', 'remaining_amount']);
    //         $oldItems = $purchase->purchaseItems->mapWithKeys(fn($i) => [
    //             $i->product_id => [
    //                 'product'  => $i->purchaseProduct->name,
    //                 'quantity' => $i->quantity,
    //                 'price'    => $i->price,
    //                 'subtotal' => $i->subtotal,
    //             ]
    //         ]);

    //         // ===== 2) UPDATE HEADER ORDER
    //         $orderDate = Carbon::parse($request->order_date);

    //         // 🔹 Tentukan due_date berdasarkan option
    //         $dueDate = null;
    //         switch ($request->due_date_option) {
    //             case 'today':
    //                 $dueDate = $orderDate;
    //                 break;
    //             case '1_week':
    //                 $dueDate = $orderDate->copy()->addWeek();
    //                 break;
    //             case '1_month':
    //                 $dueDate = $orderDate->copy()->addMonth();
    //                 break;
    //             case '3_months':
    //                 $dueDate = $orderDate->copy()->addMonths(3);
    //                 break;
    //             case 'custom':
    //                 $dueDate = $request->custom_due_date ? Carbon::parse($request->custom_due_date) : null;
    //                 break;
    //             default:
    //                 $dueDate = null; // none
    //         }

    //         $paidAmount       = $purchase->paid_amount ?? 0;
    //         $newTotalAmount   = $request->total_amount;
    //         $remainingAmount  = max(0, $newTotalAmount - $paidAmount);

    //         // Hanya update field tertentu
    //         $purchase->update([
    //             'purchase_number' => $request->purchase_number,
    //             'purchase_date'   => $request->purchase_date,
    //             'due_date'        => $dueDate,
    //             'supplier_id'     => $request->suppliers,
    //             'status'          => $request->status,
    //             'sub_total'        => $request->sub_total,
    //             'tax_percent'     => $request->tax_percent,
    //             'tax_amount'       => $request->tax_amount,
    //             'total_amount'    => $newTotalAmount,
    //             'remaining_amount' => $remainingAmount,
    //         ]);

    //         $purchase->load('purchaseItems');

    //         $existingItems = $purchase->purchaseItems->keyBy('product_id');
    //         $requestKeys   = [];

    //         foreach ($request->input('product', []) as $index => $productId) {
    //             $qty     = $request->qty[$index] ?? 0;
    //             $price   = $request->price[$index] ?? 0;
    //             $freight = $request->freight[$index] ?? 0;
    //             $total   = $request->total[$index] ?? 0;

    //             if (!$productId) {
    //                 continue;
    //             }

    //             $product = Products::findOrFail($productId);
    //             $requestKeys[] = $productId;

    //             // =========================
    //             // ✅ Update or Create Item
    //             // =========================
    //             $oldQty = 0;
    //             if ($existingItems->has($productId)) {
    //                 // item lama → ambil qty lama
    //                 $item = $existingItems[$productId];
    //                 $oldQty = $item->quantity;

    //                 $item->update([
    //                     'quantity' => $qty,
    //                     'price'    => $price,
    //                     'freight'  => $freight,
    //                     'subtotal' => $total,
    //                 ]);
    //             } else {
    //                 // item baru
    //                 PurchaseItem::create([
    //                     'purchase_id'             => $purchase->id,
    //                     'product_id'              => $productId,
    //                     'inventory_warehouse_id'  => $request->inventory_warehouse_id,
    //                     'status'                  => $purchase->payment_method ?? 'Purchase Account',
    //                     'product_name'            => $product->name,
    //                     'quantity'                => $qty,
    //                     'price'                   => $price,
    //                     'freight'                 => $freight,
    //                     'subtotal'                => $total,
    //                 ]);
    //             }

    //             // =========================
    //             // ✅ Update Incoming Stock
    //             // =========================
    //             $inventoryStock = InventoryStock::firstOrCreate(
    //                 [
    //                     'product_id'              => $productId,
    //                     'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 2,
    //                 ],
    //                 [
    //                     'incoming_stock' => 0,
    //                     'inventory_stock' => 0,
    //                 ]
    //             );

    //             // Hitung selisih quantity dari purchase ini
    //             $difference = $qty - $oldQty;

    //             // Update incoming stock berdasarkan selisih
    //             $newIncoming = max(0, $inventoryStock->incoming_stock + $difference);

    //             $inventoryStock->update([
    //                 'incoming_stock' => $newIncoming,
    //             ]);

    //             // =========================
    //             // ✅ Recalculate cost
    //             // =========================
    //             // ProductCostService::updateCostAndStock($product);
    //         }

    //         // ✅ Hapus item yang tidak ada di request
    //         foreach ($existingItems as $pid => $item) {
    //             if (!in_array($pid, $requestKeys)) {
    //                 $item->forceDelete();

    //                 // 🔹 reset inventory stock untuk produk ini
    //                 $inventoryStock = InventoryStock::where('product_id', $pid)->first();
    //                 if ($inventoryStock) {
    //                     $totalPurchasedQty = PurchaseItem::where('product_id', $pid)->sum('quantity');
    //                     $inventoryStock->update([
    //                         'incoming_stock'    => $totalPurchasedQty,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // ===== 4) SNAPSHOT BARU
    //         $purchase->load('purchaseItems');
    //         $newPurchase = $purchase->only(['purchase_number', 'due_date', 'purchase_date', 'supplier_id', 'status', 'total_amount', 'remaining_amount']);
    //         $newItems = $purchase->purchaseItems->mapWithKeys(fn($i) => [
    //             $i->product_id => [
    //                 'product'  => $i->purchaseProduct->name,
    //                 'quantity' => $i->quantity,
    //                 'price'    => $i->price,
    //                 'subtotal' => $i->subtotal,
    //             ]
    //         ]);

    //         // ===== 5) DIFF
    //         $purchaseDiff = ['old' => [], 'new' => []];
    //         foreach ($newPurchase as $field => $newVal) {
    //             $oldVal = $oldPurchase[$field] ?? null;

    //             // Kalau field date, format ke Y-m-d
    //             if (in_array($field, ['purchase_date', 'due_date'])) {
    //                 $oldVal = $oldVal ? \Carbon\Carbon::parse($oldVal)->format('Y-m-d') : null;
    //                 $newVal = $newVal ? \Carbon\Carbon::parse($newVal)->format('Y-m-d') : null;
    //             }

    //             if ($oldVal != $newVal) {
    //                 $purchaseDiff['old'][$field] = $oldVal;
    //                 $purchaseDiff['new'][$field] = $newVal;
    //             }
    //         }

    //         $itemsDiff = [];
    //         $allKeys = array_unique(array_merge(array_keys($oldItems->toArray()), array_keys($newItems->toArray())));
    //         foreach ($allKeys as $pid) {
    //             $old = $oldItems[$pid] ?? null;
    //             $new = $newItems[$pid] ?? null;

    //             if ($old && !$new) {
    //                 // removed
    //                 $itemsDiff[] = [
    //                     'product'      => $old['product'],
    //                     'old_quantity' => $old['quantity'],
    //                     'new_quantity' => 0,
    //                     'old_total'    => $old['subtotal'],
    //                     'new_total'    => 0,
    //                     'action'       => 'removed',
    //                 ];
    //             } elseif (!$old && $new) {
    //                 // added
    //                 $itemsDiff[] = [
    //                     'product'      => $new['product'],
    //                     'old_quantity' => 0,
    //                     'new_quantity' => $new['quantity'],
    //                     'old_total'    => 0,
    //                     'new_total'    => $new['subtotal'],
    //                     'action'       => 'added',
    //                 ];
    //             } elseif ($old && $new) {
    //                 // updated
    //                 $changed = [];
    //                 foreach (['quantity', 'price', 'subtotal'] as $f) {
    //                     if ($old[$f] != $new[$f]) {
    //                         $changed[$f] = ['old' => $old[$f], 'new' => $new[$f]];
    //                     }
    //                 }
    //                 if (!empty($changed)) {
    //                     $itemsDiff[] = [
    //                         'product'      => $new['product'],
    //                         'action'       => 'updated',
    //                         'fields'       => $changed,
    //                         'old_quantity' => $old['quantity'],
    //                         'new_quantity' => $new['quantity'],
    //                         'old_total'    => $old['subtotal'],
    //                         'new_total'    => $new['subtotal'],
    //                     ];
    //                 }
    //             }
    //         }

    //         $changes = ['purchase' => $purchaseDiff, 'items' => $itemsDiff];

    //         // Update Inventory jika status = Purchase List
    //         if ($purchase->status === 'Purchase List') {
    //             $warehouse = Inventory::firstOrCreate(
    //                 ['purchase_id' => $purchase->id],
    //                 [
    //                     'purchase_number' => $purchase->purchase_number,
    //                     'date'            => $purchase->purchase_date,
    //                     'supplier_id'     => $purchase->supplier_id,
    //                 ]
    //             );

    //             $existingItems = $warehouse->items->keyBy('product_id');
    //             $requestKeys   = [];

    //             foreach ($purchase->purchaseItems as $pItem) {
    //                 $requestKeys[] = $pItem->product_id;

    //                 if ($existingItems->has($pItem->product_id)) {
    //                     // update item lama
    //                     $invItem = $existingItems[$pItem->product_id];
    //                     $invItem->update([
    //                         'quantity'           => $pItem->quantity,
    //                         'remaining_stock_in' => $pItem->quantity, // kalau belum ada stock_out
    //                     ]);
    //                 } else {
    //                     // insert baru
    //                     InventoryItem::create([
    //                         'inventory_id'       => $warehouse->id,
    //                         'product_id'         => $pItem->product_id,
    //                         'purchase_item_id'   => $pItem->id,
    //                         'quantity'           => $pItem->quantity,
    //                         'stock_in'           => 0,
    //                         'remaining_stock_in' => $pItem->quantity,
    //                         'stock_out'          => 0,
    //                     ]);
    //                 }
    //             }

    //             // hapus item lama yang tidak ada lagi di purchase
    //             foreach ($existingItems as $pid => $invItem) {
    //                 if (!in_array($pid, $requestKeys)) {
    //                     $invItem->delete();
    //                 }
    //             }
    //         } else {
    //             $existingWarehouse = Inventory::where('purchase_id', $purchase->id)->first();
    //             if ($existingWarehouse) {
    //                 $existingWarehouse->items()->delete();
    //                 $existingWarehouse->delete();
    //             }
    //         }

    //         // ================== HANDLE ACCOUNT TRANSACTIONS ==================
    //         $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

    //         // Cari transaksi lama untuk purchase account
    //         $existingPurchaseTx = AccountTransaction::where('purchase_id', $purchase->id)
    //             ->where('account_id', $purchaseAccount->id)
    //             ->where('debit', '>', 0)
    //             ->first();

    //         if (!$existingPurchaseTx) {
    //             // Kalau belum ada → buat baru
    //             AccountTransaction::create([
    //                 'purchase_id'        => $purchase->id,
    //                 'purchase_number'    => $purchase->purchase_number,
    //                 'transaction_date'   => $purchase->purchase_date,
    //                 'account_id'         => $purchaseAccount->id,
    //                 'debit'              => $purchase->total_amount,
    //                 'credit'             => 0,
    //                 'note'               => $request->note ?? '',
    //                 'particular'         => 'Purchase Invoice',
    //                 'transaction_group_id' => Str::uuid(),
    //             ]);
    //             $purchaseAccount->increment('closing_balance', $purchase->total_amount);
    //         } else {
    //             // Kalau ada → update nilai debit
    //             $diff = $purchase->total_amount - $existingPurchaseTx->debit;

    //             $existingPurchaseTx->update([
    //                 'transaction_date' => $purchase->purchase_date,
    //                 'debit'            => $purchase->total_amount,
    //                 'note'             => $request->note ?? '',
    //             ]);

    //             if ($diff != 0) {
    //                 $purchaseAccount->increment('closing_balance', $diff);
    //             }
    //         }

    //         // ================== HANDLE ADDITIONAL PAYMENT ==================
    //         if ($request->has('paid_amount') && $request->paid_amount > $paidAmount && $request->cash_bank_account_id) {
    //             $additionalPay = $request->paid_amount - $paidAmount;
    //             if ($additionalPay > 0) {
    //                 $cashBank = Account::findOrFail($request->cash_bank_account_id);

    //                 AccountTransaction::create([
    //                     'purchase_id'        => $purchase->id,
    //                     'purchase_number'    => $purchase->purchase_number,
    //                     'transaction_date'   => $purchase->purchase_date,
    //                     'account_id'         => $cashBank->id,
    //                     'debit'              => 0,
    //                     'credit'             => $additionalPay,
    //                     'note'               => $request->note ?? '',
    //                     'particular'         => 'Additional Payment',
    //                     'transaction_group_id' => Str::uuid(),
    //                 ]);

    //                 $cashBank->decrement('closing_balance', $additionalPay);
    //             }
    //         }

    //         // ===== 6) SIMPAN HISTORY
    //         PurchaseEditHistory::create([
    //             'purchase_id' => $purchase->id,
    //             'edited_by'   => Auth::id(),
    //             'changes'     => $changes,
    //             'text'        => $request->edit_note,
    //             'edited_at'   => now(),
    //         ]);

    //         $purchase->update([
    //             'status_edited' => true,
    //         ]);

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase Update Error', [
    //             'message' => $e->getMessage(),
    //             'line'    => $e->getLine(),
    //             'file'    => $e->getFile(),
    //         ]);
    //         return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date'   => 'required|date',
            'due_date_option' => 'nullable|string|in:none,today,1_week,1_month,3_months,custom',
            'custom_due_date' => 'nullable|date',
            'suppliers'       => 'required|exists:suppliers,id',
            'purchase_number' => 'required|string|unique:purchases,purchase_number,' . $id,
            'status'          => 'required|string',
            'product'         => 'required|array',
            'product.*'       => 'exists:products,id',
            'qty'             => 'required|array',
            'qty.*'           => 'numeric|min:1',
            'price'           => 'required|array',
            'price.*'         => 'numeric|min:0',
            'freight'         => 'required|array',
            'freight.*'       => 'numeric|min:0',
            'total'           => 'required|array',
            'total.*'         => 'numeric|min:0',
            'sub_total'       => 'required|numeric|min:0',
            'tax_percent'     => 'nullable|numeric|min:0',
            'tax_amount'      => 'nullable|numeric|min:0',
            'total_amount_product'  => 'required|numeric|min:0',
            'total_amount_freight'  => 'required|numeric|min:0',
            'total_amount'          => 'required|numeric|min:0',
            'note'            => 'nullable|string',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'edit_note'       => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

            // 🚫 Cegah edit jika sudah ada return/stock-in
            if ($purchase->purchaseReturn()->exists()) {
                DB::rollBack();
                return back()->with('error', 'Purchase ini memiliki Purchase Return dan tidak bisa diedit lagi.');
            }
            if ($purchase->hasStockIn()) {
                DB::rollBack();
                return back()->with('error', 'Purchase ini sudah memiliki Stock In dan tidak bisa diedit lagi.');
            }

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
            $oldItems = $purchase->purchaseItems->mapWithKeys(fn($i) => [
                $i->product_id => [
                    'product'  => $i->purchaseProduct->name ?? 'Unknown',
                    'quantity' => $i->quantity,
                    'price'    => $i->price,
                    'freight'  => $i->freight,
                    'subtotal' => $i->subtotal,
                ]
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
            $grandTotal   = $totalProduct + $totalFreight;

            $paidProduct = $purchase->paid_amount_product ?? 0;
            $paidFreight = $purchase->paid_amount_freight ?? 0;

            $remainingProduct = max(0, $totalProduct - $paidProduct);
            $remainingFreight = max(0, $totalFreight - $paidFreight);
            $remainingAmount  = $remainingProduct + $remainingFreight;

            // ===== 4️⃣ UPDATE PURCHASE HEADER
            $purchase->update([
                'purchase_number' => $request->purchase_number,
                'purchase_date'   => $request->purchase_date,
                'due_date'        => $dueDate,
                'supplier_id'     => $request->suppliers,
                'status'          => $request->status,
                'sub_total'       => $request->sub_total,
                'tax_percent'     => $request->tax_percent,
                'tax_amount'      => $request->tax_amount,
                'total_amount_product'     => $totalProduct,
                'total_amount_freight'     => $totalFreight,
                'total_amount'             => $grandTotal,
                'remaining_amount_product' => $remainingProduct,
                'remaining_amount_freight' => $remainingFreight,
                'remaining_amount'         => $remainingAmount,
            ]);

            // ===== 5️⃣ UPDATE ITEMS
            $existingItems = $purchase->purchaseItems->keyBy('product_id');
            $requestKeys   = [];

            foreach ($request->input('product', []) as $index => $productId) {
                $qty     = $request->qty[$index] ?? 0;
                $price   = $request->price[$index] ?? 0;
                $freight = $request->freight[$index] ?? 0;
                $total   = $request->total[$index] ?? 0;

                if (!$productId) continue;

                $product = Products::findOrFail($productId);
                $requestKeys[] = $productId;

                $oldQty = 0;
                if ($existingItems->has($productId)) {
                    $item = $existingItems[$productId];
                    $oldQty = $item->quantity;
                    $item->update([
                        'quantity' => $qty,
                        'price'    => $price,
                        'freight'  => $freight,
                        'subtotal' => $total,
                    ]);
                } else {
                    PurchaseItem::create([
                        'purchase_id'             => $purchase->id,
                        'product_id'              => $productId,
                        'inventory_warehouse_id'  => $request->inventory_warehouse_id,
                        'status'                  => 'Purchase Account',
                        'product_name'            => $product->name,
                        'quantity'                => $qty,
                        'price'                   => $price,
                        'freight'                 => $freight,
                        'subtotal'                => $total,
                    ]);
                }

                // 🔁 Update Inventory Stock
                $invStock = InventoryStock::firstOrCreate(
                    ['product_id' => $productId, 'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 2],
                    ['incoming_stock' => 0]
                );
                $difference = $qty - $oldQty;
                $invStock->update(['incoming_stock' => max(0, $invStock->incoming_stock + $difference)]);
            }

            // Hapus item yang dihapus dari request
            foreach ($existingItems as $pid => $item) {
                if (!in_array($pid, $requestKeys)) {
                    $item->forceDelete();
                    $invStock = InventoryStock::where('product_id', $pid)->first();
                    if ($invStock) {
                        $totalPurchasedQty = PurchaseItem::where('product_id', $pid)->sum('quantity');
                        $invStock->update(['incoming_stock' => $totalPurchasedQty]);
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
            $newItems = $purchase->purchaseItems->mapWithKeys(fn($i) => [
                $i->product_id => [
                    'product'  => $i->purchaseProduct->name ?? 'Unknown',
                    'quantity' => $i->quantity,
                    'price'    => $i->price,
                    'freight'  => $i->freight,
                    'subtotal' => $i->subtotal,
                ]
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

                if ($old && !$new) {
                    $itemsDiff[] = ['product' => $old['product'], 'action' => 'removed', 'old' => $old, 'new' => null];
                } elseif (!$old && $new) {
                    $itemsDiff[] = ['product' => $new['product'], 'action' => 'added', 'old' => null, 'new' => $new];
                } elseif ($old && $new && $old != $new) {
                    $itemsDiff[] = ['product' => $new['product'], 'action' => 'updated', 'old' => $old, 'new' => $new];
                }
            }

            // ===== 8️⃣ SIMPAN HISTORY
            PurchaseEditHistory::create([
                'purchase_id' => $purchase->id,
                'edited_by'   => Auth::id(),
                'changes'     => ['purchase' => $purchaseDiff, 'items' => $itemsDiff],
                'text'        => $request->edit_note,
                'edited_at'   => now(),
            ]);

            $purchase->update(['status_edited' => true]);

            DB::commit();
            return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase Update Error', [
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
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

            // 🚫 Cek dulu apakah ada Purchase Return
            if ($purchase->purchaseReturn()->exists()) {
                DB::rollBack();
                return back()->with('error', 'Purchase ini memiliki Purchase Return dan tidak bisa dihapus.');
            }

            // 🚫 Cek apakah sudah ada stock in
            if ($purchase->hasStockIn()) {
                DB::rollBack();
                return back()->with('error', 'Purchase ini sudah memiliki Stock In dan tidak bisa dihapus.');
            }

            // 🔁 Rollback stok incoming & stock-in
            foreach ($purchase->purchaseItems as $item) {
                $inventoryStock = InventoryStock::where('product_id', $item->product_id)->first();
                if ($inventoryStock) {
                    $stockInQty = InventoryItem::where('purchase_item_id', $item->id)
                        ->where('stock_in', '>', 0)
                        ->sum('stock_in');

                    $incomingLeft = max(0, $item->quantity - $stockInQty);

                    if ($incomingLeft > 0) {
                        $inventoryStock->decrement('incoming_stock', $incomingLeft);
                    }

                    if ($stockInQty > 0) {
                        $inventoryStock->decrement('stock_after_sales', $stockInQty);
                        InventoryItem::where('purchase_item_id', $item->id)->delete();
                    }
                }
            }

            $productIds = $purchase->purchaseItems->pluck('product_id')->filter()->unique()->toArray();

            // 🔁 Handle account transactions (persis kayak Sale)
            $transactions = AccountTransaction::where('purchase_id', $purchase->id)->get();

            foreach ($transactions as $trx) {
                $account = Account::find($trx->account_id);
                if (!$account) continue;

                if ($account->type === 'Purchase Account') {
                    // Hapus transaksi Purchase Account
                    $account->closing_balance -= $trx->debit;
                    $account->closing_balance += $trx->credit;
                    $trx->delete();
                } else {
                    // Cash / Bank → jangan dihapus, hanya unlink
                    $trx->purchase_id = null;
                    $trx->note = trim(($trx->note ?? '') . ' [Purchase deleted]');
                    $trx->save();
                }

                $account->save();
            }

            // 🔁 Hapus items
            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            // 🔁 Hapus file image kalau ada
            if ($purchase->image && file_exists(public_path('storage/' . $purchase->image))) {
                unlink(public_path('storage/' . $purchase->image));
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
            $purchase->deleted_by   = Auth::id();
            $purchase->save();

            // Soft delete purchase
            $purchase->delete();

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
            return back()->with('success', 'Purchase berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus purchase: ' . $e->getMessage());
        }
    }

    // public function markAsPaid($id, Request $request)
    // {
    //     $request->merge([
    //         'paid_amount' => str_replace('.', '', $request->paid_amount),
    //     ]);

    //     $request->validate([
    //         'purchase_id' => 'required|exists:purchases,id',
    //         'paid_amount' => 'required|numeric|min:0',
    //         'cash_bank_account_id' => 'required|exists:accounts,id',
    //         'transaction_date' => 'required|date',
    //         'transaction_type' => 'required|exists:accounts,id',
    //         'note' => 'nullable|string',
    //         'particular' => 'nullable|string',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $purchase = Purchase::findOrFail($request->purchase_id);

    //         // Ambil transaction_group_id yang sudah ada (jika tidak ada, generate baru)
    //         $groupId = Str::uuid();

    //         $purchaseAccount = Account::findOrFail($request->transaction_type); // Akun pembelian (debit)
    //         $cashBankAccount = Account::findOrFail($request->cash_bank_account_id); // Akun kas/bank (kredit)

    //         // Transaksi KREDIT (kas/bank berkurang)
    //         AccountTransaction::create([
    //             'purchase_id' => $purchase->id,
    //             'purchase_number' => $purchase->purchase_number,
    //             'transaction_date' => $request->transaction_date,
    //             'account_id' => $cashBankAccount->id,
    //             'debit' => 0,
    //             'credit' => $request->paid_amount,
    //             'note' => $request->note ?? '',
    //             'particular' => $purchaseAccount->name . ' - ' . $purchaseAccount->type,
    //             'transaction_group_id' => $groupId,
    //         ]);

    //         $cashBankAccount->closing_balance -= $request->paid_amount;
    //         $cashBankAccount->save();

    //         // Update nilai paid_amount di purchases (bertambah)
    //         $purchase->paid_amount += $request->paid_amount;

    //         $purchase->remaining_amount = $purchase->total_amount - $purchase->paid_amount;

    //         // Kalau sebelumnya Unpaid, bisa ubah jadi Partially Paid atau Paid
    //         if ($purchase->paid_amount == $purchase->total_amount) {
    //             $purchase->payment_status = 'Paid';
    //         } elseif ($purchase->paid_amount > $purchase->total_amount) {
    //             $purchase->payment_status = 'Overpaid';
    //         } else {
    //             $purchase->payment_status = 'Partially Paid';
    //         }

    //         // Simpan group ID jika belum pernah disimpan
    //         if (!$purchase->transaction_group_id) {
    //             $purchase->transaction_group_id = $groupId;
    //         }

    //         $purchase->payment_method = $purchaseAccount->type;
    //         $purchase->save();

    //         DB::commit();
    //         return redirect()->back()->with('success', 'Pembayaran berhasil disimpan.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal menyimpan pembayaran: ' . $e->getMessage());
    //     }
    // }

    public function markAsPaidProduct($id, Request $request)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($request->purchase_id);
            $groupId = Str::uuid();

            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // =========================
            // 1️⃣ Kas / Bank - CREDIT
            // =========================
            AccountTransaction::create([
                'purchase_id'          => $purchase->id,
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $cashBankAccount->id,
                'debit'                => 0,
                'credit'               => $request->paid_amount,
                'note'                 => $request->note ?? '',
                'particular'           => 'Purchase Product Payment - ' . $purchaseAccount->name,
                'transaction_group_id' => $groupId,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // =========================
            // 2️⃣ Purchase Account - DEBIT
            // =========================
            AccountTransaction::create([
                'purchase_id'          => $purchase->id,
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $purchaseAccount->id,
                'debit'                => $request->paid_amount,
                'credit'               => 0,
                'note'                 => $request->note ?? '',
                'particular'           => 'Purchase Product Payment - ' . $cashBankAccount->name,
                'transaction_group_id' => $groupId,
            ]);

            $purchaseAccount->increment('closing_balance', $request->paid_amount);

            // =========================
            // 3️⃣ Update Purchase Fields
            // =========================
            $purchase->paid_amount_product += $request->paid_amount;
            $purchase->remaining_amount_product = $purchase->total_amount_product - $purchase->paid_amount_product;

            // 🔹 Status final: gabungkan hasil pembayaran produk + freight
            $totalPaid = $purchase->paid_amount_product + $purchase->paid_amount_freight;
            $totalAll  = $purchase->total_amount_product + $purchase->total_amount_freight;

            if ($totalPaid >= $totalAll) {
                $purchase->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $purchase->payment_status = 'Partially Paid';
            } else {
                $purchase->payment_status = 'Unpaid';
            }

            $purchase->transaction_group_id = $groupId;
            $purchase->save();

            DB::commit();
            return back()->with('success', 'Pembayaran produk berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran produk: ' . $e->getMessage());
        }
    }

    public function markAsPaidFreight($id, Request $request)
    {
        $request->merge([
            'paid_amount' => str_replace('.', '', $request->paid_amount),
        ]);

        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'paid_amount' => 'required|numeric|min:0',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'note' => 'nullable|string',
            'particular' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($request->purchase_id);
            $groupId = $purchase->transaction_group_id ?? Str::uuid();

            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            // =========================
            // 1️⃣ Kas / Bank - CREDIT
            // =========================
            AccountTransaction::create([
                'purchase_id'          => $purchase->id,
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $cashBankAccount->id,
                'debit'                => 0,
                'credit'               => $request->paid_amount,
                'note'                 => $request->note ?? '',
                'particular'           => 'Freight Payment - ' . $purchaseAccount->name,
                'transaction_group_id' => $groupId,
            ]);

            $cashBankAccount->decrement('closing_balance', $request->paid_amount);

            // =========================
            // 2️⃣ Purchase Account - DEBIT
            // =========================
            AccountTransaction::create([
                'purchase_id'          => $purchase->id,
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $purchaseAccount->id,
                'debit'                => $request->paid_amount,
                'credit'               => 0,
                'note'                 => $request->note ?? '',
                'particular'           => 'Freight Payment - ' . $cashBankAccount->name,
                'transaction_group_id' => $groupId,
            ]);

            $purchaseAccount->increment('closing_balance', $request->paid_amount);

            // =========================
            // 3️⃣ Update Purchase Fields (Freight)
            // =========================
            $purchase->paid_amount_freight += $request->paid_amount;
            $purchase->remaining_amount_freight = $purchase->total_amount_freight - $purchase->paid_amount_freight;

            // 🔹 Status akhir (gabungan produk + freight)
            $totalPaid = $purchase->paid_amount_product + $purchase->paid_amount_freight;
            $totalAll  = $purchase->total_amount_product + $purchase->total_amount_freight;

            if ($totalPaid >= $totalAll) {
                $purchase->payment_status = 'Paid';
            } elseif ($totalPaid > 0) {
                $purchase->payment_status = 'Partially Paid';
            } else {
                $purchase->payment_status = 'Unpaid';
            }

            $purchase->transaction_group_id = $groupId;
            $purchase->save();

            DB::commit();
            return back()->with('success', 'Pembayaran freight berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran freight: ' . $e->getMessage());
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
            'purchase'     => $purchase,
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
            'transaction_date'      => 'required|date',
            'paid_amount'           => 'required|numeric|min:1',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $transactions = AccountTransaction::where('transaction_group_id', $groupId)->get();
            if ($transactions->isEmpty()) {
                throw new \Exception("Payment not found");
            }

            $purchaseId = $transactions->first()->purchase_id;
            $purchase   = Purchase::findOrFail($purchaseId);

            // cari transaksi credit lama (Cash/Bank)
            $oldCredit = $transactions->firstWhere('credit', '>', 0);
            if (!$oldCredit) {
                throw new \Exception("Credit transaction (Cash/Bank) not found in this group");
            }

            $oldAccount = $oldCredit->account;
            $oldAmount  = $oldCredit->credit;

            // rollback saldo akun lama
            $oldAccount->closing_balance += $oldAmount;
            $oldAccount->save();

            // update transaksi credit lama → ganti akun/amount/date/note
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);
            $oldCredit->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'credit'           => $request->paid_amount,
                'note'             => $request->note ?? '',
            ]);

            // update saldo akun baru
            $cashBankAccount->closing_balance -= $request->paid_amount;
            $cashBankAccount->save();

            // update juga tanggal/note untuk baris debit Purchase biar sinkron
            $purchaseTrx = $transactions->firstWhere('debit', '>', 0);
            if ($purchaseTrx) {
                $purchaseTrx->update([
                    'transaction_date' => $request->transaction_date,
                    'note'             => $request->note ?? '',
                ]);
            }

            // hitung ulang paid amount
            $totalPaid = AccountTransaction::where('purchase_id', $purchase->id)
                ->where('credit', '>', 0) // hanya ambil pembayaran Cash/Bank
                ->sum('credit');

            $purchase->paid_amount      = $totalPaid;
            $purchase->remaining_amount = max(0, $purchase->total_amount - $totalPaid);

            if ($purchase->paid_amount == 0) {
                $purchase->payment_status = 'Unpaid';
            } elseif ($purchase->paid_amount < $purchase->total_amount) {
                $purchase->payment_status = 'Partially Paid';
            } elseif ($purchase->paid_amount == $purchase->total_amount) {
                $purchase->payment_status = 'Paid';
            } else {
                $purchase->payment_status = 'Overpaid';
            }

            $purchase->save();

            DB::commit();
            return redirect()->back()->with('success', 'Payment berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update payment: ' . $e->getMessage());
        }
    }

    public function getEditHistory($id)
    {
        $purchase = Purchase::findOrFail($id);

        $histories = PurchaseEditHistory::with('user')
            ->where('purchase_id', $id)
            ->orderBy('edited_at', 'desc')
            ->get();

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
            $purchase = Purchase::onlyTrashed()->with('purchaseItems')->findOrFail($id);

            // ✅ Restore purchase
            $purchase->restore();

            // ✅ Restore purchase items kalau ikut soft delete
            if (method_exists($purchase, 'purchaseItems')) {
                $purchase->purchaseItems()->withTrashed()->restore();
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
                if (!$account) continue;

                if ($account->type === 'Purchase Account') {
                    // restore transaksi purchase account
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
            return redirect()->back()->with('success', 'Purchase berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore purchase gagal', [
                'purchase_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan purchase!');
        }
    }
}
