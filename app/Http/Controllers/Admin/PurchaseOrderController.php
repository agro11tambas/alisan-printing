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
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ProductCostService;

class PurchaseOrderController extends Controller
{
    public function getPurchaseOrders()
    {
        $purchase_number = Purchase::first();
        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.purchase-orders.purchase-orders', compact('purchase_number', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function dataPurchaseOrders(Request $request)
    {
        $purchases = Purchase::with('supplier')
            ->where('status', 'Purchase Orders');

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

        if ($request->filled('search_keyword')) {
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
                return '<div>
                    <div>' . $purchase->purchase_number . '</div>
                    <small class="text-muted">' . $date . '</small>
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
            ->addColumn('payment_status', function ($purchase) {
                $payment_status = strtolower($purchase->payment_status);

                switch ($payment_status) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->payment_status . '</div>';
                        break;
                    case 'unpaid':
                        return '<div class="badge bg-soft-danger text-danger">' . $purchase->payment_status . '</div>';
                        break;
                    default:
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->payment_status . '</div>';
                        break;
                }
            })
            ->addColumn('account_name', function ($purchase) {
                return optional($purchase->purchaseAccount)->type ?? '-';
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
                return view('erp.pages.purchases.purchase-orders.partials.action-button', compact('purchase'))->render();
            })
            ->rawColumns(['purchase_number', 'total_amount', 'payment_status', 'action', 'status', 'products'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::orderBy('name', 'asc')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('erp.pages.purchases.purchase-orders.create-purchase', compact('products', 'suppliers'));
    }

    // public function store(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'purchase_date'     => 'required|date',
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
    //         $purchaseDate = Carbon::parse($request->purchase_date);
    //         $purchaseDateFormatted = $purchaseDate->format('dmy');
    //         $todayPurchaseCount = Purchase::whereDate('purchase_date', $purchaseDate->toDateString())->count();
    //         $purchaseSequence = $todayPurchaseCount + 1;
    //         $prefix = $request->status === 'Purchase Orders' ? 'PO' : ' ';
    //         $purchaseNumber = $prefix . '/' . $purchaseSequence . '/ALS/' . $purchaseDateFormatted;

    //         // === Hitungan total ===
    //         $subtotal = array_sum($request->total);
    //         $taxAmount = $request->tax_amount ?? 0;
    //         $grandTotal = $subtotal + $taxAmount;

    //         $paidAmount = $request->payment_status === 'Paid' ? $grandTotal : $request->paid_amount;
    //         $remainingAmount = $grandTotal - $paidAmount;

    //         $paidAmount = 0;
    //         $status = 'Purchase Orders';
    //         $paymentStatus = 'Pending';

    //         $purchase = Purchase::create([
    //             'purchase_number' => $purchaseNumber,
    //             'purchase_date'   => $request->purchase_date,
    //             'supplier_id'     => $request->suppliers,
    //             'payment_status'  => $paymentStatus,
    //             'paid_amount'     => $paidAmount,
    //             'sub_total'         => $subtotal,
    //             'tax_percent'       => $request->tax_percent,
    //             'tax_amount'        => $taxAmount,
    //             'total_amount'    => $grandTotal,
    //             'remaining_amount' => $remainingAmount,
    //             'status'          => $status,
    //         ]);

    //         foreach ($request->product as $index => $productId) {
    //             $qty   = $request->qty[$index];
    //             $price = $request->price[$index];
    //             $freight = $request->freight[$index] ?? 0;
    //             $total = $request->total[$index];

    //             $product = Products::findOrFail($productId);

    //             PurchaseItem::create([
    //                 'purchase_id'         => $purchase->id,
    //                 'product_id' => $productId,
    //                 'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                 'product_name'        => $product->name,
    //                 'quantity'            => $qty,
    //                 'price'               => $price,
    //                 'freight'            => $freight,
    //                 'subtotal'            => $total,
    //             ]);
    //         }

    //         if ($purchase->status === 'Purchase List') {
    //             $inventory = Inventory::create([
    //                 'purchase_id'     => $purchase->id,
    //                 'supplier_id'     => $purchase->supplier_id,
    //                 'purchase_number' => $purchase->purchase_number,
    //                 'date'            => $purchase->purchase_date,
    //             ]);

    //             foreach (PurchaseItem::where('purchase_id', $purchase->id)->get() as $item) {
    //                 InventoryItem::create([
    //                     'inventory_id'         => $inventory->id,
    //                     'product_id'  => $item->product_id,
    //                     'quantity'             => $item->quantity,
    //                     'price'                => $item->price,
    //                     'stock_in'             => 0,
    //                     'remaining_stock_in'   => $item->quantity,
    //                     'stock_out'            => 0,
    //                 ]);
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase order created successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase store failed: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Purchase order failed to create');
    //     }
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'purchase_date'          => 'required|date',
    //         'suppliers'              => 'required|exists:suppliers,id',
    //         'product'                => 'required|array',
    //         'product.*'              => 'exists:products,id',
    //         'qty'                    => 'required|array',
    //         'qty.*'                  => 'numeric|min:1',
    //         'price'                  => 'required|array',
    //         'price.*'                => 'numeric|min:0',
    //         'freight'                => 'required|array',
    //         'freight.*'              => 'numeric|min:0',
    //         'total'                  => 'required|array',
    //         'total.*'                => 'numeric|min:0',
    //         'sub_total'              => 'required|numeric|min:0',
    //         'tax_percent'            => 'nullable|numeric|min:0',
    //         'tax_amount'             => 'nullable|numeric|min:0',
    //         'total_amount_product'   => 'required|numeric|min:0',
    //         'total_amount_freight'   => 'required|numeric|min:0',
    //         'total_amount'           => 'required|numeric|min:0',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $purchaseDate = Carbon::parse($request->purchase_date);
    //         $purchaseDateFormatted = $purchaseDate->format('dmy');
    //         $todayPurchaseCount = Purchase::whereDate('purchase_date', $purchaseDate->toDateString())->count();
    //         $purchaseSequence = $todayPurchaseCount + 1;
    //         $prefix = $request->status === 'Purchase Orders' ? 'PO' : ' ';
    //         $purchaseNumber = $prefix . '/' . $purchaseSequence . '/ALS/' . $purchaseDateFormatted;

    //         // === Hitung Total ===
    //         $subtotalProduct = 0;
    //         $subtotalFreight = 0;

    //         foreach ($request->product as $i => $productId) {
    //             $qty = $request->qty[$i] ?? 0;
    //             $price = $request->price[$i] ?? 0;
    //             $freight = $request->freight[$i] ?? 0;
    //             $subtotalProduct += $qty * $price;
    //             $subtotalFreight += $qty * $freight;
    //         }

    //         $taxPercent = $request->tax_percent ?? 0;
    //         $taxAmount  = $request->tax_amount ?? 0;
    //         $totalProduct = $subtotalProduct + $taxAmount;
    //         $totalFreight = $subtotalFreight;
    //         $grandTotal   = $totalProduct + $totalFreight;
    //         $subTotal     = $subtotalProduct + $subtotalFreight;

    //         // === Inisialisasi Pembayaran ===
    //         $paidProduct = 0;
    //         $paidFreight = 0;
    //         $remainingProduct = $totalProduct - $paidProduct;
    //         $remainingFreight = $totalFreight - $paidFreight;
    //         $remainingAmount  = $remainingProduct + $remainingFreight;

    //         $status = 'Purchase Orders';
    //         $paymentStatus = 'Pending';

    //         // === Simpan Purchase ===
    //         $purchase = Purchase::create([
    //             'purchase_number' => $purchaseNumber,
    //             'purchase_date'   => $request->purchase_date,
    //             'supplier_id'     => $request->suppliers,
    //             'status'          => $status,
    //             'payment_status'  => $paymentStatus,

    //             'sub_total'       => $subTotal,
    //             'tax_percent'     => $taxPercent,
    //             'tax_amount'      => $taxAmount,

    //             'total_amount_product'     => $totalProduct,
    //             'total_amount_freight'     => $totalFreight,
    //             'total_amount'             => $grandTotal,

    //             'paid_amount_product'      => $paidProduct,
    //             'paid_amount_freight'      => $paidFreight,
    //             'paid_amount'              => 0,

    //             'remaining_amount_product' => $remainingProduct,
    //             'remaining_amount_freight' => $remainingFreight,
    //             'remaining_amount'         => $remainingAmount,
    //         ]);

    //         // === Simpan Item Purchase ===
    //         foreach ($request->product as $index => $productId) {
    //             $qty     = $request->qty[$index];
    //             $price   = $request->price[$index];
    //             $freight = $request->freight[$index] ?? 0;
    //             $total   = $request->total[$index];

    //             $product = Products::findOrFail($productId);

    //             PurchaseItem::create([
    //                 'purchase_id'             => $purchase->id,
    //                 'product_id'              => $productId,
    //                 'inventory_warehouse_id'  => $request->inventory_warehouse_id,
    //                 'product_name'            => $product->name,
    //                 'quantity'                => $qty,
    //                 'price'                   => $price,
    //                 'freight'                 => $freight,
    //                 'subtotal'                => $total,
    //             ]);
    //         }

    //         // === Jika langsung ke Purchase List, buat Inventory ===
    //         if ($purchase->status === 'Purchase List') {
    //             $inventory = Inventory::create([
    //                 'purchase_id'     => $purchase->id,
    //                 'supplier_id'     => $purchase->supplier_id,
    //                 'purchase_number' => $purchase->purchase_number,
    //                 'date'            => $purchase->purchase_date,
    //             ]);

    //             foreach ($purchase->purchaseItems as $item) {
    //                 InventoryItem::create([
    //                     'inventory_id'         => $inventory->id,
    //                     'product_id'           => $item->product_id,
    //                     'quantity'             => $item->quantity,
    //                     'price'                => $item->price,
    //                     'stock_in'             => 0,
    //                     'remaining_stock_in'   => $item->quantity,
    //                     'stock_out'            => 0,
    //                 ]);
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase order created successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase store failed', [
    //             'message' => $e->getMessage(),
    //             'line'    => $e->getLine(),
    //             'file'    => $e->getFile(),
    //         ]);
    //         return redirect()->back()->with('error', 'Purchase order failed to create: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_date' => 'required|date',
            'suppliers'     => 'required|exists:suppliers,id',
            'product'       => 'required|array',
            'product.*'     => 'exists:products,id',
            'qty'           => 'required|array',
            'qty.*'         => 'numeric|min:1',
        ]);

        DB::beginTransaction();

        try {
            $purchaseDate = Carbon::parse($request->purchase_date);
            $purchaseDateFormatted = $purchaseDate->format('dmy');
            $todayPurchaseCount = Purchase::whereDate('purchase_date', $purchaseDate->toDateString())->count();
            $purchaseSequence = $todayPurchaseCount + 1;
            $purchaseNumber = 'PO/' . $purchaseSequence . '/ALS/' . $purchaseDateFormatted;

            $status = 'Purchase Orders';
            $paymentStatus = 'Pending';

            // === Simpan Purchase ===
            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'purchase_date'   => $request->purchase_date,
                'supplier_id'     => $request->suppliers,
                'status'          => $status,
                'payment_status'  => $paymentStatus,
                'sub_total'       => 0,
                'total_amount'    => 0,
                'tax_percent'     => 0,
                'tax_amount'      => 0,
                'total_amount_product' => 0,
                'total_amount_freight' => 0,
                'paid_amount'     => 0,
                'remaining_amount' => 0,
            ]);

