<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefectProduct;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryStockOut;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockOutHistory;
use App\Models\InventoryStockInHistory;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\MaterialRequestItemHistory;
use App\Models\OrderItem;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Services\ProductCostService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HistoryStockOutController extends Controller
{
    public function addStockOut($id)
    {
        $stockOut = Inventory::with('items.product')->findOrFail($id);

        return view('erp.pages.inventory.stock-out.add-stock-out', compact('stockOut'));
    }

    private function calculateCurrentStock($productId)
    {
        // Total stock in
        $stockIn = InventoryStockInHistory::whereHas('inventoryItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })->sum('stock_in');

        // Total stock out
        $stockOut = InventoryStockOutHistory::whereHas('inventoryItem', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })->sum('stock_out');

        // Ambil opening_stock dari tabel products
        $openingStock = Products::where('id', $productId)->value('opening_stock');

        return $openingStock + ($stockIn - $stockOut);
    }

    // public function store(Request $request, $id)
    // {
    //     $request->validate([
    //         'inventory_id' => 'required|exists:inventories_2,id',
    //         'change_date'  => 'required|date',
    //         'notes'        => 'nullable',
    //         'waybill_number' => 'nullable|string',
    //         'waybill_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'items' => 'required|array',
    //         'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
    //         'items.*.stock_out'         => 'required|integer|min:1',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $inventory = Inventory::findOrFail($request->inventory_id);

    //         // Upload waybill jika ada
    //         $waybillImagePath = null;
    //         if ($request->hasFile('waybill_image')) {
    //             $image = $request->file('waybill_image');
    //             $filename = time() . '_' . $image->getClientOriginalName();
    //             $image->move(public_path('uploads/waybill_image'), $filename);
    //             $waybillImagePath = 'uploads/waybill_image/' . $filename;
    //         }

    //         // Simpan header Stock Out
    //         $stockOut = InventoryStockOut::create([
    //             'inventory_id'    => $inventory->id,
    //             'invoice_number'  => $inventory->purchase_return_id
    //                 ? $inventory->purchase_number
    //                 : $inventory->order_number,
    //             'change_date'     => $request->change_date,
    //             'notes'           => $request->notes,
    //             'waybill_number'  => $request->waybill_number,
    //             'waybill_image'   => $waybillImagePath,
    //             'status'          => 'Add Stock Out',
    //             'user_id'         => $request->user()->id,
    //         ]);

    //         $touchedProducts = [];

    //         foreach ($request->items as $item) {
    //             $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

    //             // Simpan detail Stock Out
    //             InventoryStockOutHistory::create([
    //                 'inventory_stock_out_id' => $stockOut->id,
    //                 'inventory_item_id'      => $item['inventory_item_id'],
    //                 'stock_out'              => $item['stock_out'],
    //             ]);

    //             // Update inventory item
    //             $inventoryItem->increment('stock_out', $item['stock_out']);

    //             // Jika ada material request item
    //             if ($inventoryItem->material_request_item_id) {
    //                 $materialRequestItem = MaterialRequestItem::find($inventoryItem->material_request_item_id);
    //                 if ($materialRequestItem) {
    //                     $materialRequestItem->increment('issued_qty', $item['stock_out']);

    //                     MaterialRequestItemHistory::create([
    //                         'material_request_item_id' => $materialRequestItem->id,
    //                         'quantity' => $item['stock_out'],
    //                         'date'     => now()->format('Y-m-d'),
    //                         'status'   => 'pending',
    //                         'note'     => 'Stock Out #' . $stockOut->id,
    //                     ]);
    //                 }
    //             }

    //             // Jika ada purchase return item
    //             if ($inventoryItem->purchase_return_item_id) {
    //                 $purchaseReturnItem = PurchaseReturnItem::find($inventoryItem->purchase_return_item_id);
    //                 if ($purchaseReturnItem) {
    //                     $purchaseReturnItem->increment('stock_out', $item['stock_out']);
    //                 }
    //             }

    //             // Update ke InventoryStock
    //             $productId = $inventoryItem->product_id;
    //             $inventoryStock = InventoryStock::firstOrCreate(
    //                 [
    //                     'product_id'             => $productId,
    //                     'inventory_warehouse_id' => $inventoryItem->inventory_warehouse_id ?? 1,
    //                 ],
    //                 [
    //                     'opening_stock'   => 0,
    //                     'opening_rate'    => 0,
    //                     'inventory_stock' => 0,
    //                     'incoming_stock'  => 0,
    //                     'avg_cost'        => 0,
    //                 ]
    //             );

    //             // Kurangi stok fisik
    //             $inventoryStock->decrement('inventory_stock', $item['stock_out']);

    //             // Catat produk yang terdampak
    //             $touchedProducts[] = $productId;
    //         }

    //         // ✅ Recalculate avg_cost hanya jika stock out berasal dari Purchase Return
    //         if ($inventory->purchase_return_id) {
    //             foreach (array_unique($touchedProducts) as $pid) {
    //                 if ($product = Products::find($pid)) {
    //                     ProductCostService::updateCostAndStock($product);
    //                 }
    //             }
    //         }

    //         // ✅ Jika berasal dari Material Request → ubah warehouse_status jika semua item sudah issued
    //         if ($inventory->material_request_id) {
    //             $materialRequest = MaterialRequest::with('items')->find($inventory->material_request_id);

    //             if ($materialRequest) {
    //                 // Cek apakah semua item sudah terpenuhi
    //                 $allIssued = $materialRequest->items->every(function ($item) {
    //                     return $item->issued_qty >= $item->requested_qty;
    //                 });

    //                 if ($allIssued) {
    //                     $materialRequest->update(['warehouse_status' => 'Verified']);
    //                 } else {
    //                     $materialRequest->update(['warehouse_status' => 'Partial']);
    //                 }
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/inventory/stock-out')->with('success', 'Stock Out berhasil ditambahkan');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Stock Out gagal: ' . $e->getMessage());
    //     }
    // }

    public function store(Request $request, $id)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventories_2,id',
            'change_date'  => 'required|date',
            'notes'        => 'nullable',
            'waybill_number' => 'nullable|string',
            'waybill_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
            'items.*.stock_out'         => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($request->inventory_id);

            // Upload waybill jika ada
            $waybillImagePath = null;
            if ($request->hasFile('waybill_image')) {
                $image = $request->file('waybill_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/waybill_image'), $filename);
                $waybillImagePath = 'uploads/waybill_image/' . $filename;
            }

            // Simpan header Stock Out
            $stockOut = InventoryStockOut::create([
                'inventory_id'    => $inventory->id,
                'invoice_number'  => $inventory->purchase_return_id
                    ? $inventory->purchase_number
                    : $inventory->order_number,
                'change_date'     => $request->change_date,
                'notes'           => $request->notes,
                'waybill_number'  => $request->waybill_number,
                'waybill_image'   => $waybillImagePath,
                'status'          => 'Add Stock Out',
                'user_id'         => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

                // Simpan detail Stock Out
                InventoryStockOutHistory::create([
                    'inventory_stock_out_id' => $stockOut->id,
                    'inventory_item_id'      => $item['inventory_item_id'],
                    'stock_out'              => $item['stock_out'],
                ]);

                // Update inventory item
                $inventoryItem->increment('stock_out', $item['stock_out']);

                // Material Request (produksi)
                if ($inventoryItem->material_request_item_id) {
                    $materialRequestItem = MaterialRequestItem::find($inventoryItem->material_request_item_id);
                    if ($materialRequestItem) {
                        $materialRequestItem->increment('issued_qty', $item['stock_out']);

                        MaterialRequestItemHistory::create([
                            'material_request_item_id' => $materialRequestItem->id,
                            'quantity' => $item['stock_out'],
                            'date'     => now()->format('Y-m-d'),
                            'status'   => 'pending',
                            'note'     => 'Stock Out #' . $stockOut->id,
                        ]);
                    }
                }

                // Purchase Return
                if ($inventoryItem->purchase_return_item_id) {
                    $purchaseReturnItem = PurchaseReturnItem::find($inventoryItem->purchase_return_item_id);
                    if ($purchaseReturnItem) {
                        $purchaseReturnItem->increment('stock_out', $item['stock_out']);
                    }
                }

                // --- Update ke InventoryStock ---
                $productId = $inventoryItem->product_id;
                $inventoryStock = InventoryStock::firstOrCreate(
                    [
                        'product_id'             => $productId,
                        'inventory_warehouse_id' => $inventoryItem->inventory_warehouse_id ?? 1,
                    ],
                    [
                        'opening_stock'   => 0,
                        'opening_rate'    => 0,
                        'inventory_stock' => 0,
                        'incoming_stock'  => 0,
                        'avg_cost'        => 0,
                    ]
                );

                if ($inventory->purchase_return_id) {
                    $purchaseReturnItem = $inventoryItem->purchaseReturnItem ?? null;
                    if ($purchaseReturnItem) {
                        $returnCost = $purchaseReturnItem->price + $purchaseReturnItem->freight;

                        // Hitung qty sebelum pengeluaran
                        $productionQty = \App\Models\ProductionStock::where('product_id', $productId)
                            ->sum('available_quantity');

                        $previousQty  = max(0, $inventoryStock->inventory_stock + $productionQty);
                        $previousCost = $inventoryStock->avg_cost;

                        // Weighted average seperti di stock in (tapi arah minus)
                        $inventoryStock->avg_cost = round(
                            (($previousCost * $previousQty) - ($returnCost * $item['stock_out']))
                                / max(1, $previousQty - $item['stock_out']),
                            2
                        );
                    }
                }

                $inventoryStock->decrement('inventory_stock', $item['stock_out']);
                $inventoryStock->decrement('stock_after_sales', $item['stock_out']);
                $inventoryStock->save();

                // === Sinkronkan ke tabel products ===
                Products::where('id', $productId)->update([
                    'avg_cost' => $inventoryStock->avg_cost,
                ]);

                // === DEFECT PRODUCT ===
                if ($inventory->purchase_return_id) {
                    $purchaseReturn = $inventory->purchaseReturn;
                    $purchase = $inventory->purchase;

                    DefectProduct::create([
                        'product_id'              => $productId,
                        'purchase_id'             => $purchase->id ?? null,
                        'purchase_return_id'      => $purchaseReturn->id ?? null,
                        'supplier_id'             => $purchaseReturn->supplier_id ?? null,
                        'inventory_id'            => $inventory->id,
                        'inventory_stock_out_id'  => $stockOut->id,
                        'inventory_item_id'       => $inventoryItem->id,
                        'defect_date'             => $request->change_date,
                        'quantity'                => $item['stock_out'],
                        'defect_type'             => 'rusak supplier',
                        'status'                  => 'pending',
                        'note'                    => $request->notes ?? 'Stock Out defect (Purchase Return)',
                        'user_id'                 => $request->user()->id,
                    ]);
                }
            }

            // ✅ Update status material request (kalau dari produksi)
            if ($inventory->material_request_id) {
                $materialRequest = MaterialRequest::with('items')->find($inventory->material_request_id);

                if ($materialRequest) {
                    $allIssued = $materialRequest->items->every(function ($item) {
                        return $item->issued_qty >= $item->requested_qty;
                    });

                    $materialRequest->update([
                        'warehouse_status' => $allIssued ? 'Verified' : 'Partial',
                    ]);
                }
            }

            DB::commit();
            return redirect('/erp/inventory/stock-out')->with('success', 'Stock Out berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Stock Out gagal: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $inventory = Inventory::with('items.product')->findOrFail($id);

        return view('erp.pages.inventory.stock-out.edit-stock-out', compact('inventory'));
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::with('items')->findOrFail($id);

        $request->validate([
            'change_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
            'items.*.stock_out' => 'required|integer|min:0',
            'waybill_number' => 'nullable|string',
            'waybill_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        // kalau ada upload waybill
        $waybillImagePath = null;
        if ($request->hasFile('waybill_image')) {
            $waybillImagePath = $request->file('waybill_image')->store('waybills', 'public');
        }

        // update header inventory
        $inventory->update([
            'date' => $request->change_date,
            'notes' => $request->notes,
        ]);

        // buat header baru di inventory_stock_outs
        $stockOut = InventoryStockOut::create([
            'inventory_id'   => $inventory->id,
            'invoice_number' => $inventory->note === 'Purchase Returns'
                ? $inventory->purchase_number
                : $inventory->order_number,
            'change_date'    => $request->change_date,
            'notes'          => $request->notes,
            'waybill_number' => $request->waybill_number,
            'waybill_image'  => $waybillImagePath,
            'status'         => 'Edit Stock Out',
            'user_id'        => $request->user()->id,
        ]);

        foreach ($request->items as $item) {
            $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

            $oldQty = (int) $inventoryItem->stock_out;
            $newQty = (int) $item['stock_out'];
            $diff   = $newQty - $oldQty; // kalau positif = tambah keluar, kalau negatif = kembalikan stok

            // overwrite qty lama dengan yg baru
            $inventoryItem->update([
                'stock_out' => $newQty,
            ]);

            // update stok produk
            $productId = $inventoryItem->product_id;
            $currentStock = $this->calculateCurrentStock($productId);

            $product = Products::findOrFail($productId);
            $product->inventory_stock = $currentStock;
            $product->save();

            ProductCostService::updateCostAndStock($product);

            // update Material Request Item (kalau ada)
            if ($inventoryItem->material_request_item_id) {
                $materialRequestItem = MaterialRequestItem::find($inventoryItem->material_request_item_id);
                if ($materialRequestItem) {
                    $materialRequestItem->issued_qty += $diff;
                    $materialRequestItem->save();
                }
            }

            // update Production Stock
            $productionStock = ProductionStock::where('product_id', $inventoryItem->product_id)->first();
            if ($productionStock) {
                $productionStock->available_quantity += $diff;
                $productionStock->save();
            }

            InventoryStockOutHistory::create([
                'inventory_stock_out_id' => $stockOut->id,
                'inventory_item_id'      => $item['inventory_item_id'],
                'stock_out'              => $newQty,
            ]);
        }

        return redirect('/erp/inventory/stock-out')
            ->with('success', 'Inventory Stock Out berhasil diupdate');
    }

    public function getHistory($id)
    {
        $stockOut = Inventory::with([
            'items.product',
            'items.inventoryStockOut.inventoryItem',
        ])->findOrFail($id);

        return view('erp.pages.inventory.stock-out.history-stock-out', compact('stockOut'));
    }

    public function dataHistory(Request $request, $id)
    {
        $stockIn = InventoryStockOut::with(['user', 'histories.inventoryItem.product'])
            ->where('inventory_id', $id)->latest();

        if ($request->start_date && $request->end_date) {
            $stockIn->whereBetween('change_date', [$request->start_date, $request->end_date]);
        }

        return DataTables::of($stockIn)
            ->addIndexColumn()
            ->addColumn('invoice_number', function ($stockIn) {
                $html = '';
                if (strtolower($stockIn->status) === 'add stock out') {
                    $html .= '<div class="badge bg-soft-primary text-primary mb-1">Add Stock In</div><br>';
                } elseif (strtolower($stockIn->status) === 'edit stock out') {
                    $html .= '<div class="badge bg-soft-danger text-danger mb-1">Edit Stock Out</div><br>';
                }
                $html .= $stockIn->invoice_number;
                return $html;
            })
            ->addColumn('change_date', function ($stockIn) {
                return Carbon::parse($stockIn->change_date)->format('j M y');
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
                    <img src="' . $imageUrl . '" alt="Waybill Image" class="img-fluid" style="max-width: 60px;">
                </a>';
                }
                return '-';
            })
            ->addColumn('stock_out', function ($stockIn) {
                return view('erp.pages.inventory.stock-out.partials.product-stock-out-history', [
                    "items" => $stockIn->histories
                ])->render();
            })
            ->rawColumns(['invoice_number', 'waybill_image', 'stock_out'])
            ->make(true);
    }
}
