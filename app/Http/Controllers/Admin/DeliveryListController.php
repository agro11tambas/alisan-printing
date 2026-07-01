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
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DeliveryListController extends Controller
{
    public function getDeliveryList()
    {
        return view('erp.pages.deliveries.delivery-list.delivery-list');
    }

    public function dataDeliveryList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $deliveryLists = DeliveryList::with(['deliveryOrder', 'items.product']);

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

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $deliveryLists->whereHas('items.product', function ($q) use ($productKeyword) {
                // gunakan COLLATE biar bisa handle tanda kurung
                $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
            });
        }

        if ($request->filled('status') && strtolower($request->status) != 'all') {
            $deliveryLists->where('status', $request->status);
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
                case 'customer':
                    $keyword = '%' . $request->search_keyword . '%';

                    $deliveryLists->where(function ($q) use ($keyword) {

                        // 🔍 Cari berdasarkan nama customer
                        $q->whereHas('deliveryOrder.order.customer', function ($sub) use ($keyword) {
                            $sub->where('name', 'like', $keyword);
                        });

                        // 🔍 Cari berdasarkan business name
                        $q->orWhereHas('deliveryOrder.order.customerAddress', function ($sub) use ($keyword) {
                            $sub->where('business_name', 'like', $keyword);
                        });
                    });
                    break;
                default:
                    $deliveryLists->where('shipment_number', 'like', '%' . $request->search_keyword . '%');
                    break;
            }
        }

        // ✅ Hitung total sebelum paginasi
        $totalQuery = clone $deliveryLists;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        // $data = $deliveryLists->latest()->skip($start)->take($length)->get();
        $status = strtolower($request->input('status', ''));

        if ($status === 'finished') {
            $deliveryLists->orderBy('shipment_date', 'desc'); // terbaru dulu
        } elseif ($status === 'ongoing') {
            $deliveryLists->orderBy('shipment_date', 'asc'); // terlama dulu
        } else {
            $deliveryLists->orderBy('shipment_date', 'desc'); // default
        }

        $data = $deliveryLists->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($dl) {
                $date = Carbon::parse($dl->created_at)->format('j M y H:i');
                $badges = '';

                if (!empty($dl->proof_photos)) {
                    $photos = json_decode($dl->proof_photos, true);
                    if (!empty($photos)) {
                        $badges .= '<div class="badge bg-soft-success text-success me-1">Foto Diupload</div>';
                    }
                }

                if ($dl->printed) {
                    $badges .= '<div class="badge bg-soft-warning text-warning me-1">Sudah Print</div>';
                }

                $shipmentNumber = '
                <div>
                    ' . ($badges ? '<div class="mb-1">' . $badges . '</div>' : '') . '
                    <div>' . e($dl->shipment_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                $driver = e($dl->driver ?? '-');
                $vehicle = e($dl->vehicle ?? '-');
                // $customer = e($dl->deliveryOrder->customer ?? '-');

                $businessName = e($dl->deliveryOrder->order?->customerAddress?->business_name ?? '-');
                $customerName = e($dl->deliveryOrder->order?->customer?->name ?? '-');

                $customerHtml = '
                    <div style="white-space: normal; word-break: break-word; max-width:180px;">
                        <div class="fw-semibold">' . $businessName . '</div>
                        <small class="text-muted">' . $customerName . '</small>
                    </div>
                ';

                $address = $dl->deliveryOrder->shipping_address ?? '-';
                $mapLink = $dl->deliveryOrder->google_map_link ?? '#';
                $addressHtml = '
                <div class="d-flex flex-column align-items-start" style="white-space: normal; word-break: break-word; max-width: 200px;">
                    <div>' . e($address) . '</div>
                    <a href="' . $mapLink . '" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat di Maps</a>
                </div>';

                $status = strtolower($dl->status);
                $badgeClass = match ($status) {
                    'ongoing' => 'bg-soft-primary text-primary',
                    'finished' => 'bg-soft-success text-success',
                    default => 'bg-secondary',
                };
                $statusHtml = '<div class="badge ' . $badgeClass . '">' . ucfirst($dl->status) . '</div>';

                $itemsHtml = view('erp.pages.deliveries.delivery-list.partials.product-list', compact('dl'))->render();

                // 🧾 Waybill proof
                if ($dl->proof_waybill) {
                    $src = asset('storage/' . $dl->proof_waybill);
                    $waybillProof = '
                    <a href="' . $src . '" data-lightbox="waybill-' . $dl->id . '">
                        <img src="' . $src . '" width="50" height="50"
                            style="border-radius: 8px; object-fit: cover; object-position: center;" alt="Waybill Proof">
                    </a>';
                } else {
                    $waybillProof = '<span class="text-muted">-</span>';
                }

                // 📦 Delivery proof
                if ($dl->proof_delivery) {
                    $src = asset('storage/' . $dl->proof_delivery);
                    $deliveryProof = '
                    <a href="' . $src . '" data-lightbox="delivery-' . $dl->id . '">
                        <img src="' . $src . '" width="50" height="50"
                            style="border-radius: 8px; object-fit: cover; object-position: center;" alt="Delivery Proof">
                    </a>';
                } else {
                    $deliveryProof = '<span class="text-muted">-</span>';
                }

                // 📷 Proof photos
                if (empty($dl->proof_photos)) {
                    $proofPhotos = '<span class="text-muted">No Proof</span>';
                } else {
                    $photos = json_decode($dl->proof_photos, true);
                    if (empty($photos)) {
                        $proofPhotos = '<span class="text-muted">No Proof</span>';
                    } else {
                        $proofPhotos = '
                        <button class="btn btn-sm btn-outline-info btn-preview-proof" 
                            data-id="' . $dl->id . '" 
                            data-shipment="' . e($dl->shipment_number) . '" 
                            data-photos=\'' . json_encode($photos) . '\'>
                            <i class="feather-eye"></i> Preview
                        </button>';
                    }
                }

                // 🧱 Items mobile card
                if ($dl->items->isEmpty()) {
                    $itemsMobile = '<span class="text-muted">No items</span>';
                } else {
                    $rows = '';
                    foreach ($dl->items as $item) {
                        $product = e($item->product->name ?? '-');
                        $qty = number_format($item->shipped_quantity ?? 0);
                        $rows .= "
                            <tr>
                                <td style='padding:6px 8px; font-size:12px; border-bottom:1px solid #f0f0f0;'>$product</td>
                                <td style='padding:6px 8px; text-align:right; font-size:12px; color:#16a34a; border-bottom:1px solid #f0f0f0;'>x$qty</td>
                            </tr>
                        ";
                    }

                    // ✅ Cek izin upload bukti
                    $canUploadProof = (
                        (empty($dl->proof_photos) || $dl->proof_photos === '[]')
                        && in_array(Auth::user()->role, ['Kurir', 'Admin', 'Owner'])
                    );

                    // ✅ Tombol Lihat di Maps
                    $mapButton = $dl->deliveryOrder->google_map_link
                        ? "<a href='" . e($dl->deliveryOrder->google_map_link) . "' 
                                target='_blank'
                                style='display:inline-block;margin-top:8px;padding:6px 10px;
                                background:#2563eb;color:white;border-radius:6px;
                                font-size:12px;text-decoration:none;'>
                                <i class=\"feather-map-pin\"></i> Lihat di Maps
                            </a>"
                        : "<span class='text-muted' style='font-size:12px;'>Tidak ada link Maps</span>";

                    // ✅ Tombol Upload Bukti
                    $uploadButton = $canUploadProof
                        ? "<a href='javascript:void(0);' 
                                class='btn-upload-proof'
                                data-id='{$dl->id}'
                                data-url='" . route('delivery-list.upload-proof', $dl->id) . "'
                                data-photos='" . ($dl->proof_photos ?? '[]') . "'
                                style='display:inline-block;margin-top:8px;margin-left:6px;padding:6px 10px;
                                background:#16a34a;color:white;border-radius:6px;
                                font-size:12px;text-decoration:none;'>
                                    <i class=\"feather-upload\"></i> Upload Bukti
                            </a>"
                        : '';

                    // ✅ Template tampilan mobile
                    $itemsMobile = "
                        <div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;
                            padding:12px 14px;margin-bottom:10px;box-shadow:0 1px 3px rgba(0,0,0,0.08);
                            font-size:13px;line-height:1.5;'>
            
                            " . ($badges ? "<div class='mb-2'>$badges</div>" : "") . "
            
                            <div style='margin-bottom:10px;'>
                                <div><strong>Shipment:</strong> <span style='color:#2563eb;'>{$dl->shipment_number}</span></div>
                                <div><strong>Business Name:</strong> " . e($dl->deliveryOrder->order?->customerAddress?->business_name ?? '-') . "</div>
                                <div><strong>Customer:</strong> " . e($dl->deliveryOrder->customer ?? '-') . "</div>                                
                                <div><strong>Address:</strong><br>
                                <div style='color:#4b5563;max-width:300px;white-space:normal;word-break:break-word;overflow-wrap:break-word;'>
                                    " . e($dl->deliveryOrder->shipping_address ?? '-') . "
                                </div>
                            </div>

                            <div class='d-flex flex-wrap align-items-center'>
                                $mapButton
                                $uploadButton
                            </div>
                        </div>

                        <div>
                            <strong>Items:</strong>
                            <div class='table-responsive' style='margin-top:6px;'>
                                <table style='width:100%; border-collapse:collapse;'>
                                    <thead>
                                        <tr style=\"background:#f3f4f6; text-align:left;\">
                                            <th style='padding:6px 8px; font-size:12px; font-weight:600; color:#374151;'>Product</th>
                                            <th style='padding:6px 8px; text-align:right; font-size:12px; font-weight:600; color:#374151;'>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>$rows</tbody>
                                </table>
                            </div>
                        </div>
                    </div>";
                }


                // ⚙️ Action button partial
                $action = view('erp.pages.deliveries.delivery-list.partials.action-button', compact('dl'))->render();

                return [
                    'id' => $dl->id,
                    'shipment_number' => $shipmentNumber,
                    'shipment_date' => $date,
                    'driver' => $driver,
                    'vehicle' => $vehicle,
                    'customer' => $customerHtml,
                    'address' => $addressHtml,
                    'status' => $statusHtml,
                    'items' => $itemsHtml,
                    'waybill_proof' => $waybillProof,
                    'delivery_proof' => $deliveryProof,
                    'items_mobile' => $itemsMobile,
                    'proof_photos' => $proofPhotos,
                    'action' => $action,
                    'created_at' => $dl->created_at,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
        $deliveryOrder = DeliveryOrder::with([
            'items' => fn($q) => $q->orderBy('id', 'asc'),
            'items.product',
            'items.deliveryListItems'
        ])->findOrFail($doId);

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

    public function store(Request $request, $doId)
    {
        $request->validate([
            'shipment_number' => 'required|string|max:255|unique:delivery_lists,shipment_number',
            'shipment_date'   => 'required|date',
            'driver_id'       => 'required|exists:users,id',
            'vehicle'         => 'nullable|string|max:255',
            'note'            => 'nullable|string',
            'items'           => 'required|array',
            'items.*.shipped_quantity' => 'nullable|numeric|min:0',
        ]);

        $deliveryOrder = DeliveryOrder::with('order')->findOrFail($doId);

        // 🔹 Ambil data driver
        $driver = User::find($request->driver_id);

        $deliveryList = DeliveryList::create([
            'delivery_order_id' => $deliveryOrder->id,
            'shipment_number'   => $request->shipment_number,
            'shipment_date'     => $request->shipment_date,
            'driver_id'         => $driver->id,
            'driver'            => $driver->name,
            'vehicle'           => $request->vehicle,
            'note'              => $request->note,
            'status'            => 'Ongoing',
        ]);

        foreach ($request->items as $item) {
            $inputQty = (float) ($item['shipped_quantity'] ?? 0);

            if ($inputQty > 0) {
                $doItem = DeliveryOrderItem::find($item['delivery_order_item_id']);

                if (!$doItem) {
                    continue;
                }

                $unitConversionValue = (float) ($doItem->unit_conversion_value ?? 1);

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $shippedBaseQty = $inputQty * $unitConversionValue;

                $deliveryList->items()->create([
                    'delivery_order_item_id' => $doItem->id,
                    'product_id'             => $doItem->product_id,
                    'shipped_quantity'       => $shippedBaseQty,

                    'product_unit_conversion_id' => $doItem->product_unit_conversion_id,
                    'unit_name'                  => $doItem->unit_name,
                    'unit_conversion_value'      => $unitConversionValue,

                    'note'                   => $item['note'] ?? null,
                ]);

                $newShippedQty = $doItem->shipped_qty + $shippedBaseQty;

                $doItem->update([
                    'shipped_qty' => $newShippedQty,
                    'status'      => $newShippedQty >= $doItem->progress_qty ? 'Shipped' : $doItem->status,
                ]);

                // ✅ Hanya decrement jika mode order BUKAN polosan
                if ($deliveryOrder->order && $deliveryOrder->order->mode !== 'polosan') {
                    $productionStock = ProductionStock::where('product_id', $item['product_id'])->first();
                    if ($productionStock) {
                        $productionStock->decrement('finished_product_stock', $shippedBaseQty);
                    }
                }
            }
        }

        // ✅ Update status DeliveryOrder jika semua item sudah shipped
        // $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
        // if ($allShipped) {
        //     $deliveryOrder->update(['status' => 'Finished']);
        // }

        return redirect('/erp/deliveries/delivery-orders')
            ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil dibuat!");
    }

    public function edit($id)
    {
        $deliveryList = DeliveryList::with(['items.deliveryOrderItem.product'])->findOrFail($id);

        // 🔹 Ambil daftar user dengan role Kurir
        $drivers = \App\Models\User::where('role', 'Kurir')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('erp.pages.deliveries.delivery-list.edit-delivery-list', compact('deliveryList', 'drivers'));
    }

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
            $item = DeliveryListItem::with('deliveryOrderItem')->find($itemData['delivery_list_item_id']);
            if (!$item) continue;

            $doItem = $item->deliveryOrderItem;
            if (!$doItem) continue;

            $unitConversionValue = (float) ($doItem->unit_conversion_value ?? $item->unit_conversion_value ?? 1);

            if ($unitConversionValue <= 0) {
                $unitConversionValue = 1;
            }

            $oldQty = (float) $item->shipped_quantity; // base lama
            $inputQty = (float) ($itemData['shipped_quantity'] ?? 0); // input display
            $newQty = $inputQty * $unitConversionValue; // base baru

            $difference = $newQty - $oldQty;

            $item->update([
                'shipped_quantity' => $newQty,

                'product_unit_conversion_id' => $doItem->product_unit_conversion_id,
                'unit_name'                  => $doItem->unit_name,
                'unit_conversion_value'      => $unitConversionValue,

                'note' => $itemData['note'] ?? null,
            ]);

            // ✅ Update ready_qty dan shipped_qty di DeliveryOrderItem
            $doItem = $item->deliveryOrderItem;
            if ($doItem && $difference != 0) {
                // $newReadyQty   = $doItem->ready_qty - $difference; // kurangi / tambahkan berdasarkan selisih
                $newShippedQty = $doItem->shipped_qty + $difference;

                $doItem->update([
                    // 'ready_qty'   => max(0, $newReadyQty),
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
        // $deliveryOrder = $deliveryList->deliveryOrder;
        // $allShipped = $deliveryOrder->items()->where('status', '!=', 'Shipped')->count() === 0;
        // if ($allShipped) {
        //     $deliveryOrder->update(['status' => 'Finished']);
        // }

        return redirect('/erp/deliveries/delivery-list')
            ->with('success', "Delivery List {$deliveryList->shipment_number} berhasil diperbarui!");
    }

    public function printWaybill($id)
    {
        $deliveryList = DeliveryList::with([
            'deliveryOrder.order.customer',
            'items.product'
        ])->findOrFail($id);

        // ✅ Set kolom printed = true jika belum pernah diprint
        if (!$deliveryList->printed) {
            $deliveryList->update(['printed' => true]);
        }

        $order = $deliveryList->deliveryOrder->order;

        return view('erp.pages.deliveries.delivery-list.print-waybill', compact('deliveryList', 'order'));
    }


    public function uploadProof(Request $request, $id)
    {
        $deliveryList = DeliveryList::findOrFail($id);

        $request->validate([
            'proof_photos'   => 'required|array',
            'proof_photos.*' => 'image|mimes:jpg,jpeg,png|max:10240', // max 10MB per foto
        ]);

        $savedPhotos = [];

        // ✅ simpan di folder uploads/proof-photos di luar /public
        $uploadPath = public_path('uploads/proof-photos');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        foreach ($request->file('proof_photos', []) as $photo) {
            $fileName = 'proof_' . time() . '_' . uniqid() . '.' . $photo->extension();
            $photo->move($uploadPath, $fileName);
            $savedPhotos[] = 'uploads/proof-photos/' . $fileName;
        }

        // Simpan sebagai JSON array (biar bisa multi foto)
        $deliveryList->proof_photos = json_encode($savedPhotos);
        $deliveryList->save();

        return back()->with('success', 'Bukti berhasil diupload!');
    }


    public function verify($id)
    {
        $deliveryList = DeliveryList::findOrFail($id);

        // ✅ Pastikan bukti sudah lengkap sebelum bisa diverifikasi
        // if (!$deliveryList->proof_photos) {
        //     return redirect()->back()->with('error', 'Bukti pengantaran dan surat jalan belum lengkap.');
        // }

        $deliveryList->status = 'Finished';
        $deliveryList->verified_by = Auth::id(); // kalau kamu mau catat siapa yang verifikasi
        $deliveryList->verified_at = now();
        $deliveryList->save();

        return redirect()->back()->with('success', 'Delivery List berhasil diverifikasi!');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $deliveryList = DeliveryList::withTrashed()->findOrFail($id);

            // 🔹 Ambil semua item delivery list (termasuk soft deleted)
            $items = DeliveryListItem::withTrashed()
                ->where('delivery_list_id', $deliveryList->id)
                ->get();

            // 🔹 Rollback shipped_qty ke delivery_order_items
            foreach ($items as $item) {
                if ($item->delivery_order_item_id && $item->shipped_quantity > 0) {
                    DB::table('delivery_order_items')
                        ->where('id', $item->delivery_order_item_id)
                        ->decrement('shipped_qty', $item->shipped_quantity);
                }

                // 🔹 Hapus permanen item
                $item->forceDelete();
            }

            // 🔹 Hapus parent list
            $deliveryList->forceDelete();

            // 🔹 Cek dan update status DeliveryOrder jika masih ada
            if ($deliveryList->delivery_order_id) {
                $deliveryOrder = DB::table('delivery_orders')
                    ->where('id', $deliveryList->delivery_order_id)
                    ->first();

                if ($deliveryOrder) {
                    // Cek apakah masih ada list aktif untuk order ini
                    $remainingLists = DB::table('delivery_lists')
                        ->where('delivery_order_id', $deliveryOrder->id)
                        ->count();

                    // Jika semua list sudah dihapus, ubah status jadi Ongoing
                    if ($remainingLists === 0) {
                        DB::table('delivery_orders')
                            ->where('id', $deliveryOrder->id)
                            ->update(['status' => 'Ongoing']);
                    }
                }
            }

            DB::commit();
            return back()->with('success', "Delivery List {$deliveryList->shipment_number} berhasil dihapus dan stok terkoreksi.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus Delivery List: ' . $e->getMessage());
        }
    }
}
