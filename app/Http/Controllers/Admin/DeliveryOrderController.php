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
        $deliveryOrders = DeliveryOrder::with(['order.customer'])
            ->orderByDesc('id');

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

        if ($request->filled('status') && strtolower($request->status) != 'all') {
            $deliveryOrders->where('status', $request->status);
        }

        // 🔎 Search
        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $deliveryOrders->whereHas('order.customer', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $deliveryOrders->where('delivery_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        return DataTables::eloquent($deliveryOrders)
            ->addIndexColumn()
            ->addColumn('delivery_number', function ($do) {
                $date = Carbon::parse($do->delivery_date)->format('j M y');
                return '<div>
                    <div>' . $do->delivery_number . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';
            })
            ->addColumn('delivery_date', function ($do) {
                return Carbon::parse($do->delivery_date)->format('j M y');
            })
            ->addColumn('customer', function ($do) {
                return $do->order?->customer?->name ?? '-';
            })
            ->addColumn('status', function ($do) {
                $status = strtolower($do->status);
                switch ($status) {
                    case 'pending':
                        $badgeClass = 'bg-soft-warning text-warning';
                        break;
                    case 'finished':
                        $badgeClass = 'bg-soft-primary text-primary';
                        break;
                    case 'shipped':
                        $badgeClass = 'bg-soft-success text-success';
                        break;
                    case 'ongoing':
                        $badgeClass = 'bg-soft-danger text-danger';
                        break;
                    default:
                        $badgeClass = 'bg-secondary';
                        break;
                }
                return '<div class="badge ' . $badgeClass . '">' . ucfirst($do->status) . '</div>';
            })
            ->addColumn('products', function ($do) {
                return view('erp.pages.deliveries.delivery-orders.partials.product-list', compact('do'))->render();
            })
            ->addColumn('action', function ($do) {
                return view('erp.pages.deliveries.delivery-orders.partials.action-button', compact('do'))->render();
            })
            ->rawColumns(['delivery_number', 'status', 'action', 'products'])
            ->make(true);
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

        $shipments = $query->get();

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