            // === Simpan Item Purchase ===
            foreach ($request->product as $index => $productId) {
                $qty = $request->qty[$index];
                $product = Products::findOrFail($productId);

                PurchaseItem::create([
                    'purchase_id'            => $purchase->id,
                    'product_id'             => $productId,
                    'inventory_warehouse_id' => $request->inventory_warehouse_id,
                    'product_name'           => $product->name,
                    'quantity'               => $qty,
                    'price'                  => 0,
                    'freight'                => 0,
                    'subtotal'               => 0,
                ]);
            }

            DB::commit();
            return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase store failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Purchase order failed to create: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

        $products = Products::all();
        $suppliers = Supplier::all();
        return view('erp.pages.purchases.purchase-orders.edit-purchase', compact(
            'purchase',
            'products',
            'suppliers',
        ));
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'purchase_date'     => 'required|date',
    //         'suppliers'         => 'required|exists:suppliers,id',
    //         'notes'             => 'nullable|string',
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
    //         $purchase = Purchase::with('purchaseItems')->findOrFail($id);

    //         $calculatedTotalAmount = array_sum($request->total);
    //         $taxAmount = $request->tax_amount ?? 0;

    //         $paidAmount = $request->payment_status === 'Paid' ? $calculatedTotalAmount : $request->paid_amount;
    //         $remainingAmount = ($calculatedTotalAmount + $taxAmount) - $paidAmount;

