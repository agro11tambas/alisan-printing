<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CanceledProduct;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\ProductionStock;
use App\Models\Products;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductionStockInController extends Controller
{
    // public function addStockIn($id)
    // {
    //     $stockIn = Inventory::with('items.product')->findOrFail($id);

    //     return view('erp.pages.production.stock-in.add-stock-in', compact('stockIn'));
    // }

    public function addStockIn($supplierId)
    {
        $groupedInventories = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In Production')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->get();

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier       = optional($groupedInventories->first()->purchase->supplier);
        $invoiceNumbers = $groupedInventories
            ->map(fn($inv) => $inv->purchase->purchase_number ?? '-')
            ->filter()->unique()->implode(', ');

        $mergedItems = $groupedInventories->flatMap(fn($inv) => $inv->items)
            ->groupBy('product_id')
            ->map(function ($productItems) {
                $first = $productItems->first();
                return (object)[
                    'product'               => $first->product,
                    'product_id'            => $first->product_id,
                    'unit_name'             => $first->unit_name ?? 'Pcs',
                    'unit_conversion_value' => $first->unit_conversion_value ?? 1,
                    'quantity'              => $productItems->sum('quantity'),
                    'qty_base'              => $productItems->sum('qty_base'),
                    'stock_in'              => $productItems->sum('stock_in'),
                    'remaining'             => $productItems->sum('qty_base') - $productItems->sum('stock_in'),
                    'item_ids'              => $productItems->pluck('id')->toArray(),
                ];
            })->values();

        $firstInventory = $groupedInventories->first();

        return view('erp.pages.production.stock-in.add-stock-in', compact( // ← beda di sini
            'mergedItems',
            'supplier',
            'invoiceNumbers',
            'firstInventory',
            'supplierId',
        ));
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
    //     $request->validate([
    //         'inventory_id' => 'required|exists:inventories_2,id',
    //         'change_date' => 'required|date',
    //         'notes' => 'nullable',
    //         'waybill_number' => 'nullable|string',
    //         'waybill_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

    //             $previousQty = max(
    //                 0,
    //                 $this->getTotalStockForAvg($productId) - $item['stock_in']
    //             );

    //             $productionStock = ProductionStock::firstOrCreate(
    //                 [
    //                     'product_id'             => $productId,
    //                     'production_warehouse_id' => $inventoryItem->production_warehouse_id ?? 2,
    //                 ],
    //                 [
    //                     'opening_stock'     => 0,
    //                     'available_quantity'   => 0,
    //                     'incoming_stock'    => 0,

    //                 ]
    //             );

    //             if ($inventory->purchase_id) {

    //                 $productionStock->decrement('incoming_stock', $item['stock_in']);
    //                 $productionStock->increment('available_quantity', $item['stock_in']);

    //                 $purchaseItem = $inventoryItem->purchaseItem ?? null;

    //                 if ($purchaseItem) {
    //                     $cost = $purchaseItem->final_price;

    //                     $newAvgCost = round(
    //                         (($previousCost * $previousQty) + ($cost * $item['stock_in']))
    //                             / max(1, $previousQty + $item['stock_in']),
    //                         3
    //                     );

    //                     $product->update([
    //                         'avg_cost' => $newAvgCost,
    //                     ]);
    //                 }
    //             } elseif ($inventory->canceled_product_id) {

    //                 $productionStock->increment('available_quantity', $item['stock_in']);

    //                 $canceledProduct = CanceledProduct::find($inventory->canceled_product_id);
    //                 $cost = $canceledProduct?->avg_cost_at_cancel ?? 0;

    //                 $newAvgCost = round(
    //                     (($previousCost * $previousQty) + ($cost * $item['stock_in']))
    //                         / max(1, $previousQty + $item['stock_in']),
    //                     3
    //                 );

    //                 $product->update([
    //                     'avg_cost' => $newAvgCost,
    //                 ]);
    //             }
    //         }

    //         DB::commit();
    //         return redirect('/erp/productions/stock-in')->with('success', 'Stock In berhasil ditambahkan');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Stock In gagal: ' . $e->getMessage());
    //     }
    // }

    public function storeGrouped(Request $request, $supplierId)
    {
        $items = $request->input('items', []);
        foreach ($items as $index => $item) {
            if (isset($item['stock_in'])) {
                $cleaned = preg_replace('/[^0-9-]/', '', $item['stock_in']);
                $items[$index]['stock_in'] = (int) $cleaned;
            }
        }
        $request->merge(['items' => $items]);

        $request->validate([
            'change_date'                  => 'required|date',
            'waybill_number'               => 'nullable|string',
            'waybill_image'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'items'                        => 'required|array',
            'items.*.product_id'           => 'required|exists:products,id',
            'items.*.inventory_item_ids'   => 'required|array',
            'items.*.inventory_item_ids.*' => 'exists:inventory_items_2,id',
            'items.*.stock_in'             => 'required|integer|min:0',
            'items.*.unit_conversion_value' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            $waybillImagePath = null;
            if ($request->hasFile('waybill_image')) {
                $image    = $request->file('waybill_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                // $image->move(public_path('uploads/waybill_image'), $filename);
                // $waybillImagePath = 'uploads/waybill_image/' . $filename;

                $uploadPath = base_path('uploads/waybill_image');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                $image->move($uploadPath, $filename);
                $waybillImagePath = 'uploads/waybill_image/' . $filename;
            }

            foreach ($request->items as $itemData) {
                $conv    = (float) ($itemData['unit_conversion_value'] ?? 1);
                $addQty  = (int) $itemData['stock_in'];       // input user (kotak)
                $addBase = (int) round($addQty * $conv);       // pcs

                if ($addQty <= 0) continue;

                // FIFO: terlama duluan berdasarkan purchase_date
                $inventoryItems = InventoryItem::whereIn('inventory_items_2.id', $itemData['inventory_item_ids'])
                    ->join('inventories_2', 'inventory_items_2.inventory_id', '=', 'inventories_2.id')
                    ->join('purchases', 'inventories_2.purchase_id', '=', 'purchases.id')
                    ->orderBy('purchases.purchase_date', 'asc')
                    ->select('inventory_items_2.*')
                    ->get();

                $remaining = $addBase; // ← base unit

                foreach ($inventoryItems as $inventoryItem) {
                    if ($remaining <= 0) break;

                    $canAdd = $inventoryItem->qty_base - $inventoryItem->stock_in; // pcs
                    if ($canAdd <= 0) continue;

                    $toAdd = min($remaining, $canAdd);

                    $inventory = Inventory::findOrFail($inventoryItem->inventory_id);

                    $stockIn = InventoryStockIn::create([
                        'inventory_id'   => $inventory->id,
                        'invoice_number' => $inventory->note === 'Return Canceled Product to Warehouse'
                            ? $inventory->order_number
                            : $inventory->purchase_number,
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
                        $purchaseItem = PurchaseItem::find($inventoryItem->purchase_item_id);
                        if ($purchaseItem) {
                            $purchaseItem->increment('stock_in', $toAdd);
                        }
                    }

                    // Ambil sebelum increment
                    $productId    = $inventoryItem->product_id;
                    $product      = Products::findOrFail($productId);
                    $previousCost = $product->avg_cost ?? 0;
                    $previousQty  = max(0, $this->getTotalStockForAvg($productId));

                    $inventoryItem->increment('stock_in', $toAdd);

                    $productionStock = ProductionStock::firstOrCreate(
                        [
                            'product_id'              => $productId,
                            'production_warehouse_id' => $inventoryItem->production_warehouse_id ?? 2,
                        ],
                        [
                            'opening_stock'      => 0,
                            'available_quantity' => 0,
                            'incoming_stock'     => 0,
                        ]
                    );

                    if ($inventory->purchase_id) {
                        $productionStock->decrement('incoming_stock', $toAdd);
                        $productionStock->increment('available_quantity', $toAdd);

                        $purchaseItemForCost = $inventoryItem->purchaseItem ?? null;
                        if ($purchaseItemForCost) {
                            $cost       = $purchaseItemForCost->final_price;
                            $newAvgCost = round(
                                (($previousCost * $previousQty) + ($cost * $toAdd))
                                    / max(1, $previousQty + $toAdd),
                                3
                            );
                            $product->update(['avg_cost' => $newAvgCost]);
                        }
                    } elseif ($inventory->canceled_product_id) {
                        $productionStock->increment('available_quantity', $toAdd);

                        $canceledProduct = CanceledProduct::find($inventory->canceled_product_id);
                        $cost            = $canceledProduct?->avg_cost_at_cancel ?? 0;
                        $newAvgCost      = round(
                            (($previousCost * $previousQty) + ($cost * $toAdd))
                                / max(1, $previousQty + $toAdd),
                            3
                        );
                        $product->update(['avg_cost' => $newAvgCost]);
                    }

                    $remaining -= $toAdd;
                }
            }

            DB::commit();
            return redirect('/erp/productions/stock-in')->with('success', 'Stock In berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Stock In gagal: ' . $e->getMessage());
        }
    }

    // public function getHistory($id)
    // {
    //     $stockIn = Inventory::with([
    //         'items.product',
    //         'items.inventoryStockIn.inventoryItem',
    //     ])->findOrFail($id);

    //     return view('erp.pages.production.stock-in.history-stock-in', compact('stockIn'));
    // }

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
    //             return view('erp.pages.production.stock-in.partials.product-stock-in-history', [
    //                 "items" => $stockIn->histories
    //             ])->render();
    //         })
    //         ->rawColumns(['invoice_number', 'waybill_image', 'stock_in'])
    //         ->make(true);
    // }

    public function dataHistory(Request $request, $supplierId)
    {
        $inventoryIds = Inventory::where('status', 'Stock In Production')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->pluck('id');

        $query = InventoryStockIn::with(['user', 'histories.inventoryItem.product'])
            ->whereIn('inventory_id', $inventoryIds)
            ->latest();

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('change_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('verified')) {
            if ($request->verified === 'verified') {
                $query->where('is_verified', true);
            } elseif ($request->verified === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        // Group by waybill_number, merge histories
        $grouped = $query->get()
            ->groupBy(fn($row) => $row->waybill_number ?: 'NO_WAYBILL_' . $row->id)
            ->map(function ($rows) {
                $first = $rows->first();

                $mergedHistories = $rows->flatMap(fn($r) => $r->histories)
                    ->groupBy(fn($h) => $h->inventoryItem->product_id ?? $h->id)
                    ->map(function ($items) {
                        $first = $items->first();
                        $first->merged_stock_in = $items->sum('stock_in');
                        return $first;
                    })
                    ->filter(fn($item) => $item->merged_stock_in > 0)
                    ->values();

                return (object) [
                    'id'             => $first->id,
                    'status'         => $first->status,
                    'invoice_number' => $rows->pluck('invoice_number')->unique()->implode(', '),
                    'change_date'    => $first->created_at,
                    'user'           => $first->user,
                    'waybill_number' => $first->waybill_number,
                    'waybill_image'  => $first->waybill_image,
                    'histories'      => $mergedHistories,
                    'is_verified'    => $first->is_verified,
                    'verified_at'    => $first->verified_at,
                ];
            })
            ->values();

        return DataTables::of($grouped)
            ->addIndexColumn()
            // ->addColumn('invoice_number', function ($row) {
            //     $html = '';
            //     if (strtolower($row->status) === 'add stock in') {
            //         $html .= '<div class="badge bg-soft-primary text-primary mb-1">Add Stock In</div><br>';
            //     } elseif (strtolower($row->status) === 'edit stock in') {
            //         $html .= '<div class="badge bg-soft-danger text-danger mb-1">Edit Stock Out</div><br>';
            //     }
            //     $html .= $row->invoice_number;
            //     return $html;
            // })

            ->addColumn('invoice_number', function ($row) {
                $html = '';
                if (strtolower($row->status) === 'add stock in') {
                    $html .= '<div class="badge bg-soft-primary text-primary mb-1">Add Stock In</div><br>';
                } elseif (strtolower($row->status) === 'edit stock in') {
                    $html .= '<div class="badge bg-soft-danger text-danger mb-1">Edit Stock Out</div><br>';
                }
                $html .= $row->invoice_number;

                // Verified button di bawah invoice number
                $html .= '<br>';
                if ($row->is_verified) {
                    $html .= '<button class="btn btn-success btn-sm btn-verified mt-1" data-id="' . $row->id . '" disabled>
                                <i class="ri-checkbox-circle-line"></i> Paid
                            </button>';
                } else {
                    $html .= '<button class="btn btn-outline-primary btn-sm btn-verify mt-1" data-id="' . $row->id . '">
                                <i class="ri-checkbox-circle-line"></i> Mark As Paid
                            </button>';
                }

                return $html;
            })
            ->addColumn('change_date', fn($row) => Carbon::parse($row->change_date)->format('j M y H:i:s'))
            ->addColumn('user_name', fn($row) => $row->user->name)
            ->addColumn('waybill_number', fn($row) => $row->waybill_number)
            ->addColumn('waybill_image', function ($row) {
                if ($row->waybill_image) {
                    $imageUrl = asset($row->waybill_image);
                    return '<a href="' . $imageUrl . '" data-lightbox="waybill-' . $row->id . '">
                    <img src="' . $imageUrl . '" alt="Waybill Image" class="img-fluid" style="max-width: 60px;">
                </a>';
                }
                return '-';
            })
            ->addColumn('stock_in', fn($row) => view('erp.pages.production.stock-in.partials.product-stock-in-history', [
                'items' => $row->histories
            ])->render())
            ->addColumn('verified', function ($row) {
                if ($row->is_verified) {
                    return '<button class="btn btn-success btn-sm btn-verified" data-id="' . $row->id . '" disabled>
                    <i class="ri-checkbox-circle-line"></i> Paid
                </button>';
                }
                return '<button class="btn btn-outline-primary btn-sm btn-verify" data-id="' . $row->id . '">
                <i class="ri-checkbox-circle-line"></i> Mark As Paid
            </button>';
            })
            ->rawColumns(['invoice_number', 'waybill_image', 'stock_in', 'verified'])
            ->make(true);
    }

    public function getHistory($supplierId)
    {
        $groupedInventories = Inventory::with(['items.product', 'purchase.supplier'])
            ->where('status', 'Stock In Production')
            ->whereHas('purchase.supplier', fn($q) => $q->where('id', $supplierId))
            ->get();

        abort_if($groupedInventories->isEmpty(), 404);

        $supplier = optional($groupedInventories->first()->purchase->supplier);

        $inventoryIds = $groupedInventories->pluck('id')->toArray();

        $mergedItems = $groupedInventories->flatMap(fn($inv) => $inv->items)
            ->groupBy('product_id')
            ->map(function ($productItems) {
                return (object)[
                    'product'  => $productItems->first()->product,
                    'quantity' => $productItems->sum('quantity'),
                    'stock_in' => $productItems->sum('stock_in'),
                ];
            })->values();

        return view('erp.pages.production.stock-in.history-stock-in', compact(
            'mergedItems',
            'supplier',
            'inventoryIds',
            'supplierId',
        ));
    }

    public function verify($id)
    {
        $stockIn = InventoryStockIn::findOrFail($id);

        if ($stockIn->is_verified) {
            return response()->json(['message' => 'Already verified'], 400);
        }

        $stockIn->update([
            'is_verified'  => true,
            'verified_by'  => Auth::id(),
            'verified_at'  => now(),
        ]);

        return response()->json(['message' => 'Berhasil diverifikasi']);
    }

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
        $productionStock = productionStock::where('product_id', $inventoryItem->product_id)
            ->where('production_warehouse_id', $inventoryItem->production_warehouse_id)
            ->first();

        if (!$productionStock) {
            return response()->json(['message' => 'Production stock tidak ditemukan'], 404);
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
        $productionStock->available_quantity += $diff;

        if ($productionStock->available_quantity < 0) $productionStock->available_quantity = 0;

        $product = Products::findOrFail($inventoryItem->product_id);

        // qty sebelum perubahan
        $prevQty = max(
            0,
            $this->getTotalStockForAvg($inventoryItem->product_id) - $oldQty
        );

        // avg cost lama dari PRODUCT
        $prevCost = $product->avg_cost ?? 0;

        // ambil COST (bukan price)
        $cost = 0;
        if ($inventoryItem->purchase_item_id) {
            $purchaseItem = PurchaseItem::find($inventoryItem->purchase_item_id);
            $cost = $purchaseItem?->final_price ?? 0;
        }

        if (($prevQty + $newQty) > 0) {
            $newAvgCost = round(
                (($prevQty * $prevCost) + ($newQty * $cost))
                    / ($prevQty + $newQty),
                3
            );

            $product->update([
                'avg_cost' => $newAvgCost,
            ]);
        }

        $productionStock->save(); // ⬅️ INI WAJIB

        // Update history
        $history->update([
            'stock_in' => $newQty,
            'notes'    => $request->notes,
        ]);

        return response()->json(['message' => 'History berhasil diperbarui']);
    }
}
