<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryList;
use App\Models\DeliveryListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\DeliveryOrder;
use Yajra\DataTables\Facades\DataTables;

class DeliveryOrderController extends Controller
{
    public function getDeliveryOrders()
    {
        return view('erp.pages.deliveries.delivery-orders.delivery-orders');
    }

    public function dataDeliveryOrders(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $deliveryOrders = DeliveryOrder::with([
            'order.customer',
            'order.customerAddress',              // 🔥 dipakai kolom customer, tanpa ini N+1
            'items.product',                      // 🔥 load semua item barang
            'items.orderProgress.items',          // 🔥 load progress per item
            'items.orderProgressItem',            // 🔥 dipakai partial product-list, relasi ini beda dari orderProgress.items
            'items.deliveryListItems.shipment',   // 🔥 load list pengiriman
        ]);

        // 🔎 Filter by date
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $deliveryOrders->whereDate('delivery_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $deliveryOrders->whereBetween('delivery_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $deliveryOrders->whereMonth('delivery_date', Carbon::now()->month)
                        ->whereYear('delivery_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $deliveryOrders->whereBetween('delivery_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $deliveryOrders->whereBetween('delivery_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $deliveryOrders->whereYear('delivery_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $deliveryOrders->whereBetween('delivery_date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // 🔎 Filter by status
        if ($request->filled('status') && strtolower($request->status) != 'all') {
            $deliveryOrders->where('status', $request->status);
        }

        // 🔎 Search
        if ($request->filled('search_keyword')) {
            $keyword =  '%' . $request->search_keyword . '%';

            if ($request->search_type === 'customer') {
                $deliveryOrders->where(function ($q) use ($keyword) {

                    // 🔍 Cari berdasarkan nama customer
                    $q->whereHas('order.customer', function ($sub) use ($keyword) {
                        $sub->where('name', 'like', $keyword);
                    });

                    // 🔍 Cari berdasarkan business_name
                    $q->orWhereHas('order.customerAddress', function ($sub) use ($keyword) {
                        $sub->where('business_name', 'like', $keyword);
                    });
                });
            } else {
                // 🔍 Search delivery number
                $deliveryOrders->where('delivery_number', 'like', $keyword);
            }
        }

        // 🔎 Search by product name
        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $deliveryOrders->whereHas('items.product', function ($q) use ($productKeyword) {
                $q->where('name', 'like', "%{$productKeyword}%");
            });
        }

        $status = strtolower($request->input('status', ''));

        if (in_array($status, ['finished', 'shipped'])) {
            $deliveryOrders->orderBy('delivery_date', 'desc');
        } elseif (in_array($status, ['pending', 'ongoing'])) {
            $deliveryOrders->orderBy('delivery_date', 'asc');
        } else {
            $deliveryOrders->orderByDesc('id'); // default
        }

        // ✅ Satu query saja, tanpa count() terpisah
        [$data, $hasMore] = $this->lazyLoadPage($deliveryOrders, $start, $length);

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($do) {
                $date = Carbon::parse($do->created_at)->format('j M y H:i');
                $deliveryNumberHtml = '
                <div>
                    <div>' . e($do->delivery_number) . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                // $customer = e($do->order?->customer?->name ?? '-');

                $businessName = e($do->order?->customerAddress?->business_name ?? '-');
                $customerName = e($do->order?->customer?->name ?? '-');

                $customerHtml = '
                    <div style="white-space: normal; word-break: break-word; max-width:180px;">
                        <div class="fw-semibold">' . $businessName . '</div>
                        <small class="text-muted">' . $customerName . '</small>
                    </div>
                ';

                $status = strtolower($do->status);
                $badgeClass = match ($status) {
                    'pending' => 'bg-soft-warning text-warning',
                    'finished' => 'bg-soft-primary text-primary',
                    'shipped' => 'bg-soft-success text-success',
                    'ongoing' => 'bg-soft-danger text-danger',
                    default => 'bg-secondary',
                };
                $statusHtml = '<div class="badge ' . $badgeClass . '">' . ucfirst($do->status) . '</div>';

                // 📦 Products partial
                $productsHtml = view('erp.pages.deliveries.delivery-orders.partials.product-list', compact('do'))->render();

                // ⚙️ Action partial
                $actionHtml = view('erp.pages.deliveries.delivery-orders.partials.action-button', compact('do'))->render();

                $orderNotesValue = $do->order?->notes;

                $orderNotes = $orderNotesValue
                    ? '<div class="" style="white-space:normal; word-break:break-word; max-width:220px;">'
                    . e($orderNotesValue) .
                    '</div>'
                    : '<div class="">-</div>';


                return [
                    'id' => $do->id,
                    'delivery_number' => $deliveryNumberHtml,
                    'delivery_date' => $date,
                    'customer' => $customerHtml,
                    'status' => $statusHtml,
                    'products' => $productsHtml,
                    'order_notes' => $orderNotes,
                    'action' => $actionHtml,
                    'created_at' => $do->created_at,
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    public function getItems($id)
    {
        $deliveryOrder = DeliveryOrder::with(['items.product'])->findOrFail($id);

        $items = $deliveryOrder->items->map(function ($item) {
            // hitung qty yang sudah dikirim
            $alreadyShipped = $item->deliveryListItems()->sum('shipped_quantity');

            return [
                'id'              => $item->id,
                'product_id'      => $item->product_id,
                'product_name'    => $item->product->name ?? '-',
                'ordered_qty'     => $item->quantity,
                'already_shipped' => $alreadyShipped,
            ];
        });

        return response()->json([
            'items' => $items
        ]);
    }

    public function generateNumber($doId)
    {
        $deliveryOrder = DeliveryOrder::findOrFail($doId);

        // panggil service/helper
        $shipmentNumber = \App\Services\DeliveryListService::generateShipmentNumber($deliveryOrder);

        return response()->json([
            'number' => $shipmentNumber
        ]);
    }

    public function getDeliveryHistory($id)
    {
        $delivery = DeliveryOrder::with(['items.product', 'shipments.items.product', 'user'])->findOrFail($id);
        return view('erp.pages.deliveries.delivery-orders.history-delivery-order', compact('delivery'));
    }

    public function dataDeliveryHistory(Request $request, $id)
    {
        $query = DeliveryList::with(['driverUser', 'items.product'])
            ->where('delivery_order_id', $id)
            ->latest();

        if ($request->filter) {
            $query->when(true, function ($q) use ($request) {
                switch ($request->filter) {
                    case 'today':
                        $q->whereDate('shipment_date', Carbon::today());
                        break;
                    case 'last_7_days':
                        $q->whereBetween('shipment_date', [Carbon::now()->subDays(7), Carbon::now()]);
                        break;
                    case 'this_month':
                        $q->whereMonth('shipment_date', Carbon::now()->month)
                            ->whereYear('shipment_date', Carbon::now()->year);
                        break;
                    case 'last_30_days':
                        $q->whereBetween('shipment_date', [Carbon::now()->subDays(30), Carbon::now()]);
                        break;
                    case 'year_to_date':
                        $q->whereBetween('shipment_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                        break;
                    case 'yearly':
                        $q->whereYear('shipment_date', Carbon::now()->year);
                        break;
                    case 'custom':
                        if ($request->filled('start_date') && $request->filled('end_date')) {
                            $q->whereBetween('shipment_date', [$request->start_date, $request->end_date]);
                        }
                        break;
                }
            });
        }

        $shipments = $query;

        return DataTables::of($shipments)
            ->addIndexColumn()
            ->addColumn('driver_name', fn($row) => $row->driverUser?->name ?? $row->driver ?? '-')
            ->addColumn('shipment_date', fn($row) => \Carbon\Carbon::parse($row->shipment_date)->format('d M Y'))
            ->addColumn('products', function ($row) {
                $items = $row->items;
                return view('erp.pages.deliveries.delivery-orders.partials.shipment-products', compact('items'))->render();
            })
            ->rawColumns(['products'])
            ->make(true);
    }

    public function updateDeliveryHistory(Request $request, $id)
    {
        $request->validate([
            'shipped_quantity' => 'required|integer|min:0',
            'note'             => 'nullable|string|max:255',
        ]);

        $item = DeliveryListItem::with('deliveryOrderItem')->findOrFail($id);

        // 🔹 Simpan nilai lama untuk hitung selisih
        $oldQty = $item->shipped_quantity ?? 0;
        $newQty = (int) $request->shipped_quantity;
        $difference = $newQty - $oldQty; // bisa positif atau negatif

        // 🔹 Update item shipment
        $item->update([
            'shipped_quantity' => $newQty,
            'note'             => $request->note,
        ]);

        // 🔹 Update shipped_qty di tabel delivery_order_items
        if ($item->deliveryOrderItem) {
            $parent = $item->deliveryOrderItem;
            $updatedShipped = max(0, ($parent->shipped_qty ?? 0) + $difference); // pastikan tidak minus
            $parent->update(['shipped_qty' => $updatedShipped]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment item updated successfully and delivery item updated.'
        ]);
    }
}