    //         $paidAmount = 0;
    //         $status = 'Purchase Orders';
    //         $paymentStatus = 'Pending';

    //         // ================== UPDATE PURCHASE HEADER ==================
    //         $purchase->update([
    //             'purchase_date'     => $request->purchase_date,
    //             'supplier_id'       => $request->suppliers,
    //             'payment_status'    => $paymentStatus,
    //             'paid_amount'       => $paidAmount,
    //             'sub_total'         => $request->sub_total,
    //             'tax_percent'       => $request->tax_percent,
    //             'tax_amount'        => $taxAmount,
    //             'total_amount'      => $calculatedTotalAmount + $taxAmount,
    //             'remaining_amount'  => $remainingAmount,
    //             'status'            => $status,
    //             'notes'             => $request->notes,
    //         ]);

    //         // ================== UPDATE / INSERT ITEM BARU ==================
    //         $processedItemIds = [];

    //         foreach ($request->product as $index => $productId) {
    //             $qty   = $request->qty[$index];
    //             $price = $request->price[$index];
    //             $freight = $request->freight[$index];
    //             $total = $request->total[$index];

    //             $product = Products::findOrFail($productId);

    //             $item = PurchaseItem::updateOrCreate(
    //                 [
    //                     'purchase_id' => $purchase->id,
    //                     'product_id'  => $productId,
    //                 ],
    //                 [
    //                     'inventory_warehouse_id' => $request->inventory_warehouse_id,
    //                     'product_name'           => $product->name,
    //                     'quantity'               => $qty,
    //                     'price'                  => $price,
    //                     'freight'                => $freight,
    //                     'subtotal'               => $total,
    //                     'deleted_at'             => null, // restore kalau sebelumnya soft delete
    //                 ]
    //             );

    //             $processedItemIds[] = $item->id;
    //         }

