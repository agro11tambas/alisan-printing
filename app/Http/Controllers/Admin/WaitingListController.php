<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderProgress;
use Carbon\Carbon;

class WaitingListController extends Controller
{
    public function getWaitingList()
    {
        return view('erp.pages.production.waiting-list.waiting-list');
    }

    public function dataWaitingList(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $progresses = OrderProgress::with([
            'order.customer',
            'items.product.productionStocks'
        ])->orderBy('created_at', 'desc');

        // ✅ Filter tanggal
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $progresses->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $progresses->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $progresses->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $progresses->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $progresses->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $progresses->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $progresses->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        // ✅ Filter pencarian
        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $progresses->whereHas('order.customer', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $progresses->whereHas('order', function ($q) use ($request) {
                    $q->where('order_number', 'like', '%' . $request->search_keyword . '%');
                });
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $progresses->whereHas('items.product', function ($q) use ($productKeyword) {
                // gunakan COLLATE biar bisa handle tanda kurung
                $q->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
            });
        }

        // ✅ Filter status progress
        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $progresses->whereDoesntHave('items', function ($q) {
                    $q->whereColumn('completed_quantity', '<', 'quantity');
                });
            } elseif ($request->progress_status === 'progress') {
                $progresses->whereHas('items', function ($q) {
                    $q->whereColumn('completed_quantity', '<', 'quantity');
                });
            }
        }

        // ✅ Hitung total data untuk lazy-load
        $totalQuery = clone $progresses;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $progresses->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load / infinite scroll)
        return response()->json([
            'data' => $data->map(function ($progress) {
                $date = Carbon::parse($progress->date)->format('j M y');

                // 🏷️ Edited badge
                $editedBadge = $progress->status_edited == 1
                    ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                    : '';

                // ✅ Completed badge
                $completeBadge = $progress->items->every(fn($item) => $item->completed_quantity >= $item->quantity)
                    ? '<div><span class="badge bg-soft-success text-success mb-1">Completed</span></div>'
                    : '';

                // 🧾 Invoice display
                $invoiceNumberHtml = $completeBadge . '
                <div>
                    <div>' . e($progress->invoice_number) . $editedBadge . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';

                $customerHtml = '
                    <div>
                        <div class="fw-semibold">' . e($progress->order?->customerAddress?->business_name ?? '-') . '</div>
                        <small class="text-muted">' . e($progress->order?->customer?->name ?? '-') . '</small>
                    </div>
                ';

                // 📦 Product progress partial
                $progressView = view('erp.pages.production.waiting-list.partials.product-progress', compact('progress'))->render();

                // 📍 Shipping address
                $shipping = e($progress->order->shipping_address ?? '-');

                // ⚙️ Action button partial
                $allCompleted = $progress->items->every(function ($item) {
                    return ($item->completed_quantity ?? 0) >= ($item->quantity ?? 0);
                });
                $actionButtons = view('erp.pages.production.waiting-list.partials.action-button', compact('progress', 'allCompleted'))->render();

                return [
                    'id' => $progress->id,
                    'invoice_number' => $invoiceNumberHtml,
                    'customer' => $customerHtml,
                    'progress' => $progressView,
                    'shipping_address' => $shipping,
                    'action' => $actionButtons,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }


    public function getCompleteList()
    {
        return view('erp.pages.production.waiting-list.complete-list');
    }

    public function dataCompleteList(Request $request)
    {
        $orders = Order::with('customer')
            ->where('status', 'complete list');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $orders->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        // Filter order_number
        if ($request->filled('order_number')) {
            $orders->where('order_number', 'like', '%' . $request->order_number . '%');
        }

        if ($request->filled('customer_name')) {
            $orders->whereHas('customer', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }

        $orders = $orders->latest()->get();

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
                return view('erp.pages.production.waiting-list.partials.product-list', compact('order'))->render();
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
                    case 'complete list':
                        $badgeClass = 'bg-soft-success text-success';
                        break;
                }

                return '<div class="badge ' . $badgeClass . '">' . ucfirst($status) . '</div>';
            })
            // ->addColumn('notes', function ($order) {
            //     return $order->notes;
            // })
            ->addColumn('action', function ($order) {
                return view('erp.pages.production.waiting-list.partials.action-button', compact('order'))->render();
            })
            ->rawColumns(['payment_status', 'progress', 'status', 'action'])
            ->make(true);
    }

    public function markAsCompleteList($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'Waiting List') {
            $order->status = 'Complete List';
            $order->save();

            return redirect()->back()->with('success', 'Order marked as Complete List.');
        }

        return redirect()->back()->with('warning', 'Order status is not Waiting List.');
    }

    public function markAsDelivery($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'Complete List') {
            $order->status = 'Delivery';
            $order->save();

            return redirect()->back()->with('success', 'Order marked as Delivery.');
        }

        return redirect()->back()->with('warning', 'Order status is not Complete List.');
    }
}
