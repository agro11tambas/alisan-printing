<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryList;
use App\Models\DeliveryListItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\ProductionStock;
use App\Models\User;
use App\Services\DeliveryListService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class DeliveryListController extends Controller
{
    public function getDeliveryList()
    {
        return view('erp.pages.deliveries.delivery-list.delivery-list');
    }

    public function dataDeliveryList(Request $request)
    {
        $deliveryLists = DeliveryList::with(['deliveryOrder', 'items.product'])
            ->orderByDesc('id');

        // 🔎 Filter by date
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $deliveryLists->whereDate('shipment_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $deliveryLists->whereBetween('shipment_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $deliveryLists->whereMonth('shipment_date', Carbon::now()->month)
                        ->whereYear('shipment_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $deliveryLists->whereBetween('shipment_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $deliveryLists->whereBetween('shipment_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $deliveryLists->whereYear('shipment_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $deliveryLists->whereBetween('shipment_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // 🔎 Search
        if ($request->filled('search_keyword')) {
            switch ($request->search_type) {
                case 'driver':
                    $deliveryLists->where('driver', 'like', '%' . $request->search_keyword . '%');
                    break;
                case 'vehicle':
                    $deliveryLists->where('vehicle', 'like', '%' . $request->search_keyword . '%');
                    break;
                default:
                    $deliveryLists->where('shipment_number', 'like', '%' . $request->search_keyword . '%');
                    break;
            }
        }

        $deliveryLists = $deliveryLists->latest()->get();

        return DataTables::of($deliveryLists)
            ->addIndexColumn()
            ->addColumn('shipment_number', function ($dl) {
                $date = Carbon::parse($dl->shipment_date)->format('j M y');
                return '<div>
                    <div>' . $dl->shipment_number . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';
            })
            ->addColumn('shipment_date', function ($dl) {
                return Carbon::parse($dl->shipment_date)->format('j M y');
            })
            ->addColumn('driver', function ($dl) {
                return $dl->driver ?? '-';
            })
            ->addColumn('vehicle', function ($dl) {
                return $dl->vehicle ?? '-';
            })
            ->addColumn('status', function ($dl) {
                $status = strtolower($dl->status);
                switch ($status) {
                    case 'ongoing':
                        $badgeClass = 'bg-soft-primary text-primary';
                        break;
                    case 'finished':
                        $badgeClass = 'bg-soft-success text-success';
                        break;
                    default:
                        $badgeClass = 'bg-secondary';
                        break;
                }
                return '<div class="badge ' . $badgeClass . '">' . ucfirst($dl->status) . '</div>';
            })
            ->addColumn('items', function ($dl) {
                return view('erp.pages.deliveries.delivery-list.partials.product-list', compact('dl'))->render();
            })
            ->addColumn('waybill_proof', function ($dl) {
                if ($dl->proof_waybill) {
                    $src = asset('storage/' . $dl->proof_waybill); // kalau disimpan di storage/app/public
                    return '
                        <a href="' . $src . '" data-lightbox="waybill-' . $dl->id . '">
                            <img src="' . $src . '"
                                width="50"
                                height="50"
                                style="border-radius: 8px; object-fit: cover; object-position: center;"
                                alt="Waybill Proof">
                        </a>
                    ';
                } else {
                    return '<span class="text-muted">-</span>';
                }
            })
            ->addColumn('delivery_proof', function ($dl) {
                if ($dl->proof_delivery) {
                    $src = asset('storage/' . $dl->proof_delivery); // kalau disimpan di storage/app/public
                    return '
                        <a href="' . $src . '" data-lightbox="delivery-' . $dl->id . '">
                            <img src="' . $src . '"
                                width="50"
                                height="50"
                                style="border-radius: 8px; object-fit: cover; object-position: center;"
                                alt="Delivery Proof">
                        </a>
                    ';
                } else {
                    return '<span class="text-muted">-</span>';
                }
            })
            ->addColumn('action', function ($dl) {
                return view('erp.pages.deliveries.delivery-list.partials.action-button', compact('dl'))->render();
            })
            ->rawColumns(['shipment_number', 'status', 'items', 'action', 'waybill_proof', 'delivery_proof'])
            ->make(true);
    }

    // public function create($doId)
    // {
    //     $deliveryOrder = DeliveryOrder::with(['items.product', 'items.deliveryListItems'])
    //         ->findOrFail($doId);

    //     // generate nomor otomatis
    //     $shipmentNumber = \App\Services\DeliveryListService::generateShipmentNumber($deliveryOrder);

    //     return view('erp.pages.deliveries.delivery-list.create-delivery-list', compact('deliveryOrder', 'shipmentNumber'));
    // }

    public function create($doId)
    {
        $deliveryOrder = DeliveryOrder::with(['items.product', 'items.deliveryListItems'])
            ->findOrFail($doId);

        // Generate nomor otomatis
        $shipmentNumber = \App\Services\DeliveryListService::generateShipmentNumber($deliveryOrder);

        // 🔹 Ambil daftar user dengan role Kurir
        $drivers = \App\Models\User::where('role', 'Kurir')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('erp.pages.deliveries.delivery-list.create-delivery-list', compact(
            'deliveryOrder',
            'shipmentNumber',
            'drivers' // ✅ kirim ke view
        ));
    }

    // public function store(Request $request, $doId)
    // {
    //     $request->validate([
    //         'shipment_number' => 'required|string|max:255|unique:delivery_lists,shipment_number',
    //         'shipment_date'   => 'required|date',
    //         'driver'          => 'required|string|max:255',
    //         'vehicle'         => 'nullable|string|max:255',
    //         'note'            => 'nullable|string',
    //         'items'           => 'required|array',
    //         'items.*.shipped_quantity' => 'nullable|numeric|min:0',
    //     ]);

    //     $deliveryOrder = DeliveryOrder::findOrFail($doId);

    //     $deliveryList = DeliveryList::create([
    //         'delivery_order_id' => $deliveryOrder->id,
    //         'shipment_number'   => $request->shipment_number,
    //         'shipment_date'     => $request->shipment_date,
    //         'driver'            => $request->driver,
    //         'vehicle'           => $request->vehicle,
    //         'note'              => $request->note,
    //         'status'            => 'Ongoing',
    //     ]);

    //     foreach ($request->items as $item) {
    //         if (($item['shipped_quantity'] ?? 0) > 0) {
    //             $deliveryList->items()->create([
    //                 'delivery_order_item_id' => $item['delivery_order_item_id'],
    //                 'product_id'             => $item['product_id'],
    //                 'shipped_quantity'       => $item['shipped_quantity'],
    //                 'note'                   => $item['note'] ?? null,
    //             ]);

    //             // ✅ Update DeliveryOrderItem
    //             $doItem = DeliveryOrderItem::find($item['delivery_order_item_id']);
    //             if ($doItem) {
    //                 $newReadyQty   = max(0, $doItem->ready_qty - $item['shipped_quantity']);
    //                 $newShippedQty = $doItem->shipped_qty + $item['shipped_quantity'];

    //                 $doItem->update([
    //                     'ready_qty'   => $newReadyQty,
    //                     'shipped_qty' => $newShippedQty,
    //                     'status'      => $newShippedQty >= $doItem->progress_qty ? 'Shipped' : $doItem->status,
    //                 ]);
    //             }

    //             // ✅ Decrement finished_product_stock di ProductionStock
    //             $productionStock = ProductionStock::where('product_id', $item['product_id'])->first();
    //             if ($productionStock) {
    //                 $productionStock->decrement('finished_product_stock', $item['shipped_quantity']);
    //             }
    //         }
    //     }

    //     // ✅ Kalau semua item DO sudah shipped, update status DO
    //     $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
    //     if ($allShipped) {
    //         $deliveryOrder->update(['status' => 'Shipped']);
    //     }

    //     return redirect('/erp/deliveries/delivery-list')
    //         ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil dibuat!");
    // }

    public function store(Request $request, $doId)
    {
        $request->validate([
            'shipment_number' => 'required|string|max:255|unique:delivery_lists,shipment_number',
            'shipment_date'   => 'required|date',
            'driver_id'       => 'required|exists:users,id', // ✅ ganti jadi ambil dari dropdown driver
            'vehicle'         => 'nullable|string|max:255',
            'note'            => 'nullable|string',
            'items'           => 'required|array',
            'items.*.shipped_quantity' => 'nullable|numeric|min:0',
        ]);

        $deliveryOrder = DeliveryOrder::findOrFail($doId);

        // 🔹 Ambil data driver berdasarkan ID untuk isi nama otomatis
        $driver = User::find($request->driver_id);

        $deliveryList = DeliveryList::create([
            'delivery_order_id' => $deliveryOrder->id,
            'shipment_number'   => $request->shipment_number,
            'shipment_date'     => $request->shipment_date,
            'driver_id'         => $driver->id,     // ✅ simpan relasi driver
            'driver'            => $driver->name,   // ✅ isi juga nama driver (biar tetap bisa dibaca di laporan)
            'vehicle'           => $request->vehicle,
            'note'              => $request->note,
            'status'            => 'Ongoing',
        ]);

        foreach ($request->items as $item) {
            if (($item['shipped_quantity'] ?? 0) > 0) {
                $deliveryList->items()->create([
                    'delivery_order_item_id' => $item['delivery_order_item_id'],
                    'product_id'             => $item['product_id'],
                    'shipped_quantity'       => $item['shipped_quantity'],
                    'note'                   => $item['note'] ?? null,
                ]);

                // ✅ Update DeliveryOrderItem
                $doItem = DeliveryOrderItem::find($item['delivery_order_item_id']);
                if ($doItem) {
                    $newReadyQty   = max(0, $doItem->ready_qty - $item['shipped_quantity']);
                    $newShippedQty = $doItem->shipped_qty + $item['shipped_quantity'];

                    $doItem->update([
                        'ready_qty'   => $newReadyQty,
                        'shipped_qty' => $newShippedQty,
                        'status'      => $newShippedQty >= $doItem->progress_qty ? 'Shipped' : $doItem->status,
                    ]);
                }

                // ✅ Decrement finished_product_stock di ProductionStock
                $productionStock = ProductionStock::where('product_id', $item['product_id'])->first();
                if ($productionStock) {
                    $productionStock->decrement('finished_product_stock', $item['shipped_quantity']);
                }
            }
        }

        // ✅ Kalau semua item DO sudah shipped, update status DO
        $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
        if ($allShipped) {
            $deliveryOrder->update(['status' => 'Shipped']);
        }

        return redirect('/erp/deliveries/delivery-list')
            ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil dibuat!");
    }

    // public function edit($id)
    // {
    //     $deliveryList = DeliveryList::with(['items.deliveryOrderItem.product'])->findOrFail($id);

    //     return view('erp.pages.deliveries.delivery-list.edit-delivery-list', compact('deliveryList'));
    // }

    public function edit($id)
    {
        $deliveryList = DeliveryList::with(['items.deliveryOrderItem.product'])->findOrFail($id);

        // 🔹 Ambil daftar user dengan role Kurir
        $drivers = \App\Models\User::where('role', 'Kurir')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('erp.pages.deliveries.delivery-list.edit-delivery-list', compact('deliveryList', 'drivers'));
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'shipment_date'   => 'required|date',
    //         'driver'          => 'required|string|max:255',
    //         'vehicle'         => 'nullable|string|max:255',
    //         'note'            => 'nullable|string',
    //         'items'           => 'required|array',
    //         'items.*.shipped_quantity' => 'nullable|numeric|min:0',
    //     ]);

    //     $deliveryList  = DeliveryList::with('items.deliveryOrderItem')->findOrFail($id);
    //     $deliveryOrder = $deliveryList->deliveryOrder;

    //     // Update header
    //     $deliveryList->update([
    //         'shipment_date' => $request->shipment_date,
    //         'driver'        => $request->driver,
    //         'vehicle'       => $request->vehicle,
    //         'note'          => $request->note,
    //     ]);

    //     // Ambil semua item lama dalam bentuk koleksi keyed by delivery_order_item_id
    //     $existingItems = $deliveryList->items->keyBy('delivery_order_item_id');

    //     // Loop semua item dari request
    //     foreach ($request->items as $itemData) {
    //         $deliveryOrderItemId = $itemData['delivery_order_item_id'];
    //         $newQty = $itemData['shipped_quantity'] ?? 0;

    //         if ($newQty <= 0) {
    //             // kalau 0, skip aja
    //             continue;
    //         }

    //         /** @var \App\Models\DeliveryListItem|null $existing */
    //         $existing = $existingItems->get($deliveryOrderItemId);
    //         $doItem   = DeliveryOrderItem::find($deliveryOrderItemId);

    //         if ($existing) {
    //             // ✅ update qty lama ke qty baru
    //             $oldQty = $existing->shipped_quantity;

    //             if ($newQty != $oldQty) {
    //                 // hitung selisih
    //                 $diff = $newQty - $oldQty;

    //                 // update DeliveryListItem
    //                 $existing->update([
    //                     'shipped_quantity' => $newQty,
    //                     'note'             => $itemData['note'] ?? null,
    //                 ]);

    //                 // update DeliveryOrderItem qty sesuai selisih
    //                 if ($doItem) {
    //                     $doItem->shipped_qty += $diff;
    //                     $doItem->ready_qty   = max(0, $doItem->ready_qty - $diff);
    //                     $doItem->status      = $doItem->shipped_qty >= $doItem->progress_qty ? 'Shipped' : 'Ongoing';
    //                     $doItem->save();
    //                 }

    //                 // update ProductionStock
    //                 $productionStock = ProductionStock::where('product_id', $itemData['product_id'])->first();
    //                 if ($productionStock) {
    //                     $productionStock->decrement('finished_product_stock', $diff);
    //                 }
    //             }
    //         } else {
    //             // ✅ item baru
    //             $deliveryList->items()->create([
    //                 'delivery_order_item_id' => $deliveryOrderItemId,
    //                 'product_id'             => $itemData['product_id'],
    //                 'shipped_quantity'       => $newQty,
    //                 'note'                   => $itemData['note'] ?? null,
    //             ]);

    //             if ($doItem) {
    //                 $doItem->shipped_qty += $newQty;
    //                 $doItem->ready_qty   = max(0, $doItem->ready_qty - $newQty);
    //                 $doItem->status      = $doItem->shipped_qty >= $doItem->progress_qty ? 'Shipped' : 'Ongoing';
    //                 $doItem->save();
    //             }

    //             $productionStock = ProductionStock::where('product_id', $itemData['product_id'])->first();
    //             if ($productionStock) {
    //                 $productionStock->decrement('finished_product_stock', $newQty);
    //             }
    //         }
    //     }

    //     // 🔁 Cek kalau ada item lama yang gak dikirim lagi → hapus & rollback qty
    //     $requestIds = collect($request->items)->pluck('delivery_order_item_id');
    //     $deletedItems = $deliveryList->items->whereNotIn('delivery_order_item_id', $requestIds);

    //     foreach ($deletedItems as $deletedItem) {
    //         $doItem = $deletedItem->deliveryOrderItem;
    //         if ($doItem) {
    //             $doItem->shipped_qty = max(0, $doItem->shipped_qty - $deletedItem->shipped_quantity);
    //             $doItem->ready_qty   += $deletedItem->shipped_quantity;
    //             $doItem->status      = $doItem->shipped_qty >= $doItem->progress_qty ? 'Shipped' : 'Ongoing';
    //             $doItem->save();
    //         }

    //         $productionStock = ProductionStock::where('product_id', $deletedItem->product_id)->first();
    //         if ($productionStock) {
    //             $productionStock->increment('finished_product_stock', $deletedItem->shipped_quantity);
    //         }

    //         $deletedItem->delete();
    //     }

    //     // 🔁 Update status DO
    //     $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
    //     $deliveryOrder->update(['status' => $allShipped ? 'Shipped' : 'Ongoing']);

    //     return redirect('/erp/deliveries/delivery-list')
    //         ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil diupdate!");
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'shipment_date' => 'required|date',
            'driver_id'     => 'required|exists:users,id',
            'vehicle'       => 'nullable|string|max:255',
            'note'          => 'nullable|string',
            'items'         => 'required|array',
            'items.*.shipped_quantity' => 'nullable|numeric|min:0',
        ]);

        $deliveryList = DeliveryList::findOrFail($id);
        $driver = \App\Models\User::find($request->driver_id);

        $deliveryList->update([
            'shipment_date' => $request->shipment_date,
            'driver_id'     => $driver->id,
            'driver'        => $driver->name,
            'vehicle'       => $request->vehicle,
            'note'          => $request->note,
        ]);

        foreach ($request->items as $itemData) {
            $item = DeliveryListItem::find($itemData['delivery_list_item_id']);
            if (!$item) continue;

            $oldQty = $item->shipped_quantity;
            $newQty = (float) $itemData['shipped_quantity'];
            $difference = $newQty - $oldQty; // bisa plus atau minus

            // ✅ Update delivery_list_item
            $item->update([
                'shipped_quantity' => $newQty,
                'note' => $itemData['note'] ?? null,
            ]);

            // ✅ Update ready_qty dan shipped_qty di DeliveryOrderItem
            $doItem = $item->deliveryOrderItem;
            if ($doItem && $difference != 0) {
                $newReadyQty   = $doItem->ready_qty - $difference; // kurangi / tambahkan berdasarkan selisih
                $newShippedQty = $doItem->shipped_qty + $difference;

                $doItem->update([
                    'ready_qty'   => max(0, $newReadyQty),
                    'shipped_qty' => max(0, $newShippedQty),
                ]);
            }

            // ✅ Update stok di ProductionStock juga
            $productionStock = ProductionStock::where('product_id', $item->product_id)->first();
            if ($productionStock && $difference != 0) {
                // kalau naik (200 - 100 = +100) → decrement stok
                // kalau turun (50 - 100 = -50) → increment stok
                if ($difference > 0) {
                    $productionStock->decrement('finished_product_stock', $difference);
                } elseif ($difference < 0) {
                    $productionStock->increment('finished_product_stock', abs($difference));
                }
            }
        }

        // ✅ Kalau semua item DO sudah shipped, update status DO
        $deliveryOrder = $deliveryList->deliveryOrder;
        $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
        if ($allShipped) {
            $deliveryOrder->update(['status' => 'Shipped']);
        }

        return redirect('/erp/deliveries/delivery-list')
            ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil diperbarui!");
    }

    public function printWaybill($id)
    {
        $deliveryList = DeliveryList::with([
            'deliveryOrder.order.customer',
            'items.product'
        ])->findOrFail($id);

        $order = $deliveryList->deliveryOrder->order;

        return view('erp.pages.deliveries.delivery-list.print-waybill', compact('deliveryList', 'order'));
    }

    public function uploadProof(Request $request, $id, $type)
    {
        $deliveryList = DeliveryList::findOrFail($id);

        if ($type === 'delivery') {
            $request->validate([
                'proof_delivery' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $fileName = 'delivery_' . time() . '.' . $request->file('proof_delivery')->extension();
            $path = $request->file('proof_delivery')->storeAs('uploads/delivery-proofs', $fileName, 'public');
            $deliveryList->proof_delivery = $path;
        }

        if ($type === 'waybill') {
            $request->validate([
                'proof_waybill' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $fileName = 'waybill_' . time() . '.' . $request->file('proof_waybill')->extension();
            $path = $request->file('proof_waybill')->storeAs('uploads/waybill-proofs', $fileName, 'public');
            $deliveryList->proof_waybill = $path;
        }

        // ✅ Jika keduanya sudah ada → status Finished
        // if ($deliveryList->proof_delivery && $deliveryList->proof_waybill) {
        //     $deliveryList->status = 'Finished';
        // }

        $deliveryList->save();

        return redirect()->back()->with('success', 'Bukti berhasil diupload!');
    }

    public function verify($id)
    {
        $deliveryList = DeliveryList::findOrFail($id);

        // ✅ Pastikan bukti sudah lengkap sebelum bisa diverifikasi
        if (!$deliveryList->proof_delivery || !$deliveryList->proof_waybill) {
            return redirect()->back()->with('error', 'Bukti pengantaran dan surat jalan belum lengkap.');
        }

        $deliveryList->status = 'Finished';
        $deliveryList->verified_by = Auth::id(); // kalau kamu mau catat siapa yang verifikasi
        $deliveryList->verified_at = now();
        $deliveryList->save();

        return redirect()->back()->with('success', 'Delivery List berhasil diverifikasi!');
    }
}