    //         // ================== HAPUS ITEM YANG SUDAH TIDAK ADA ==================
    //         $purchase->purchaseItems()
    //             ->whereNotIn('id', $processedItemIds)
    //             ->delete();

    //         // ================== HANDLE INVENTORY ==================
    //         if ($request->status === 'Purchase List') {
    //             $inventory = Inventory::updateOrCreate(
    //                 ['purchase_id' => $purchase->id],
    //                 [
    //                     'supplier_id'     => $purchase->supplier_id,
    //                     'purchase_number' => $purchase->purchase_number,
    //                     'date'            => $purchase->purchase_date,
    //                 ]
    //             );

    //             // Hapus item inventory sebelumnya
    //             InventoryItem::where('inventory_id', $inventory->id)->delete();

    //             // Simpan item baru dari purchase items
    //             $items = $purchase->purchaseItems()->get();
    //             foreach ($items as $item) {
    //                 InventoryItem::create([
    //                     'inventory_id'       => $inventory->id,
    //                     'product_id'         => $item->product_id,
    //                     'quantity'           => $item->quantity,
    //                     'price'              => $item->price,
    //                     'stock_in'           => 0,
    //                     'remaining_stock_in' => $item->quantity,
    //                     'stock_out'          => 0,
    //                 ]);
    //             }
    //         } else {
    //             // Jika status bukan Purchase List, hapus data Inventory-nya
    //             $existingInventory = Inventory::where('purchase_id', $purchase->id)->first();
    //             if ($existingInventory) {
    //                 InventoryItem::where('inventory_id', $existingInventory->id)->delete();
    //                 $existingInventory->delete();
    //             }
    //         }

    //         DB::commit();

    //         $redirectUrl = $request->status === 'Purchase List'
    //             ? '/erp/purchases/purchase-list'
    //             : '/erp/purchases/purchase-orders';

    //         return redirect($redirectUrl)->with('success', 'Purchase updated successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Purchase update failed: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Purchase order failed to update. ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date'          => 'required|date',
            'suppliers'              => 'required|exists:suppliers,id',
            'notes'                  => 'nullable|string',
            'product'                => 'required|array',
            'product.*'              => 'exists:products,id',
            'qty'                    => 'required|array',
            'qty.*'                  => 'numeric|min:1',
            'price'                  => 'required|array',
            'price.*'                => 'numeric|min:0',
            'freight'                => 'required|array',
            'freight.*'              => 'numeric|min:0',
            'total'                  => 'required|array',
            'total.*'                => 'numeric|min:0',
            'sub_total'              => 'required|numeric|min:0',
            'tax_percent'            => 'nullable|numeric|min:0',
            'tax_amount'             => 'nullable|numeric|min:0',
            'total_amount_product'   => 'required|numeric|min:0',
            'total_amount_freight'   => 'required|numeric|min:0',
            'total_amount'           => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

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
            $taxAmount  = $request->tax_amount ?? 0;
            $totalProduct = $subtotalProduct + $taxAmount;
            $totalFreight = $subtotalFreight;
            $grandTotal   = $totalProduct + $totalFreight;
            $subTotal     = $subtotalProduct + $subtotalFreight;

            // ===== 2️⃣ PEMBAYARAN =====
            $paidProduct = $purchase->paid_amount_product ?? 0;
            $paidFreight = $purchase->paid_amount_freight ?? 0;

            $remainingProduct = max(0, $totalProduct - $paidProduct);
            $remainingFreight = max(0, $totalFreight - $paidFreight);
            $remainingAmount  = $remainingProduct + $remainingFreight;

            $status = 'Purchase Orders';
            $paymentStatus = 'Pending';

            // ===== 3️⃣ UPDATE PURCHASE HEADER =====
            $purchase->update([
                'purchase_date'     => $request->purchase_date,
                'supplier_id'       => $request->suppliers,
                'payment_status'    => $paymentStatus,
                'sub_total'         => $subTotal,
                'tax_percent'       => $taxPercent,
                'tax_amount'        => $taxAmount,
                'total_amount_product'     => $totalProduct,
                'total_amount_freight'     => $totalFreight,
                'total_amount'             => $grandTotal,
                'paid_amount_product'      => $paidProduct,
                'paid_amount_freight'      => $paidFreight,
                'paid_amount'              => $paidProduct + $paidFreight,
                'remaining_amount_product' => $remainingProduct,
                'remaining_amount_freight' => $remainingFreight,
                'remaining_amount'         => $remainingAmount,
                'status'                   => $status,
                'notes'                    => $request->notes,
            ]);

            // ===== 4️⃣ UPDATE / INSERT ITEM BARU =====
            $processedItemIds = [];

