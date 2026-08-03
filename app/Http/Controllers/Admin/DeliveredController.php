<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\OrderItem;
use App\Models\Delivery;
use App\Models\DeliveryItemHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliveredController extends Controller
{
    public function getDeliveryList()
    {
        return view('erp.pages.deliveries.delivery-list.delivery-list');
    }

    public function dataDeliveryList(Request $request)
    {
        $orders = Order::with(['customer', 'orderItems.product', 'orderItems.productBundle.items.product'])
            ->whereIn('status', ['sale list']);

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $orders->whereDate('order_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $orders->whereBetween('order_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $orders->whereMonth('order_date', Carbon::now()->month)
                        ->whereYear('order_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $orders->whereBetween('order_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $orders->whereBetween('order_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $orders->whereYear('order_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $orders->whereBetween('order_date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $orders->whereHas('customer', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $orders->where('order_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $orders->whereDoesntHave('orderItems', function ($q) {
                    $q->whereColumn('completed_delivery', '==', 'completed_quantity');
                });
            } elseif ($request->progress_status === 'progress') {
                $orders->whereHas('orderItems', function ($q) {
                    $q->whereColumn('completed_delivery', '<', 'completed_quantity');
                });
            }
        }

        $orders = $orders->latest();

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('order_number', function ($order) {
                return $order->order_number;
            })
            ->addColumn('order_date', function ($order) {
                return Carbon::parse($order->order_date)->format('j M y');
            })
            ->addColumn('customer', function ($order) {
                return $order->customer->name;
            })
            ->addColumn('progress', function ($order) {
                return view('erp.pages.deliveries.delivery-list.partials.product-list', compact('order'))->render();
            })
            ->addColumn('delivered', function ($order) {
                return view('erp.pages.deliveries.delivery-list.partials.product-delivered', compact('order'))->render();
            })
            ->addColumn('shipping_address', function ($order) {
                return $order->shipping_address;
            })
            ->addColumn('total_amount', function ($order) {
                return 'Rp ' . number_format($order->total_amount, 0, ',', '.');
            })
            ->addColumn('grand_total', function ($order) {
                return 'Rp ' . number_format($order->grand_total, 0, ',', '.');
            })
            ->addColumn('payment_status', function ($order) {
                $payment_status = strtolower($order->payment_status);

                switch ($payment_status) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $order->payment_status . '</div>';
                    case 'unpaid':
                        return '<div class="badge bg-soft-danger text-danger">' . $order->payment_status . '</div>';
                    default:
                        return '<div class="badge bg-soft-warning text-warning">' . $order->payment_status . '</div>';
                }

                return $order->payment_status;
            })
            ->addColumn('status', function ($order) {
                $status = strtolower($order->status); // buat lebih aman lowercase dulu

                switch ($status) {
                    case 'sale list':
                        $badgeClass = 'bg-soft-info text-info';
                        break;
                }

                return '<div class="badge ' . $badgeClass . '">' . ucfirst($status) . '</div>';
            })
            // ->addColumn('notes', function ($order) {
            //     return $order->notes;
            // })
            ->addColumn('action', function ($order) {
                return view('erp.pages.deliveries.delivery-list.partials.action-button', compact('order'))->render();
            })
            ->rawColumns(['payment_status', 'progress', 'delivered', 'status', 'action'])
            ->make(true);
    }

    public function markAsDelivered($id)
    {
        $order = Order::find($id);

        if ($order->status === 'Waiting List' || $order->status === 'Complete List') {
            $order->status = 'Delivered';
            $order->save();

            return redirect()->back()->with('success', 'Order marked as Delivered.');
        }

        return redirect()->back()->with('warning', 'Order status is not Delivered.');
    }

    public function uploadImageDelivered()
    {
        $order = Order::find(request()->id);
        return view('erp.pages.deliveries.delivery-list.partials.upload-image', compact('order'));
    }

    public function putImageDelivered(Request $request, $id)
    {
        // dd($request->all());
        $request->validate([
            'delivery_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $order = Order::findOrFail($id);

        if ($request->hasFile('delivery_image')) {
            $image = $request->file('delivery_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('uploads/delivery_images');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $image->move($destinationPath, $imageName);

            // ✅ Set path ke kolom database
            $order->delivery_image = 'uploads/delivery_images/' . $imageName;

            // ✅ Simpan ke database
            $order->save();
        }

        return redirect('/erp/deliveries/delivery-list')->with('success', 'Image uploaded successfully.');
    }

    public function getDeliveryHistory(Request $request, $id)
    {
        $order = Order::with([
            'orderItems.product',
            'orderItems.deliveryItems.delivery'
        ])->findOrFail($id);

        return view('erp.pages.deliveries.delivery-list.delivery-history', compact('order'));
    }

    public function dataDeliveryHistory(Request $request, $id)
    {
        $deliveries = Delivery::with(['user', 'items.product'])
            ->where('order_id', $id)
            ->latest();

        if ($request->start_date && $request->end_date) {
            $deliveries->whereBetween('delivered_at', [$request->start_date, $request->end_date]);
        }

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $deliveries->whereDate('delivered_at', Carbon::today());
                    break;
                case 'last_7_days':
                    $deliveries->whereBetween('delivered_at', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $deliveries->whereMonth('delivered_at', Carbon::now()->month)
                        ->whereYear('delivered_at', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $deliveries->whereBetween('delivered_at', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $deliveries->whereBetween('delivered_at', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $deliveries->whereYear('delivered_at', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $deliveries->whereBetween('delivered_at', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        return DataTables::of($deliveries)
            ->addIndexColumn()
            ->addColumn('user_name', function ($delivery) {
                return $delivery->user->name ?? '-';
            })
            ->addColumn('invoice_number', function ($delivery) {
                return $delivery->invoice_number ?? '-';
            })
            ->addColumn('delivered_at', function ($delivery) {
                return Carbon::parse($delivery->delivered_at)->format('j M y');
            })
            ->addColumn('products', function ($delivery) {
                return view('erp.pages.deliveries.delivery-list.partials.product-delivered-histories', [
                    'items' => $delivery->items
                ])->render();
            })
            ->addColumn('delivery_proof', function ($delivery) {
                if ($delivery->delivery_proof) {
                    $imageUrl = asset($delivery->delivery_proof);
                    return '<a href="' . $imageUrl . '" data-lightbox="delivery-' . $delivery->id . '">
                        <img src="' . $imageUrl . '" alt="Delivery Image" class="img-fluid" style="max-width: 60px;">
                    </a>';
                }
                return '-';
            })
            ->rawColumns(['products', 'delivery_proof']) // untuk render img tag
            ->make(true);
    }

    public function create($id)
    {
        $order = Order::with(['orderItems.product'])
            ->whereIn('status', ['sale list'])
            ->findOrFail($id);

        return view('erp.pages.deliveries.delivery-list.create-delivery', compact('order'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'delivered_at' => 'required|date',
            'delivery_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes' => 'nullable',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.delivered_quantity' => 'required|integer|min:1',
        ]);

        $path = null;
        if ($request->hasFile('delivery_proof')) {
            $filename = time() . '_' . $request->file('delivery_proof')->getClientOriginalName();

            // Simpan ke public/upload/delivery_proofs
            $destination = public_path('upload/delivery_proofs');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true); // Buat folder jika belum ada
            }

            $request->file('delivery_proof')->move($destination, $filename);

            // Simpan path relatif agar bisa dipanggil di view
            $path = 'upload/delivery_proofs/' . $filename;
        }

        $order = Order::findOrFail($request->order_id);

        // Buat record utama delivery
        $delivery = Delivery::create([
            'order_id' => $request->order_id,
            'invoice_number' => $order->order_number,
            'user_id' => $request->user()->id,
            'delivery_proof' => $path,
            'notes' => $request->notes,
            'delivered_at' => $request->delivered_at,
        ]);

        foreach ($request->items as $item) {
            DeliveryItemHistory::create([
                'delivery_id' => $delivery->id,
                'order_item_id' => $item['order_item_id'],
                'delivered_quantity' => $item['delivered_quantity'],
                'kurir' => $item['kurir'],
                'note' => $item['note'],
            ]);

            $orderItem = OrderItem::findOrFail($item['order_item_id']);
            $orderItem->increment('completed_delivery', $item['delivered_quantity']);
        }

        return redirect('/erp/delivery-list')->with('success', 'Pengiriman berhasil dicatat.');
    }

    public function printWaybill($id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);
        return view('erp.pages.deliveries.delivery-list.print-waybill', compact('order'));
    }
}
