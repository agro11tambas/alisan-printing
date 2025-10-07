<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $deliveryOrders = $deliveryOrders->get();

        return DataTables::of($deliveryOrders)
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
                    case 'draft':
                        $badgeClass = 'bg-soft-dark text-dark';
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
}