            foreach ($request->product as $index => $productId) {
                $qty     = $request->qty[$index];
                $price   = $request->price[$index];
                $freight = $request->freight[$index];
                $total   = $request->total[$index];

                $product = Products::findOrFail($productId);

                $item = PurchaseItem::updateOrCreate(
                    [
                        'purchase_id' => $purchase->id,
                        'product_id'  => $productId,
                    ],
                    [
                        'inventory_warehouse_id' => $request->inventory_warehouse_id,
                        'product_name'           => $product->name,
                        'quantity'               => $qty,
                        'price'                  => $price,
                        'freight'                => $freight,
                        'subtotal'               => $total,
                        'deleted_at'             => null,
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
                        'supplier_id'     => $purchase->supplier_id,
                        'purchase_number' => $purchase->purchase_number,
                        'date'            => $purchase->purchase_date,
                    ]
                );

                // Reset item inventory
                InventoryItem::where('inventory_id', $inventory->id)->delete();

                // Tambahkan item baru dari purchase items
                $items = $purchase->purchaseItems()->get();
                foreach ($items as $item) {
                    InventoryItem::create([
                        'inventory_id'       => $inventory->id,
                        'product_id'         => $item->product_id,
                        'quantity'           => $item->quantity,
                        'price'              => $item->price,
                        'stock_in'           => 0,
                        'remaining_stock_in' => $item->quantity,
                        'stock_out'          => 0,
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
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Purchase order failed to update. ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

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
            if ($purchase->image && file_exists(public_path('storage/' . $purchase->image))) {
                unlink(public_path('storage/' . $purchase->image));
            }

            // Hard delete purchase
            $purchase->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Purchase deleted permanently');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete purchase: ' . $e->getMessage());
        }
    }

    // public function markAsPurchaseList($id, Request $request)
    // {
    //     $request->merge([
    //         'paid_amount' => str_replace('.', '', $request->paid_amount),
    //     ]);

    //     $rules = [
    //         'purchase_number' => 'required',
    //         'purchase_date' => 'required|date',
    //         // 'payment_status' => 'required|in:Paid,Unpaid,Partially Paid',
    //         'notes' => 'nullable|string',
    //         'note' => 'nullable|string',
    //         'particular' => 'nullable|string',
    //     ];

    //     if ($request->payment_status !== 'Unpaid') {
    //         $rules = array_merge($rules, [
    //             'paid_amount' => 'nullable|numeric|min:0',
    //             'cash_bank_account_id' => 'nullable|exists:accounts,id',
    //             'transaction_date' => 'nullable|date',
    //             'transaction_type' => 'nullable|exists:accounts,id',
    //         ]);
    //     }

    //     $request->validate($rules);

    //     DB::beginTransaction();

    //     try {
    //         $purchase = Purchase::findOrFail($id);
    //         $status = 'Purchase List';

    //         $paidAmount = $request->paid_amount ?? 0;
    //         $totalAmount = $purchase->total_amount;
    //         $remainingAmount = $totalAmount - $paidAmount;

    //         if ($paidAmount <= 0) {
    //             $paymentStatus = 'Unpaid';
    //         } elseif ($paidAmount < $totalAmount) {
    //             $paymentStatus = 'Partially Paid';
    //         } else {
    //             $paymentStatus = 'Paid';
    //         }

    //         $groupId = Str::uuid();
    //         $purchaseAccount = Account::findOrFail($request->transaction_type);

    //         AccountTransaction::create([
    //             'purchase_id' => $purchase->id,
    //             'purchase_number' => $purchase->purchase_number,
    //             'transaction_date' => $request->purchase_date,
    //             'account_id' => $purchaseAccount->id,
    //             'debit' => $totalAmount,
    //             'credit' => 0,
    //             'note' => $request->note ?? '',
    //             'particular' => '',
    //             'transaction_group_id' => $groupId,
    //         ]);

    //         $purchaseAccount->closing_balance += $totalAmount;
    //         $purchaseAccount->save();

    //         if ($paidAmount > 0) {
    //             $groupId = Str::uuid();
    //             $purchaseAccount = Account::findOrFail($request->transaction_type);
    //             $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

    //             AccountTransaction::create([
    //                 'purchase_id' => $purchase->id,
    //                 'purchase_number' => $purchase->purchase_number,
    //                 'transaction_date' => $request->transaction_date,
    //                 'account_id' => $cashBankAccount->id,
    //                 'debit' => 0,
    //                 'credit' => $request->paid_amount,
    //                 'note' => $request->note ?? '',
    //                 'particular' => $purchaseAccount->name . ' - ' . $purchaseAccount->type,
    //                 'transaction_group_id' => $groupId,
    //             ]);

    //             $cashBankAccount->closing_balance -= $request->paid_amount;
    //             $cashBankAccount->save();

    //             $purchase->transaction_group_id = $groupId;
    //             $purchase->payment_method = $purchaseAccount->type;
    //             $purchase->save();
    //         }

    //         $purchase->update([
    //             'purchase_number' => $request->purchase_number,
    //             'status' => $status,
    //             'purchase_date' => $request->purchase_date,
    //             'paid_amount' => $request->paid_amount ?? 0,
    //             'remaining_amount' => $remainingAmount,
    //             'payment_status' => $paymentStatus,
    //             'transaction_type' => $request->transaction_type ?? null,
    //             'notes' => $request->notes,
    //             'payment_method' => $paymentStatus !== 'Unpaid' ? $purchaseAccount->type : null,
    //         ]);

    //         // Simpan inventory
    //         if ($purchase->status === 'Purchase List') {
    //             $inventory = Inventory::create([
    //                 'purchase_id' => $purchase->id,
    //                 'purchase_number' => $purchase->purchase_number,
    //                 'date' => $purchase->purchase_date,
    //                 'status' => 'Stock In',
    //                 'note' => 'Purchase Account',
    //             ]);

    //             foreach (PurchaseItem::where('purchase_id', $purchase->id)->get() as $item) {
    //                 InventoryItem::create([
    //                     'inventory_id' => $inventory->id,
    //                     'product_id' => $item->product_id,
    //                     'inventory_warehouse_id' => $item->inventory_warehouse_id,
    //                     'purchase_item_id' => $item->id,
    //                     'quantity' => $item->quantity,
    //                     'price' => $item->price,
    //                     'stock_in' => 0,
    //                     'remaining_stock_in' => $item->quantity,
    //                     'stock_out' => 0,
    //                 ]);

    //                 $item->status = 'Purchase Account';
    //                 $item->save();

    //                 // ✅ Update incoming stock seperti di store()
    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     [
    //                         'product_id' => $item->product_id,
    //                         'inventory_warehouse_id' => $item->inventory_warehouse_id ?? 2,
    //                     ],
    //                     [
    //                         'incoming_stock' => 0,
    //                     ]
    //                 );

    //                 // Tambahkan incoming stock sesuai jumlah purchase
    //                 $inventoryStock->increment('incoming_stock', $item->quantity);

    //                 // ✅ Update cost produk
    //                 // ProductCostService::updateCostAndStock($item->purchaseProduct);
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase Order marked as Purchase List.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal memperbarui status pembelian: ' . $e->getMessage());
    //     }
    // }

    // public function markAsPurchaseList($id, Request $request)
    // {
    //     // 🔹 Bersihkan format angka
    //     $request->merge([
    //         'paid_amount_product' => str_replace('.', '', $request->paid_amount_product ?? 0),
    //         'paid_amount_freight' => str_replace('.', '', $request->paid_amount_freight ?? 0),
    //     ]);

    //     $rules = [
    //         'purchase_number'      => 'required',
    //         'purchase_date'        => 'required|date',
    //         'notes'                => 'nullable|string',
    //         'cash_bank_account_id' => 'nullable|exists:accounts,id',
    //         'transaction_date'     => 'nullable|date',
    //         'transaction_type'     => 'nullable|exists:accounts,id',
    //     ];

    //     $request->validate($rules);

    //     DB::beginTransaction();
    //     try {
    //         $purchase = Purchase::findOrFail($id);
    //         $status   = 'Purchase List';

    //         // 🔹 Hitung produk
    //         $totalProduct     = $purchase->total_amount_product ?? 0;
    //         $paidProduct      = (float) $request->paid_amount_product ?? 0;
    //         $remainingProduct = max($totalProduct - $paidProduct, 0);

    //         // 🔹 Hitung freight
    //         $totalFreight     = $purchase->total_amount_freight ?? 0;
    //         $paidFreight      = (float) $request->paid_amount_freight ?? 0;
    //         $remainingFreight = max($totalFreight - $paidFreight, 0);

    //         // 🔹 Status pembayaran
    //         $paymentStatusProduct = $paidProduct <= 0 ? 'Unpaid' : ($paidProduct < $totalProduct ? 'Partially Paid' : 'Paid');
    //         $paymentStatusFreight = $paidFreight <= 0 ? 'Unpaid' : ($paidFreight < $totalFreight ? 'Partially Paid' : 'Paid');

    //         // 🔹 Semua transaksi pakai Purchase Account
    //         $purchaseAccount = Account::findOrFail($request->transaction_type);

    //         // ✅ Transaksi Produk
    //         if ($totalProduct > 0) {
    //             $groupId = Str::uuid();

    //             AccountTransaction::create([
    //                 'purchase_id'          => $purchase->id,
    //                 'purchase_number'      => $purchase->purchase_number,
    //                 'transaction_date'     => $request->purchase_date,
    //                 'account_id'           => $purchaseAccount->id,
    //                 'debit'                => $totalProduct,
    //                 'credit'               => 0,
    //                 'note'                 => 'Purchase Product',
    //                 'particular'           => 'Purchase Product',
    //                 'transaction_group_id' => $groupId,
    //             ]);

    //             $purchaseAccount->increment('closing_balance', $totalProduct);

    //             if ($paidProduct > 0 && $request->cash_bank_account_id) {
    //                 $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

    //                 AccountTransaction::create([
    //                     'purchase_id'          => $purchase->id,
    //                     'purchase_number'      => $purchase->purchase_number,
    //                     'transaction_date'     => $request->transaction_date,
    //                     'account_id'           => $cashBankAccount->id,
    //                     'debit'                => 0,
    //                     'credit'               => $paidProduct,
    //                     'note'                 => 'Payment to Supplier',
    //                     'particular'           => $purchaseAccount->name . ' - Product',
    //                     'transaction_group_id' => $groupId,
    //                 ]);

    //                 $cashBankAccount->decrement('closing_balance', $paidProduct);
    //             }
    //         }

    //         // ✅ Transaksi Freight (juga pakai Purchase Account)
    //         if ($totalFreight > 0) {
    //             $groupId = Str::uuid();

    //             AccountTransaction::create([
    //                 'purchase_id'          => $purchase->id,
    //                 'purchase_number'      => $purchase->purchase_number,
    //                 'transaction_date'     => $request->purchase_date,
    //                 'account_id'           => $purchaseAccount->id,
    //                 'debit'                => $totalFreight,
    //                 'credit'               => 0,
    //                 'note'                 => 'Purchase Freight',
    //                 'particular'           => 'Purchase Freight (same account)',
    //                 'transaction_group_id' => $groupId,
    //             ]);

    //             $purchaseAccount->increment('closing_balance', $totalFreight);

    //             if ($paidFreight > 0 && $request->cash_bank_account_id) {
    //                 $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

    //                 AccountTransaction::create([
    //                     'purchase_id'          => $purchase->id,
    //                     'purchase_number'      => $purchase->purchase_number,
    //                     'transaction_date'     => $request->transaction_date,
    //                     'account_id'           => $cashBankAccount->id,
    //                     'debit'                => 0,
    //                     'credit'               => $paidFreight,
    //                     'note'                 => 'Payment to Expedition',
    //                     'particular'           => $purchaseAccount->name . ' - Freight',
    //                     'transaction_group_id' => $groupId,
    //                 ]);

    //                 $cashBankAccount->decrement('closing_balance', $paidFreight);
    //             }
    //         }

    //         // 🔹 Update purchase utama
    //         $purchase->update([
    //             'purchase_number'             => $request->purchase_number,
    //             'status'                      => $status,
    //             'purchase_date'               => $request->purchase_date,
    //             'paid_amount_product'         => $paidProduct,
    //             'remaining_amount_product'    => $remainingProduct,
    //             'payment_status_product'      => $paymentStatusProduct,
    //             'paid_amount_freight'         => $paidFreight,
    //             'remaining_amount_freight'    => $remainingFreight,
    //             'payment_status_freight'      => $paymentStatusFreight,
    //             'transaction_type'            => $request->transaction_type,
    //             'cash_bank_account_id'        => $request->cash_bank_account_id,
    //             'notes'                       => $request->notes,
    //         ]);

    //         // 🔹 Simpan inventory (tetap sama)
    //         if ($purchase->status === 'Purchase List' && !Inventory::where('purchase_id', $purchase->id)->exists()) {
    //             $inventory = Inventory::create([
    //                 'purchase_id'       => $purchase->id,
    //                 'purchase_number'   => $purchase->purchase_number,
    //                 'date'              => $purchase->purchase_date,
    //                 'status'            => 'Stock In',
    //                 'note'              => 'Purchase Account',
    //             ]);

    //             foreach (PurchaseItem::where('purchase_id', $purchase->id)->get() as $item) {
    //                 InventoryItem::create([
    //                     'inventory_id'           => $inventory->id,
    //                     'product_id'             => $item->product_id,
    //                     'inventory_warehouse_id' => $item->inventory_warehouse_id,
    //                     'purchase_item_id'       => $item->id,
    //                     'quantity'               => $item->quantity,
    //                     'price'                  => $item->price,
    //                     'stock_in'               => 0,
    //                     'remaining_stock_in'     => $item->quantity,
    //                     'stock_out'              => 0,
    //                 ]);

    //                 $item->update(['status' => 'Purchase Account']);

    //                 $inventoryStock = InventoryStock::firstOrCreate(
    //                     [
    //                         'product_id'             => $item->product_id,
    //                         'inventory_warehouse_id' => $item->inventory_warehouse_id ?? 2,
    //                     ],
    //                     ['incoming_stock' => 0]
    //                 );

    //                 $inventoryStock->increment('incoming_stock', $item->quantity);
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase Order marked as Purchase List.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()->with('error', 'Gagal memperbarui status pembelian: ' . $e->getMessage());
    //     }
    // }

    public function markAsPurchaseList($id)
    {
        $purchase = Purchase::with(['supplier', 'purchaseItems.purchaseProduct'])->findOrFail($id);

        // Ambil semua supplier (kalau ingin dropdown tetap aktif)
        $suppliers = Supplier::all();

        // Ambil semua produk (kalau ingin bisa tambah produk lain)
        $products = Products::orderBy('name', 'asc')
            ->addSelect([
                'last_price' => DB::table('purchase_items as pi')
                    ->select('pi.price')
                    ->whereColumn('pi.product_id', 'products.id')
                    ->where('pi.price', '>', 0)
                    ->orderByDesc('pi.id')
                    ->limit(1)
            ])
            ->get();
            
        return view('erp.pages.purchases.purchase-orders.mark-as-purchase-list', [
            'purchase'  => $purchase,
            'suppliers' => $suppliers,
            'products'  => $products,
        ]);
    }

    public function updatePurchaseList(Request $request, $id)
    {
        $request->validate([
            'purchase_number'   => 'required|string|unique:purchases,purchase_number,' . $id,
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
            $purchase = Purchase::with(['purchaseItems'])->findOrFail($id);
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
            $totalProduct  = $request->total_amount_product;
            $totalFreight  = $request->total_amount_freight;
            $taxPercent    = $request->tax_percent ?? 0;

            // ✅ Pajak hanya dihitung dari product
            $taxAmount = ($totalProduct * $taxPercent) / 100;

            // ✅ Product (sudah termasuk pajak)
            $totalProductWithTax = $totalProduct + $taxAmount;

            // ✅ Grand total = produk (termasuk pajak) + freight
            $grandTotal = $totalProductWithTax + $totalFreight;

            // ✅ Semua unpaid
            $paidProduct       = 0;
            $remainingProduct  = $totalProductWithTax;
            $paidFreight       = 0;
            $remainingFreight  = $totalFreight;

            // ====== UPDATE HEADER ======
            $purchase->update([
                'purchase_number'           => $request->purchase_number,
                'purchase_date'             => $purchaseDate,
                'due_date'                  => $dueDate,
                'supplier_id'               => $request->suppliers,
                'payment_status'            => $paymentStatus,

                // detail nilai
                'sub_total'                 => $totalProduct + $totalFreight,
                'tax_percent'               => $taxPercent,
                'tax_amount'                => $taxAmount,
                'freight_total'             => $totalFreight,

                // ✅ Produk (sudah termasuk pajak)
                'total_amount_product'      => $totalProductWithTax,
                'paid_amount_product'       => $paidProduct,
                'remaining_amount_product'  => $remainingProduct,

                // ✅ Freight (tanpa pajak)
                'total_amount_freight'      => $totalFreight,
                'paid_amount_freight'       => $paidFreight,
                'remaining_amount_freight'  => $remainingFreight,

                // ✅ Grand total
                'total_amount'              => $grandTotal,
                'paid_amount'               => 0,
                'remaining_amount'          => $grandTotal,
                'status'                    => $status,
            ]);

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
                $qty     = $request->qty[$index];
                $price   = $request->price[$index];
                $freight = $request->freight[$index];
                $total   = $request->total[$index];

                // ✅ Hitung price_after_tax & final_price
                $taxPercent = $request->tax_percent ?? 0;
                $priceAfterTax = $price + ($price * $taxPercent / 100);
                $finalPrice = $priceAfterTax + $freight;

                $item = $purchase->purchaseItems[$index] ?? new PurchaseItem(['purchase_id' => $purchase->id]);
                $item->fill([
                    'product_id'              => $productId,
                    'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 1,
                    'status'                  => 'Purchase Account',
                    'quantity'                => $qty,
                    'price'                   => $price,
                    'price_after_tax'         => $priceAfterTax,
                    'freight'                 => $freight,
                    'final_price'             => $finalPrice,
                    'subtotal'                => $total,
                ]);
                $item->save();

                // === Update atau buat Inventory ===
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

                $inventoryItem = InventoryItem::firstOrNew([
                    'purchase_item_id' => $item->id,
                    'inventory_id'     => $inventory->id,
                ]);

                $inventoryItem->fill([
                    'product_id'              => $productId,
                    'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 1,
                    'quantity'                => $qty,
                    'price'                   => $price,
                    'stock_in'                => 0,
                    'remaining_stock_in'      => $qty,
                    'stock_out'               => 0,
                ]);
                $inventoryItem->save();

                $inventoryStock = InventoryStock::firstOrCreate(
                    [
                        'product_id' => $productId,
                        'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                    ],
                    ['incoming_stock' => 0]
                );

                $inventoryStock->increment('incoming_stock', $qty);
            }

            // ======= UPDATE / CREATE TRANSAKSI AKUN ==========
            $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

            $trx = AccountTransaction::firstOrNew([
                'purchase_id' => $purchase->id,
                'account_id'  => $purchaseAccount->id,
            ]);

            $trx->fill([
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $purchase->purchase_date,
                'debit'                => $purchase->total_amount_product,
                'credit'               => 0,
                'note'                 => 'Purchase Account Transaction (Updated)',
                'particular'           => 'Purchase Invoice',
                'transaction_group_id' => $trx->transaction_group_id ?? Str::uuid(),
            ]);
            $trx->save();

            DB::commit();

            return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase list berhasil diperbarui tanpa hapus data lama!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase update failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui purchase list: ' . $e->getMessage());
        }
    }
}
