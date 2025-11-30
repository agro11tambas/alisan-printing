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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoryStockInController extends Controller
{
    public function addStockIn($id)
    {
        $stockIn = Inventory::with('items.product')->findOrFail($id);

        return view('erp.pages.inventory.stock-in.add-stock-in', compact('stockIn'));
    }

    private function getTotalStockForAvg($productId)
    {
        // dari inventory_stocks
        $inventoryStock = InventoryStock::where('product_id', $productId)->sum('inventory_stock');

        // dari production_stocks → available_quantity
        $productionStock = ProductionStock::where('product_id', $productId)->sum('available_quantity');

        return $inventoryStock + $productionStock;
    }

    public function store(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'inventory_id' => 'required|exists:inventories_2,id',
            'change_date' => 'required|date',
            'notes' => 'nullable',
            'waybill_number' => 'nullable|string',
            'waybill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'items' => 'required|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items_2,id',
            'items.*.stock_in' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($request->inventory_id);

            $waybillImagePath = null;
            if ($request->hasFile('waybill_image')) {
                $image = $request->file('waybill_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/waybill_image'), $filename);
                $waybillImagePath = 'uploads/waybill_image/' . $filename;
            }

            $stockIn = InventoryStockIn::create([
                'inventory_id' => $inventory->id,
                'invoice_number' => $inventory->note === 'Return Canceled Product to Warehouse'
                    ? $inventory->order_number
                    : $inventory->purchase_number,
                'change_date' => $request->change_date,
                'notes' => $request->notes,
                'waybill_number' => $request->waybill_number,
                'waybill_image' => $waybillImagePath,
                'status'         => 'Add Stock In',
                'user_id' => $request->user()->id,
            ]);

            foreach ($request->items as $item) {

                InventoryStockInHistory::create([
                    'inventory_stock_in_id' => $stockIn->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'stock_in' => $item['stock_in'],
                    'notes'    => $item['notes'] ?? null,
                ]);

                $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);
                $inventoryItem->increment('stock_in', $item['stock_in']);

                if ($inventoryItem->purchase_item_id) {
                    $purchaseItem = PurchaseItem::find($inventoryItem->purchase_item_id);
                    if ($purchaseItem) {
                        $purchaseItem->increment('stock_in', $item['stock_in']);
                    }
                }

                // --- Update InventoryStock ---
                $productId = $inventoryItem->product_id;

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
                    // Barang dari Purchase List → update stok fisik dan hapus dari incoming
                    $inventoryStock->decrement('incoming_stock', $item['stock_in']);
                    $inventoryStock->increment('inventory_stock', $item['stock_in']);
                    $inventoryStock->increment('stock_after_sales', $item['stock_in']);

                    // 🔹 Hitung ulang avg_cost (weighted average pakai stock_after_sales)
                    $purchaseItem = $inventoryItem->purchaseItem ?? null;
                    // if ($purchaseItem) {
                    //     $purchaseCost = $purchaseItem->final_price;

                    //     // Hitung stok total sebelum pembelian dari stock_after_sales
                    //     $previousQty  = max(0, $inventoryStock->stock_after_sales - $item['stock_in']);
                    //     $previousCost = $inventoryStock->avg_cost;

                    //     // Weighted average formula
                    //     $inventoryStock->avg_cost = round(
                    //         (($previousCost * $previousQty) + ($purchaseCost * $item['stock_in']))
                    //             / max(1, $previousQty + $item['stock_in']),
                    //         3
                    //     );
                    //     $inventoryStock->save();
                    // }
                    if ($purchaseItem) {
                        $cost = $purchaseItem->final_price;

                        $previousQty  = max(0, $this->getTotalStockForAvg($productId) - $item['stock_in']);
                        $previousCost = $inventoryStock->avg_cost;

                        $inventoryStock->avg_cost = round(
                            (($previousCost * $previousQty) + ($cost * $item['stock_in']))
                                / max(1, $previousQty + $item['stock_in']),
                            3
                        );

                        $inventoryStock->save();
                    }
                    // } elseif ($inventory->canceled_product_id) {
                    //     // 🔹 Barang dari Sale Return (Canceled Product)
                    //     $inventoryStock->increment('inventory_stock', $item['stock_in']);
                    //     $inventoryStock->increment('stock_after_sales', $item['stock_in']);

                    //     // Ambil data canceled product
                    //     $canceledProduct = \App\Models\CanceledProduct::find($inventory->canceled_product_id);
                    //     $avgCostAtCancel = $canceledProduct?->avg_cost_at_cancel ?? 0;

                    //     // Ambil total stok sebelumnya dari stock_after_sales (bukan inventory_stock)
                    //     $previousQty  = max(0, $inventoryStock->stock_after_sales - $item['stock_in']);
                    //     $previousCost = $inventoryStock->avg_cost;

                    //     // Weighted average baru berdasarkan stock_after_sales
                    //     $inventoryStock->avg_cost = round(
                    //         (($previousCost * $previousQty) + ($avgCostAtCancel * $item['stock_in']))
                    //             / max(1, $previousQty + $item['stock_in']),
                    //         3
                    //     );

                    //     $inventoryStock->save();
                } elseif ($inventory->canceled_product_id) {

                    $inventoryStock->increment('inventory_stock', $item['stock_in']);

                    $canceledProduct = CanceledProduct::find($inventory->canceled_product_id);
                    $cost = $canceledProduct?->avg_cost_at_cancel ?? 0;

                    $previousQty  = max(0, $this->getTotalStockForAvg($productId) - $item['stock_in']);
                    $previousCost = $inventoryStock->avg_cost;

                    $inventoryStock->avg_cost = round(
                        (($previousCost * $previousQty) + ($cost * $item['stock_in']))
                            / max(1, $previousQty + $item['stock_in']),
                        3
                    );

                    $inventoryStock->save();
                } elseif ($inventoryItem->material_request_item_id) {
                    // hanya update stok fisik gudang
                    $inventoryStock->increment('inventory_stock', $item['stock_in']);
                    // ❌ tidak update stock_after_sales
                    $inventoryStock->save();
                }

                Products::where('id', $productId)->update([
                    'avg_cost' => $inventoryStock->avg_cost,
                ]);
            }

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

        foreach ($request->items as $item) {
            $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

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
                ProductCostService::updateCostAndStock($product);
            }
        }

        return redirect('/erp/inventory/stock-in')
            ->with('success', 'Inventory Stock Out berhasil diupdate');
    }

    public function getHistory($id)
    {
        $stockIn = Inventory::with([
            'items.product',
            'items.inventoryStockIn.inventoryItem',
        ])->findOrFail($id);

        return view('erp.pages.inventory.stock-in.history-stock-in', compact('stockIn'));
    }

    public function dataHistory(Request $request, $id)
    {
        $stockIn = InventoryStockIn::with(['user', 'histories.inventoryItem.product'])
            ->where('inventory_id', $id)->latest();

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
            ->addColumn('stock_in', function ($stockIn) {
                return view('erp.pages.inventory.stock-in.partials.product-stock-in-history', [
                    "items" => $stockIn->histories
                ])->render();
            })
            ->rawColumns(['invoice_number', 'waybill_image', 'stock_in'])
            ->make(true);
    }

    public function updateHistoryItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        // 🔹 Ambil data history lama
        $history = InventoryStockInHistory::findOrFail($id);

        // 🔹 Dapatkan inventory item terkait
        $inventoryItem = $history->inventoryItem;

        if (!$inventoryItem) {
            return response()->json([
                'message' => 'Data inventory item tidak ditemukan.'
            ], 404);
        }

        // 🔹 Hitung selisih
        $oldQty = $history->stock_in;
        $newQty = $request->quantity;
        $diff = $newQty - $oldQty;

        // 🔹 Update kolom stock_in di inventory_items
        // jika diff positif → increment, negatif → decrement
        $inventoryItem->stock_in += $diff;
        if ($inventoryItem->stock_in < 0) {
            $inventoryItem->stock_in = 0; // jaga-jaga tidak minus
        }
        $inventoryItem->save();

        // 🔹 Update data history
        $history->update([
            'stock_in' => $newQty,
            'notes' => $request->notes,
        ]);

        return response()->json(['message' => 'History item dan stok berhasil diperbarui.']);
    }
}
