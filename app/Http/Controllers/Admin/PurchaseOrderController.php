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
use App\Models\ProductionStock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ProductCostService;
use App\Services\PurchaseNumberService;

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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchases = Purchase::with(['supplier', 'purchaseAccount', 'purchaseItems.purchaseProduct'])
            ->where('status', 'Purchase Orders')
            ->orderByDesc('purchase_date');

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
                    $query->where('name', 'like', $request->search_keyword . '%');
                });
            } else {
                $purchases->where('purchase_number', 'like', $request->search_keyword . '%');
            }
        }

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
                    <div>' . e($purchase->purchase_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                // 👤 Supplier
                $supplier = e($purchase->supplier->name ?? '-');

                // 💰 Total Amount
                $totalAmount = 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');

                // 🏷️ Payment Status Badge
                $paymentStatus = strtolower($purchase->payment_status);
                $paymentBadge = match ($paymentStatus) {
                    'paid' => '<div class="badge bg-soft-success text-success">' . e($purchase->payment_status) . '</div>',
                    'unpaid' => '<div class="badge bg-soft-danger text-danger">' . e($purchase->payment_status) . '</div>',
                    default => '<div class="badge bg-soft-warning text-warning">' . e($purchase->payment_status) . '</div>',
                };

                // 🧾 Account & Payment Method
                $accountName = e(optional($purchase->purchaseAccount)->type ?? '-');
                $paymentMethod = e($purchase->payment_method ?? '-');
                $items = $purchase->purchaseItems()->with(['purchaseProduct' => fn($q) => $q->withTrashed()])->get();
                $products = $items->map(function ($item) {
                    return [
                        'name' => $item->purchaseProduct->name ?? '-',
                        'sku' => $item->purchaseProduct->sku ?? '-',
                        'qty' => number_format($item->quantity, 0, ',', '.'),
                    ];
                })->toArray();

                // 🏷️ Status
                $status = strtolower($purchase->status);
                $statusBadge = match ($status) {
                    'purchase orders' => '<div class="badge bg-soft-warning text-warning">' . e($purchase->status) . '</div>',
                    'purchase list' => '<div class="badge bg-soft-success text-success">' . e($purchase->status) . '</div>',
                    default => '<div class="badge bg-secondary">' . e($purchase->status) . '</div>',
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
                    'action' => $actionHtml,
                    'user' => $purchase->user->name ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }


    public function create()
    {
        $products = Products::orderBy('name', 'asc')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('erp.pages.purchases.purchase-orders.create-purchase', compact('products', 'suppliers'));
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
            'suppliers'     => 'required|exists:suppliers,id',
            'product'       => 'required|array',
            'product.*'     => 'exists:products,id',
            'qty'           => 'required|array',
            'qty.*'         => 'numeric|min:1',
            'stock_destination' => 'required|in:warehouse,production',
        ]);

        DB::beginTransaction();

        try {
            $purchaseNumber = PurchaseNumberService::generate($request->purchase_date);

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
                'stock_destination'        => $request->stock_destination,
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date'          => 'required|date_format:Y-m-d\TH:i',
            'suppliers'              => 'required|exists:suppliers,id',
            'notes'                  => 'nullable|string',
            'product'                => 'required|array',
            'product.*'              => 'exists:products,id',
            'qty'                    => 'required|array',
            'qty.*'                  => 'numeric|min:1',
            // 'price'                  => 'required|array',
            // 'price.*'                => 'numeric|min:0',
            // 'freight'                => 'required|array',
            // 'freight.*'              => 'numeric|min:0',
            // 'total'                  => 'required|array',
            // 'total.*'                => 'numeric|min:0',
            // 'sub_total'              => 'required|numeric|min:0',
            'tax_percent'            => 'nullable|numeric|min:0',
            'tax_amount'             => 'nullable|numeric|min:0',
            // 'total_amount_product'   => 'required|numeric|min:0',
            // 'total_amount_freight'   => 'required|numeric|min:0',
            // 'total_amount'           => 'required|numeric|min:0',
            'stock_destination' => 'required|in:warehouse,production',
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
                'stock_destination'        => $request->stock_destination,
            ]);

            // ===== 4️⃣ UPDATE / INSERT ITEM BARU =====
            $processedItemIds = [];

            foreach ($request->product as $index => $productId) {
                $qty     = $request->qty[$index] ?? 0;
                $price   = $request->price[$index] ?? 0;
                $freight = $request->freight[$index] ?? 0;
                $total   = $request->total[$index] ?? 0;

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
            'purchase_date'     => 'required|date_format:Y-m-d\TH:i',
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
            'stock_destination'     => 'required|in:warehouse,production',
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

            foreach ($purchase->purchaseItems as $oldItem) {
                if ($purchase->stock_destination === 'warehouse' && $oldItem->inventory_warehouse_id) {
                    $oldStock = InventoryStock::where('product_id', $oldItem->product_id)
                        ->where('inventory_warehouse_id', $oldItem->inventory_warehouse_id)
                        ->first();
                    if ($oldStock) {
                        $oldStock->decrement('incoming_stock', $oldItem->quantity);
                    }
                }

                if ($purchase->stock_destination === 'production' && $oldItem->production_warehouse_id) {
                    $oldStock = ProductionStock::where('product_id', $oldItem->product_id)
                        ->where('production_warehouse_id', $oldItem->production_warehouse_id)
                        ->first();
                    if ($oldStock) {
                        $oldStock->decrement('incoming_stock', $oldItem->quantity);
                    }
                }
            }

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
                'stock_destination'         => $request->stock_destination,
            ]);

            $purchase->refresh();

            $inventoryStatus = match ($request->stock_destination) {
                'warehouse'  => 'Stock In',
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
                    // 'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 1,
                    'inventory_warehouse_id' => $purchase->stock_destination === 'warehouse'
                        ? ($request->inventory_warehouse_id ?? 1)
                        : null,

                    'production_warehouse_id' => $purchase->stock_destination === 'production'
                        ? ($request->production_warehouse_id ?? 2)
                        : null,
                    'status'                  => 'Purchase Account',
                    'quantity'                => $qty,
                    'price'                   => $price,
                    'price_after_tax'         => $priceAfterTax,
                    'freight'                 => $freight,
                    'final_price'             => $finalPrice,
                    'subtotal'                => $total,
                ]);
                $item->save();

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
                        'supplier_id'     => $purchase->supplier_id,
                        'date'            => $purchase->purchase_date,
                        'status'          => $inventoryStatus,
                        'note'            => 'Purchase Account',
                    ]
                );

                $inventoryItem = InventoryItem::firstOrNew([
                    'purchase_item_id' => $item->id,
                    'inventory_id'     => $inventory->id,
                ]);

                $inventoryItem->fill([
                    'product_id'              => $productId,
                    // 'inventory_warehouse_id'  => $request->inventory_warehouse_id ?? 1,
                    'inventory_warehouse_id' => $purchase->stock_destination === 'warehouse'
                        ? ($request->inventory_warehouse_id ?? 1)
                        : null,

                    'production_warehouse_id' => $purchase->stock_destination === 'production'
                        ? ($request->production_warehouse_id ?? 2)
                        : null,
                    'quantity'                => $qty,
                    'price'                   => $price,
                    'stock_in'                => 0,
                    'remaining_stock_in'      => $qty,
                    'stock_out'               => 0,
                ]);
                $inventoryItem->save();

                // $inventoryStock = InventoryStock::firstOrCreate(
                //     [
                //         'product_id' => $productId,
                //         'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                //     ],
                //     ['incoming_stock' => 0]
                // );

                // $inventoryStock->increment('incoming_stock', $qty);

                // 🧩 UPDATE INCOMING STOCK SESUAI DESTINATION
                if ($purchase->stock_destination === 'warehouse') {
                    $inventoryStock = InventoryStock::firstOrCreate(
                        [
                            'product_id' => $productId,
                            'inventory_warehouse_id' => $request->inventory_warehouse_id ?? 1,
                        ],
                        ['incoming_stock' => 0]
                    );

                    $inventoryStock->increment('incoming_stock', $qty);
                }

                if ($purchase->stock_destination === 'production') {
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

            // ======= UPDATE / CREATE TRANSAKSI AKUN ==========
            $purchaseAccount = Account::where('type', 'Purchase Account')->firstOrFail();

            $trx = AccountTransaction::firstOrNew([
                'purchase_id' => $purchase->id,
                'account_id'  => $purchaseAccount->id,
            ]);

            $trx->fill([
                'purchase_number'      => $purchase->purchase_number,
                'transaction_date'     => $purchase->purchase_date,
                'debit'                => $grandTotal,
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
