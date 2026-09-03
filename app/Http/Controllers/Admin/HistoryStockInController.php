<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CanceledProduct;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\Purchase;
use App\Models\Products;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use Yajra\DataTables\Facades\DataTables;
use App\Models\InventoryStockOutHistory;
use App\Models\ProductionStock;
use App\Services\ProductCostService;
use App\Services\FifoCostService;
use App\Support\UploadLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoryStockInController extends Controller
{
    // public function addStockIn($id)
    // {
    //     $stockIn = Inventory::with('items.product')->findOrFail($id);

    //     return view('erp.pages.inventory.stock-in.add-stock-in', compact('stockIn'));
    // }

    public function addStockIn($supplierId, $year, $month)
    {
        $groupedInventories = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->whereHas('purchase', function ($q) use ($year, $month) {
                $q->whereYear('purchase_date', $year)
                    ->whereMonth('purchase_date', $month);
            })
            ->get();

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier    = optional($groupedInventories->first()->purchase->supplier);
        $monthLabel  = Carbon::createFromDate($year, $month, 1)->format('F Y');
        $invoiceNumbers = $groupedInventories
            ->map(fn($inv) => $inv->purchase->purchase_number ?? '-')
            ->filter()->unique()->implode(', ');

        // Merge items by product_id
        $mergedItems = $groupedInventories->flatMap(fn($inv) => $inv->items)
            ->groupBy('product_id')
            ->map(function ($productItems) {
                $first = $productItems->first();
                return (object)[
                    'product'               => $first->product,
                    'product_id'            => $first->product_id,
                    'unit_name'             => $first->unit_name ?? 'Pcs',
                    'unit_conversion_value' => $first->unit_conversion_value ?? 1,
                    'quantity'              => $productItems->sum('quantity'),        // kotak
                    'qty_base'              => $productItems->sum('qty_base'),        // pcs
                    'stock_in'              => $productItems->sum('stock_in'),        // pcs
                    'remaining'             => $productItems->sum('qty_base') - $productItems->sum('stock_in'), // pcs
                    'item_ids'              => $productItems->pluck('id')->toArray(),
                ];
            })->values();

        // Referensi untuk form action (pakai id inventory pertama)
        $firstInventory = $groupedInventories->first();

        return view('erp.pages.inventory.stock-in.add-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'invoiceNumbers',
            'firstInventory',
            'supplierId',
            'year',
            'month'
        ));
    }

    public function addStockInByPurchaseList($inventoryId)
    {
        $inventory = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In')
            ->whereHas('purchase', fn ($query) => $query->where('status', 'Purchase List'))
            ->findOrFail($inventoryId);

        $groupedInventories = collect([$inventory]);
        $supplier = optional($inventory->purchase->supplier);
        $monthLabel = Carbon::parse($inventory->purchase->purchase_date ?? $inventory->created_at)->format('F Y');
        $mergedItems = $this->mergeInventoryItems($groupedInventories);
        $firstInventory = $inventory;
        $supplierId = $inventory->purchase->supplier_id;
        $year = Carbon::parse($inventory->purchase->purchase_date)->year;
        $month = Carbon::parse($inventory->purchase->purchase_date)->month;
        $individualInventoryId = $inventory->id;
        $invoiceNumbers = $inventory->purchase->purchase_number ?? $inventory->purchase_number ?? '-';

        return view('erp.pages.inventory.stock-in.add-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'invoiceNumbers',
            'firstInventory',
            'supplierId',
            'year',
            'month',
            'individualInventoryId'
        ));
    }

    public function storeByPurchaseList(Request $request, $inventoryId)
    {
        $inventory = Inventory::with('purchase')->where('status', 'Stock In')->findOrFail($inventoryId);
        $submittedIds = collect($request->input('items', []))
            ->flatMap(fn ($item) => $item['inventory_item_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();
        $validIds = $inventory->items()->whereIn('id', $submittedIds)->pluck('id');

        abort_unless($submittedIds->isNotEmpty() && $submittedIds->diff($validIds)->isEmpty(), 422, 'Item Stock In tidak sesuai dengan Purchase List.');

        $date = Carbon::parse($inventory->purchase->purchase_date ?? $inventory->created_at);

        return $this->storeGrouped($request, $inventory->purchase->supplier_id, $date->year, $date->month);
    }

    public function addStockInByPurchaseOrder($purchaseOrderId)
    {
        $purchaseOrder = Purchase::where('status', 'Purchase Orders')->findOrFail($purchaseOrderId);
        $groupedInventories = $this->getPurchaseOrderInventories($purchaseOrder->id);

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier = optional($purchaseOrder->supplier);
        $monthLabel = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->format('F Y');
        $invoiceNumbers = $groupedInventories
            ->pluck('purchase.purchase_number')
            ->filter()
            ->unique()
            ->implode(', ');
        $mergedItems = $this->mergeInventoryItems($groupedInventories);
        $firstInventory = $groupedInventories->first();
        $supplierId = $purchaseOrder->supplier_id;
        $year = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->year;
        $month = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->month;
        $purchaseOrderId = $purchaseOrder->id;
        $purchaseOrderNumber = $purchaseOrder->purchase_number;

        return view('erp.pages.inventory.stock-in.add-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'invoiceNumbers',
            'firstInventory',
            'supplierId',
            'year',
            'month',
            'purchaseOrderId',
            'purchaseOrderNumber'
        ));
    }

    public function storeByPurchaseOrder(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = Purchase::where('status', 'Purchase Orders')->findOrFail($purchaseOrderId);
        $inventories = $this->getPurchaseOrderInventories($purchaseOrder->id);
        $submittedIds = collect($request->input('items', []))
            ->flatMap(fn ($item) => $item['inventory_item_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique();
        $validIds = InventoryItem::whereIn('inventory_id', $inventories->pluck('id'))
            ->whereIn('id', $submittedIds)
            ->pluck('id');

        abort_unless(
            $inventories->isNotEmpty()
                && $submittedIds->isNotEmpty()
                && $submittedIds->diff($validIds)->isEmpty(),
            422,
            'Item Stock In tidak sesuai dengan Purchase Order.'
        );

        $date = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at);

        return $this->storeGrouped($request, $purchaseOrder->supplier_id, $date->year, $date->month);
    }

    private function getPurchaseOrderInventories($purchaseOrderId)
    {
        return Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In')
            ->whereHas('purchase', function ($query) use ($purchaseOrderId) {
                $query->where('status', 'Purchase List')
                    ->where('parent_purchase_id', $purchaseOrderId);
            })
            ->get();
    }

    private function mergeInventoryItems($inventories)
    {
        return $inventories->flatMap(fn ($inventory) => $inventory->items)
            ->groupBy('product_id')
            ->map(function ($productItems) {
                $first = $productItems->first();

                return (object) [
                    'product' => $first->product,
                    'product_id' => $first->product_id,
                    'unit_name' => $first->unit_name ?? 'Pcs',
                    'unit_conversion_value' => $first->unit_conversion_value ?? 1,
                    'quantity' => $productItems->sum('quantity'),
                    'qty_base' => $productItems->sum('qty_base'),
                    'stock_in' => $productItems->sum('stock_in'),
                    'remaining' => $productItems->sum('qty_base') - $productItems->sum('stock_in'),
                    'item_ids' => $productItems->pluck('id')->toArray(),
                ];
            })->values();
    }

    private function getTotalStockForAvg($productId)
    {
        // dari inventory_stocks
        $inventoryStock = InventoryStock::where('product_id', $productId)->sum('inventory_stock');

        // dari production_stocks → available_quantity
        $productionStock = ProductionStock::where('product_id', $productId)->sum('available_quantity');

        return $inventoryStock + $productionStock;
    }

    // public function store(Request $request, $id)
    // {
    //     // dd($request->all());

    //     $items = $request->input('items', []);
    //     foreach ($items as $index => $item) {
    //         if (isset($item['stock_in'])) {
    //             // Hapus semua karakter non-numeric kecuali minus
    //             $cleaned = preg_replace('/[^0-9-]/', '', $item['stock_in']);
    //             $items[$index]['stock_in'] = (int) $cleaned;
    //         }
    //     }
    //     $request->merge(['items' => $items]);
    //     $request->validate([
    //         'inventory_id' => 'required|exists:inventories_2,id',
    //         'change_date' => 'required|date',
    //         'notes' => 'nullable',
    //         'waybill_number' => 'nullable|string',
    //         'waybill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
    //         'items' => 'required|array',
    //         'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
    //         'items.*.stock_in' => 'required|integer|min:0',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $inventory = Inventory::findOrFail($request->inventory_id);

    //         $waybillImagePath = null;
    //         if ($request->hasFile('waybill_image')) {
    //             $image = $request->file('waybill_image');
    //             $filename = time() . '_' . $image->getClientOriginalName();
    //             $image->move(public_path('uploads/waybill_image'), $filename);
    //             $waybillImagePath = 'uploads/waybill_image/' . $filename;
    //         }

    //         $stockIn = InventoryStockIn::create([
    //             'inventory_id' => $inventory->id,
    //             'invoice_number' => $inventory->note === 'Return Canceled Product to Warehouse'
    //                 ? $inventory->order_number
    //                 : $inventory->purchase_number,
    //             'change_date' => $request->change_date,
    //             'notes' => $request->notes,
    //             'waybill_number' => $request->waybill_number,
    //             'waybill_image' => $waybillImagePath,
    //             'status'         => 'Add Stock In',
    //             'user_id' => $request->user()->id,
    //         ]);

    //         foreach ($request->items as $item) {

    //             InventoryStockInHistory::create([
    //                 'inventory_stock_in_id' => $stockIn->id,
    //                 'inventory_item_id' => $item['inventory_item_id'],
    //                 'stock_in' => $item['stock_in'],
    //                 'notes'    => $item['notes'] ?? null,
    //             ]);

    //             $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);
    //             $inventoryItem->increment('stock_in', $item['stock_in']);

    //             if ($inventoryItem->purchase_item_id) {
    //                 $purchaseItem = PurchaseItem::find($inventoryItem->purchase_item_id);
    //                 if ($purchaseItem) {
    //                     $purchaseItem->increment('stock_in', $item['stock_in']);
    //                 }
    //             }

    //             $productId = $inventoryItem->product_id;

    //             $product = Products::findOrFail($productId);
    //             $previousCost = $product->avg_cost ?? 0;
    //             $previousQty = max(0, $this->getTotalStockForAvg($productId) - $item['stock_in']);

    //             $inventoryStock = InventoryStock::firstOrCreate(
    //                 [
    //                     'product_id'             => $productId,
    //                     'inventory_warehouse_id' => $inventoryItem->inventory_warehouse_id ?? 1,
    //                 ],
    //                 [
    //                     'opening_stock'     => 0,
    //                     'opening_rate'      => 0,
    //                     'inventory_stock'   => 0,
    //                     'stock_after_sales' => 0,
    //                     'incoming_stock'    => 0,
    //                     'avg_cost'          => 0,
    //                 ]
    //             );

    //             if ($inventory->purchase_id) {
    //                 $inventoryStock->decrement('incoming_stock', $item['stock_in']);
    //                 $inventoryStock->increment('inventory_stock', $item['stock_in']);
    //                 $inventoryStock->increment('stock_after_sales', $item['stock_in']);

    //                 $purchaseItem = $inventoryItem->purchaseItem ?? null;
    //                 if ($purchaseItem) {
    //                     $cost = $purchaseItem->final_price;

    //                     $newAvgCost = round(
    //                         (($previousCost * $previousQty) + ($cost * $item['stock_in']))
    //                             / max(1, $previousQty + $item['stock_in']),
    //                         3
    //                     );

    //                     $product->update(['avg_cost' => $newAvgCost]);
    //                 }
    //             } elseif ($inventory->canceled_product_id) {
    //                 $inventoryStock->increment('inventory_stock', $item['stock_in']);
    //                 $inventoryStock->increment('stock_after_sales', $item['stock_in']);

    //                 $canceledProduct = CanceledProduct::find($inventory->canceled_product_id);
    //                 $cost = $canceledProduct?->avg_cost_at_cancel ?? 0;

    //                 $newAvgCost = round(
    //                     (($previousCost * $previousQty) + ($cost * $item['stock_in']))
    //                         / max(1, $previousQty + $item['stock_in']),
    //                     3
    //                 );

    //                 $product->update(['avg_cost' => $newAvgCost]);
    //             } elseif ($inventoryItem->material_request_item_id) {
    //                 $inventoryStock->increment('inventory_stock', $item['stock_in']);
    //             } else {
    //                 $inventoryStock->increment('inventory_stock', $item['stock_in']);
    //                 $inventoryStock->increment('stock_after_sales', $item['stock_in']);
    //             }

    //             // Sync avg_cost dari product ke inventoryStock
    //             $inventoryStock->avg_cost = $product->avg_cost;
    //             $inventoryStock->save();
    //         }

    //         DB::commit();
    //         return redirect('/erp/inventory/stock-in')->with('success', 'Stock In berhasil ditambahkan');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Stock In gagal: ' . $e->getMessage());
    //     }
    // }

    public function storeGrouped(Request $request, $supplierId, $year, $month)
    {
        // Bersihkan angka dari format ribuan
        $items = $request->input('items', []);
        foreach ($items as $index => $item) {
            if (isset($item['stock_in'])) {
                $cleaned = preg_replace('/[^0-9-]/', '', $item['stock_in']);
                $items[$index]['stock_in'] = (int) $cleaned;
            }
        }
        $request->merge(['items' => $items]);

        $request->validate([
            'change_date'                        => 'required|date',
            'waybill_number'                     => 'nullable|string',
            'waybill_image'                      => UploadLimit::imageRule(),
            'items'                              => 'required|array',
            'items.*.product_id'                 => 'required|exists:products,id',
            'items.*.inventory_item_ids'         => 'required|array',
            'items.*.inventory_item_ids.*'       => 'exists:inventory_items_2,id',
            'items.*.stock_in'                   => 'required|integer|min:0',
            'items.*.unit_conversion_value' => 'required|numeric|min:1',
        ], UploadLimit::imageMessages('waybill_image') + [
            'items.required' => 'Tidak ada item yang terkirim. Ini biasanya terjadi kalau foto waybill terlalu besar sehingga seluruh form ditolak server — coba foto ulang dengan ukuran lebih kecil.',
        ]);

        DB::beginTransaction();
        try {
            $waybillImagePath = null;
            if ($request->hasFile('waybill_image')) {
                $image = $request->file('waybill_image');

                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                $uploadPath = base_path('public/uploads/waybill_image');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $filename);

                $waybillImagePath = 'uploads/waybill_image/' . $filename;
            }

            // Dipakai untuk menghitung ulang status PO induk setelah Stock In.
            $touchedPurchaseItemIds = [];

            // Produk yang batch FIFO-nya perlu dibangun ulang setelah stock in.
            $touchedProductIds = [];

            foreach ($request->items as $itemData) {
                $conv    = (float) ($itemData['unit_conversion_value'] ?? 1);
                $addQty  = (int) $itemData['stock_in'];       // input user (kotak)
                $addBase = (int) round($addQty * $conv);       // pcs

                if ($addQty <= 0) continue;

                // FIFO: urutkan inventory_item dari yang terlama (created_at ASC)
                // $inventoryItems = InventoryItem::whereIn('id', $itemData['inventory_item_ids'])
                //     ->orderBy('created_at', 'asc')
                //     ->get();

                $inventoryItems = InventoryItem::whereIn('inventory_items_2.id', $itemData['inventory_item_ids'])
                    ->join('inventories_2', 'inventory_items_2.inventory_id', '=', 'inventories_2.id')
                    ->join('purchases', 'inventories_2.purchase_id', '=', 'purchases.id')
                    ->orderBy('purchases.purchase_date', 'asc')
                    ->select('inventory_items_2.*')
                    ->get();

                $remaining = $addBase; // ← harus base, bukan $addQty

                foreach ($inventoryItems as $inventoryItem) {
                    if ($remaining <= 0) break;

                    $canAdd = $inventoryItem->qty_base - $inventoryItem->stock_in; // pcs
                    if ($canAdd <= 0) continue;

                    $toAdd = min($remaining, $canAdd);

                    // Ambil inventory induknya
                    $inventory = Inventory::findOrFail($inventoryItem->inventory_id);

                    // Buat InventoryStockIn per inventory
                    $stockIn = InventoryStockIn::create([
                        'inventory_id'   => $inventory->id,
                        'invoice_number' => $inventory->purchase_number ?? $inventory->order_number,
                        'change_date'    => $request->change_date,
                        'notes'          => $request->notes ?? null,
                        'waybill_number' => $request->waybill_number,
                        'waybill_image'  => $waybillImagePath,
                        'status'         => 'Add Stock In',
                        'user_id'        => $request->user()->id,
                    ]);

                    InventoryStockInHistory::create([
                        'inventory_stock_in_id' => $stockIn->id,
                        'inventory_item_id'     => $inventoryItem->id,
                        'stock_in'              => $toAdd,
                        'notes'                 => $itemData['notes'] ?? null,
                    ]);

                    if ($inventoryItem->purchase_item_id) {
                        $touchedPurchaseItemIds[] = $inventoryItem->purchase_item_id;

                        $purchaseItem = PurchaseItem::find($inventoryItem->purchase_item_id);
                        if ($purchaseItem) {
                            $purchaseItem->increment('stock_in', $toAdd);
                        }
                    }

                    // === Logika avg_cost & inventory stock (tidak diubah dari store() lama) ===
                    $productId    = $inventoryItem->product_id;
                    $product      = Products::findOrFail($productId);
                    $previousCost = $product->avg_cost ?? 0;
                    $previousQty  = max(0, $this->getTotalStockForAvg($productId));

                    $inventoryItem->increment('stock_in', $toAdd);

                    $inventoryStock = InventoryStock::firstOrCreate(
                        [
                            'product_id'             => $productId,
                            'inventory_warehouse_id' => $inventoryItem->inventory_warehouse_id ?? 1,
                        ],
                        [
                            'opening_stock'     => 0,
                            'opening_rate'      => 0,
                            'inventory_stock'   => 0,
                            'stock_after_sales' => 0,
                            'incoming_stock'    => 0,
                            'avg_cost'          => 0,
                        ]
                    );

                    if ($inventory->purchase_id) {
                        $inventoryStock->decrement('incoming_stock', $toAdd);
                        $inventoryStock->increment('inventory_stock', $toAdd);
                        $inventoryStock->increment('stock_after_sales', $toAdd);

                        // Harga modal diserahkan ke FifoCostService setelah loop:
                        // stock in inilah yang melahirkan batch FIFO barunya.
                        //
                        // Rumus lama di sini juga salah satuan: final_price adalah
                        // harga per satuan BELI (mis. per Dus), sedangkan $toAdd
                        // dihitung dalam satuan dasar (pcs), jadi keduanya tidak
                        // sebanding dan avg_cost-nya melar sebesar isi per dus.
                        $touchedProductIds[] = $productId;
                    } elseif ($inventory->canceled_product_id) {
                        $inventoryStock->increment('inventory_stock', $toAdd);
                        $inventoryStock->increment('stock_after_sales', $toAdd);

                        $canceledProduct = CanceledProduct::find($inventory->canceled_product_id);
                        $cost            = $canceledProduct?->avg_cost_at_cancel ?? 0;
                        $newAvgCost      = round(
                            (($previousCost * $previousQty) + ($cost * $toAdd))
                                / max(1, $previousQty + $toAdd),
                            3
                        );
                        $product->update(['avg_cost' => $newAvgCost]);
                    } elseif ($inventoryItem->material_request_item_id) {
                        $inventoryStock->increment('inventory_stock', $toAdd);
                    } else {
                        $inventoryStock->increment('inventory_stock', $toAdd);
                        $inventoryStock->increment('stock_after_sales', $toAdd);
                    }

                    $inventoryStock->avg_cost = $product->avg_cost;
                    $inventoryStock->save();
                    // === End logika avg_cost ===

                    $remaining -= $toAdd;
                }
            }

            // Batch FIFO lahir dari stock in, jadi dibangun sekarang juga.
            // Tanpa ini, batch baru tidak muncul di layar HPP sampai cron malam
            // atau rebuild manual dijalankan.
            if ($touchedProductIds !== []) {
                app(FifoCostService::class)->rebuild(array_values(array_unique($touchedProductIds)));
            }

            // PO induk jadi "Completed" hanya kalau semua PL-nya sudah stock in penuh.
            Purchase::syncApprovalProgressFromPurchaseItems($touchedPurchaseItemIds);

            DB::commit();
            return redirect('/erp/inventory/stock-in')->with('success', 'Stock In berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Stock In gagal: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $inventory = Inventory::with('items.product')->findOrFail($id);

        return view('erp.pages.inventory.stock-in.edit-stock-in', compact('inventory'));
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::with('items')->findOrFail($id);

        $request->validate([
            'change_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
            'items.*.stock_in' => 'required|integer|min:0',
            'waybill_number' => 'nullable|string',
            'waybill_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        // kalau ada upload waybill
        $waybillImagePath = null;
        if ($request->hasFile('waybill_image')) {
            $waybillImagePath = $request->file('waybill_image')->store('waybills', 'public');
        }

        // update header inventory utama
        $inventory->update([
            'date' => $request->change_date,
            'notes' => $request->notes,
        ]);

        // buat header baru di inventory_stock_ins
        $stockOut = InventoryStockIn::create([
            'inventory_id'   => $inventory->id,
            'invoice_number' => $inventory->note === 'Sale Returns'
                ? $inventory->order_number
                : $inventory->purchase_number,
            'change_date'    => $request->change_date,
            'notes'          => $request->notes,
            'waybill_number' => $request->waybill_number,
            'waybill_image'  => $waybillImagePath,
            'status'         => 'Edit Stock In',
            'user_id'        => $request->user()->id,
        ]);

        $touchedPurchaseItemIds = [];

        foreach ($request->items as $item) {
            $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);
            $touchedPurchaseItemIds[] = $inventoryItem->purchase_item_id;

            $oldValue = $inventoryItem->stock_in;
            $newValue = (int) $item['stock_in'];
            $diff     = $newValue - $oldValue; // selisih

            if ($newValue != $oldValue) {
                InventoryStockInHistory::create([
                    'inventory_stock_in_id' => $stockOut->id,
                    'inventory_item_id'      => $inventoryItem->id,
                    'stock_in'              => $newValue, // bukan diff
                    'change_date'            => $request->change_date,
                    'user_id'                => $request->user()->id,
                ]);

                // overwrite total
                $inventoryItem->update([
                    'stock_in' => $newValue,
                ]);

                // update InventoryStock
                $productId = $inventoryItem->product_id;
                $inventoryStock = InventoryStock::firstOrCreate(
                    [
                        'product_id'             => $productId,
                        'inventory_warehouse_id' => $inventoryItem->inventory_warehouse_id ?? 2,
                    ],
                    [
                        'opening_stock'     => 0,
                        'opening_rate'      => 0,
                        'inventory_stock'   => 0,
                        'stock_after_sales' => 0,
                        'incoming_stock'    => 0,
                        'avg_cost'          => 0,
                    ]
                );

                if ($inventory->purchase_id) {
                    // ✅ Update stok dari Purchase
                    $inventoryStock->increment('inventory_stock', $diff);
                    $inventoryStock->increment('stock_after_sales', $diff);
                    $inventoryStock->decrement('incoming_stock', $diff);
                } else {
                    // ❌ Dari Canceled Product / manual
                    $inventoryStock->increment('inventory_stock', $diff);
                    $inventoryStock->increment('stock_after_sales', $diff);
                }

                // hitung ulang avg_cost
                $product = Products::findOrFail($productId);
                // ProductCostService::updateCostAndStock($product);
            }
        }

        Purchase::syncApprovalProgressFromPurchaseItems($touchedPurchaseItemIds);

        return redirect('/erp/inventory/stock-in')
            ->with('success', 'Inventory Stock Out berhasil diupdate');
    }

    // public function getHistory($id)
    // {
    //     $stockIn = Inventory::with([
    //         'items.product',
    //         'items.inventoryStockIn.inventoryItem',
    //     ])->findOrFail($id);

    //     return view('erp.pages.inventory.stock-in.history-stock-in', compact('stockIn'));
    // }

    public function getHistory($supplierId, $year, $month)
    {
        $groupedInventories = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->whereHas('purchase', function ($q) use ($year, $month) {
                $q->whereYear('purchase_date', $year)
                    ->whereMonth('purchase_date', $month);
            })
            ->get();

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier   = optional($groupedInventories->first()->purchase->supplier);
        $monthLabel = Carbon::createFromDate($year, $month, 1)->format('F Y');
        $inventoryIds = $groupedInventories->pluck('id')->toArray();

        // Merge items by product untuk tabel Products
        $mergedItems = $groupedInventories->flatMap(fn($inv) => $inv->items)
            ->groupBy('product_id')
            ->map(function ($productItems) {
                return (object)[
                    'product'   => $productItems->first()->product,
                    'quantity'  => $productItems->sum('quantity'),
                    'stock_in'  => $productItems->sum('stock_in'),
                ];
            })->values();

        return view('erp.pages.inventory.stock-in.history-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'inventoryIds',
            'supplierId',
            'year',
            'month'
        ));
    }

    public function getHistoryByPurchaseList($inventoryId)
    {
        $inventory = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In')
            ->whereHas('purchase', fn ($query) => $query->where('status', 'Purchase List'))
            ->findOrFail($inventoryId);

        $groupedInventories = collect([$inventory]);
        $supplier = optional($inventory->purchase->supplier);
        $monthLabel = Carbon::parse($inventory->purchase->purchase_date ?? $inventory->created_at)->format('F Y');
        $inventoryIds = [$inventory->id];
        $supplierId = $inventory->purchase->supplier_id;
        $year = Carbon::parse($inventory->purchase->purchase_date)->year;
        $month = Carbon::parse($inventory->purchase->purchase_date)->month;
        $individualInventoryId = $inventory->id;
        $invoiceNumbers = $inventory->purchase->purchase_number ?? $inventory->purchase_number ?? '-';
        $mergedItems = $this->mergeInventoryItems($groupedInventories);

        return view('erp.pages.inventory.stock-in.history-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'inventoryIds',
            'supplierId',
            'year',
            'month',
            'individualInventoryId',
            'invoiceNumbers'
        ));
    }

    public function dataHistoryByPurchaseList(Request $request, $inventoryId)
    {
        $inventory = Inventory::with('purchase')->where('status', 'Stock In')->findOrFail($inventoryId);
        $date = Carbon::parse($inventory->purchase->purchase_date ?? $inventory->created_at);
        $request->merge(['individual_inventory_id' => $inventory->id]);

        return $this->dataHistory($request, $inventory->purchase->supplier_id, $date->year, $date->month);
    }

    public function getHistoryByPurchaseOrder($purchaseOrderId)
    {
        $purchaseOrder = Purchase::where('status', 'Purchase Orders')->findOrFail($purchaseOrderId);
        $groupedInventories = $this->getPurchaseOrderInventories($purchaseOrder->id);

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier = optional($purchaseOrder->supplier);
        $monthLabel = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->format('F Y');
        $inventoryIds = $groupedInventories->pluck('id')->all();
        $supplierId = $purchaseOrder->supplier_id;
        $year = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->year;
        $month = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at)->month;
        $purchaseOrderId = $purchaseOrder->id;
        $purchaseOrderNumber = $purchaseOrder->purchase_number;
        $invoiceNumbers = $groupedInventories
            ->pluck('purchase.purchase_number')
            ->filter()
            ->unique()
            ->implode(', ');
        $mergedItems = $this->mergeInventoryItems($groupedInventories);

        return view('erp.pages.inventory.stock-in.history-stock-in', compact(
            'mergedItems',
            'supplier',
            'monthLabel',
            'inventoryIds',
            'supplierId',
            'year',
            'month',
            'purchaseOrderId',
            'purchaseOrderNumber',
            'invoiceNumbers'
        ));
    }

    public function dataHistoryByPurchaseOrder(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = Purchase::where('status', 'Purchase Orders')->findOrFail($purchaseOrderId);
        $date = Carbon::parse($purchaseOrder->purchase_date ?? $purchaseOrder->created_at);
        $request->merge(['purchase_order_id' => $purchaseOrder->id]);

        return $this->dataHistory($request, $purchaseOrder->supplier_id, $date->year, $date->month);
    }

    public function dataHistory(Request $request, $supplierId, $year, $month)
    {
        $inventoryIds = Inventory::where('status', 'Stock In')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->whereHas('purchase', function ($q) use ($year, $month) {
                $q->whereYear('purchase_date', $year)
                    ->whereMonth('purchase_date', $month);
            })
            ->pluck('id');

        if ($request->filled('individual_inventory_id')) {
            $inventoryIds = collect([(int) $request->individual_inventory_id]);
        } elseif ($request->filled('purchase_order_id')) {
            $inventoryIds = Inventory::where('status', 'Stock In')
                ->whereHas('purchase', function ($query) use ($request) {
                    $query->where('status', 'Purchase List')
                        ->where('parent_purchase_id', $request->integer('purchase_order_id'));
                })
                ->pluck('id');
        }

        $stockIn = InventoryStockIn::with(['user', 'histories.inventoryItem.product'])
            ->whereIn('inventory_id', $inventoryIds)
            ->latest();

        if ($request->start_date && $request->end_date) {
            $stockIn->whereBetween('change_date', [$request->start_date, $request->end_date]);
        }

        return DataTables::of($stockIn)
            ->addIndexColumn()
            ->addColumn('invoice_number', function ($stockIn) {
                $html = '';
                if (strtolower($stockIn->status) === 'add stock in') {
                    $html .= '<div class="badge bg-soft-primary text-primary mb-1">Add Stock In</div><br>';
                } elseif (strtolower($stockIn->status) === 'edit stock in') {
                    $html .= '<div class="badge bg-soft-danger text-danger mb-1">Edit Stock Out</div><br>';
                }
                $html .= $stockIn->invoice_number;
                return $html;
            })
            ->addColumn('change_date', function ($stockIn) {
                return Carbon::parse($stockIn->created_at)->format('j M y H:i:s');
            })
            ->addColumn('user_name', function ($stockIn) {
                return $stockIn->user->name;
            })
            ->addColumn('waybill_number', function ($stockIn) {
                return $stockIn->waybill_number;
            })
            ->addColumn('waybill_image', function ($stockIn) {
                if ($stockIn->waybill_image) {
                    $imageUrl = asset($stockIn->waybill_image);

                    return '<a href="' . $imageUrl . '" data-lightbox="waybill-' . $stockIn->id . '">
                        <div style="
                            width: 90px;
                            aspect-ratio: 16 / 9;
                            overflow: hidden;
                            border-radius: 6px;
                            background: #f5f5f5;
                        ">
                            <img src="' . $imageUrl . '" alt="Waybill Image" style="
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                display: block;
                            ">
                        </div>
                    </a>';
                }

                return '-';
            })
            ->addColumn('stock_in', function ($stockIn) {
                return view('erp.pages.inventory.stock-in.partials.product-stock-in-history', [
                    "items" => $stockIn->histories
                ])->render();
            })
            ->rawColumns(['invoice_number', 'waybill_image', 'stock_in'])
            ->make(true);
    }

    // public function dataHistory(Request $request, $id)
    // {
    //     $stockIn = InventoryStockIn::with(['user', 'histories.inventoryItem.product'])
    //         ->where('inventory_id', $id)->latest();

    //     if ($request->start_date && $request->end_date) {
    //         $stockIn->whereBetween('change_date', [$request->start_date, $request->end_date]);
    //     }

    //     return DataTables::of($stockIn)
    //         ->addIndexColumn()
    //         ->addColumn('invoice_number', function ($stockIn) {
    //             $html = '';
    //             if (strtolower($stockIn->status) === 'add stock in') {
    //                 $html .= '<div class="badge bg-soft-primary text-primary mb-1">Add Stock In</div><br>';
    //             } elseif (strtolower($stockIn->status) === 'edit stock in') {
    //                 $html .= '<div class="badge bg-soft-danger text-danger mb-1">Edit Stock Out</div><br>';
    //             }
    //             $html .= $stockIn->invoice_number;
    //             return $html;
    //         })
    //         ->addColumn('change_date', function ($stockIn) {
    //             return Carbon::parse($stockIn->created_at)->format('j M y H:i:s');
    //         })
    //         ->addColumn('user_name', function ($stockIn) {
    //             return $stockIn->user->name;
    //         })
    //         ->addColumn('waybill_number', function ($stockIn) {
    //             return $stockIn->waybill_number;
    //         })
    //         ->addColumn('waybill_image', function ($stockIn) {
    //             if ($stockIn->waybill_image) {
    //                 $imageUrl = asset($stockIn->waybill_image);
    //                 return '<a href="' . $imageUrl . '" data-lightbox="waybill-' . $stockIn->id . '">
    //                 <img src="' . $imageUrl . '" alt="Waybill Image" class="img-fluid" style="max-width: 60px;">
    //             </a>';
    //             }
    //             return '-';
    //         })
    //         ->addColumn('stock_in', function ($stockIn) {
    //             return view('erp.pages.inventory.stock-in.partials.product-stock-in-history', [
    //                 "items" => $stockIn->histories
    //             ])->render();
    //         })
    //         ->rawColumns(['invoice_number', 'waybill_image', 'stock_in'])
    //         ->make(true);
    // }

    // public function updateHistoryItem(Request $request, $id)
    // {
    //     $request->validate([
    //         'quantity' => 'required|numeric|min:0',
    //         'notes' => 'nullable|string'
    //     ]);

    //     // 🔹 Ambil data history lama
    //     $history = InventoryStockInHistory::findOrFail($id);

    //     // 🔹 Dapatkan inventory item terkait
    //     $inventoryItem = $history->inventoryItem;

    //     if (!$inventoryItem) {
    //         return response()->json([
    //             'message' => 'Data inventory item tidak ditemukan.'
    //         ], 404);
    //     }

    //     // 🔹 Hitung selisih
    //     $oldQty = $history->stock_in;
    //     $newQty = $request->quantity;
    //     $diff = $newQty - $oldQty;

    //     // 🔹 Update kolom stock_in di inventory_items
    //     // jika diff positif → increment, negatif → decrement
    //     $inventoryItem->stock_in += $diff;
    //     if ($inventoryItem->stock_in < 0) {
    //         $inventoryItem->stock_in = 0; // jaga-jaga tidak minus
    //     }
    //     $inventoryItem->save();

    //     // 🔹 Update data history
    //     $history->update([
    //         'stock_in' => $newQty,
    //         'notes' => $request->notes,
    //     ]);

    //     return response()->json(['message' => 'History item dan stok berhasil diperbarui.']);
    // }

    public function updateHistoryItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Ambil history
        $history = InventoryStockInHistory::findOrFail($id);

        // Ambil inventory item
        $inventoryItem = $history->inventoryItem; // RELASI YANG BENAR

        if (!$inventoryItem) {
            return response()->json(['message' => 'Inventory item tidak ditemukan'], 404);
        }

        // Ambil inventory stock berdasarkan product_id + warehouse_id
        $inventoryStock = InventoryStock::where('product_id', $inventoryItem->product_id)
            ->where('inventory_warehouse_id', $inventoryItem->inventory_warehouse_id)
            ->first();

        if (!$inventoryStock) {
            return response()->json(['message' => 'Inventory stock tidak ditemukan'], 404);
        }

        // Hitung selisih
        $oldQty = $history->stock_in;
        $newQty = $request->quantity;
        $diff   = $newQty - $oldQty;

        // Update inventory_item.stock_in
        $inventoryItem->stock_in += $diff;
        if ($inventoryItem->stock_in < 0) $inventoryItem->stock_in = 0;
        $inventoryItem->save();

        // Update inventory_stocks
        $inventoryStock->inventory_stock += $diff;
        $inventoryStock->stock_after_sales += $diff;

        if ($inventoryStock->inventory_stock < 0) $inventoryStock->inventory_stock = 0;
        if ($inventoryStock->stock_after_sales < 0) $inventoryStock->stock_after_sales = 0;

        // Hitung avg cost
        $price = $inventoryStock->price;
        $prevQty = max(0, $inventoryStock->stock_after_sales - $diff);
        $prevCost = $inventoryStock->avg_cost;

        if (($prevQty + $newQty) > 0) {
            $inventoryStock->avg_cost =
                (($prevQty * $prevCost) + ($newQty * $price)) / ($prevQty + $newQty);
        }

        $inventoryStock->save();

        // Update history
        $history->update([
            'stock_in' => $newQty,
            'notes'    => $request->notes,
        ]);

        Purchase::syncApprovalProgressFromPurchaseItems([$inventoryItem->purchase_item_id]);

        return response()->json(['message' => 'History berhasil diperbarui']);
    }
}
