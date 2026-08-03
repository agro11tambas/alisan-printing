<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignItem;
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

        $baseQuery = OrderProgress::with([
            'items.product.categories'
        ]);

        // Filter harus diterapkan ke baseQuery juga
        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $baseQuery->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $baseQuery->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $baseQuery->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $baseQuery->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $baseQuery->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $baseQuery->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $baseQuery->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
            }
        }

        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword . '%';

            if ($request->search_type === 'customer') {
                $baseQuery->where(function ($q) use ($keyword) {

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
                // 🔍 Search order_number seperti biasa
                $baseQuery->whereHas('order', function ($q) use ($keyword) {
                    $q->where('order_number', 'like', $keyword);
                });
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $baseQuery->whereHas('items.product', function ($q) use ($productKeyword) {
                $q->where(function ($sub) use ($productKeyword) {
                    $sub->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"])
                        ->orWhereRaw("LOWER(sku) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
                });
            });
        }

        if ($request->filled('progress_status')) {
            if ($request->progress_status === 'completed') {
                $baseQuery->whereDoesntHave('items', function ($q) {
                    $q->whereRaw('completed_quantity < (quantity * COALESCE(unit_conversion_value, 1))');
                });
            } elseif ($request->progress_status === 'progress') {
                $baseQuery->whereHas('items', function ($q) {
                    $q->whereRaw('completed_quantity < (quantity * COALESCE(unit_conversion_value, 1))');
                });
            }
        }

        // 1️⃣ Hitung total data untuk lazy load

        // 1️⃣ Hitung pending dari ORDER PROGRESS (yang sudah ada)
        $orderProgressPending = (float) \App\Models\OrderProgressItem::query()
            ->whereHas('product.categories', function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%sablon%']);
            })
            ->selectRaw('SUM(CASE WHEN quantity > COALESCE(completed_quantity, 0) THEN quantity - COALESCE(completed_quantity, 0) ELSE 0 END) AS total')
            ->value('total');

        // 2️⃣ Tambahkan TOTAL DESIGN ITEM qty dimana status = Pending
        $designPending = DesignItem::whereNull('deleted_at')
            ->whereHas('design', function ($q) {
                $q->whereRaw('LOWER(status) = ?', ['pending']);
            })
            ->whereHas('product.categories', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%sablon%']);
            })
            ->sum('quantity');

        // 3️⃣ TOTAL REMAINING = progress pending + design pending
        $totalRemaining = $orderProgressPending + $designPending;

        if ($request->progress_status === 'progress') {
            $baseQuery->orderBy('created_at', 'asc');
        } else {
            $baseQuery->orderBy('created_at', 'desc');
        }

        // Hitung total SETELAH semua filter diterapkan
        $totalData = (clone $baseQuery)->count();

        // Ambil data — SEKALI SAJA
        $data = (clone $baseQuery)
            ->with([
                'order.customer',
                'order.customerAddress',
                'items.assigns',
                'items.designItem',
                'items.product.productionStocks',
                'items.product.categories'
            ])
            ->skip($start)
            ->take($length)
            ->get();

        $mappedData = $data->map(function ($progress) {
            // $orderCreatedAt = optional($progress->order)->created_at;
            // $date = $orderCreatedAt ? Carbon::parse($orderCreatedAt)->format('d M y H:i') : '-';
            $orderDate = optional($progress->order)->order_date;

            $date = $orderDate
                ? Carbon::parse($orderDate)->format('d M y H:i')
                : '-';

            $editedBadge = $progress->status_edited == 1
                ? ' <span class="badge bg-soft-primary text-primary ms-1">Edited</span>'
                : '';

            $completeBadge = $progress->items->every(function ($item) {
                $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $requiredQty = (float) ($item->quantity ?? 0) * $unitConversionValue;

                return (float) ($item->completed_quantity ?? 0) >= $requiredQty;
            })
                ? '<div><span class="badge bg-soft-success text-success mb-1">Completed</span></div>'
                : '';

            $modeBadge = '';
            if ($progress->order?->mode === 'printing') {
                $modeBadge = '<div><span class="badge bg-soft-info text-info mb-1">Printing</span></div>';
            } elseif ($progress->order?->mode === 'polosan') {
                $modeBadge = '<div><span class="badge bg-soft-warning text-warning mb-1">Polosan</span></div>';
            }

            $invoiceNumberHtml = $modeBadge . $completeBadge . '
            <div>                
                <div>' . e($progress->invoice_number) . $editedBadge . '</div>
                <small class="text-muted">' . $date . '</small>
            </div>';

            $customerHtml = '
                <div style="white-space:normal; word-break:break-word; max-width:180px;">
                    <div class="fw-semibold">' . e($progress->order?->customerAddress?->business_name ?? '-') . '</div>
                    <small class="text-muted">' . e($progress->order?->customer?->name ?? '-') . '</small>
                </div>
            ';

            $progressView = view('erp.pages.production.waiting-list.partials.product-progress', compact('progress'))->render();
            $shipping = e($progress->order->shipping_address ?? '-');

            // $allCompleted = $progress->items->every(function ($item) {
            //     return ($item->completed_quantity ?? 0) >= ($item->quantity ?? 0);
            // });

            $allCompleted = $progress->items->every(function ($item) {
                $assignedTotal = $item->assigns->sum('completed_quantity');
                $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                if ($unitConversionValue <= 0) {
                    $unitConversionValue = 1;
                }

                $requiredQty = (float) ($item->quantity ?? 0) * $unitConversionValue;

                return $assignedTotal >= $requiredQty;
            });

            $actionButtons = view('erp.pages.production.waiting-list.partials.action-button', compact('progress', 'allCompleted'))->render();

            $orderNotesValue = $progress->order?->notes;

            $orderNotes = $orderNotesValue
                ? '<div class="" style="white-space:normal; word-break:break-word; max-width:220px;">'
                . e($orderNotesValue) .
                '</div>'
                : '<div class="">-</div>';

            return [
                'id' => $progress->id,
                'invoice_number' => $invoiceNumberHtml,
                'customer' => $customerHtml,
                'progress' => $progressView,
                'shipping_address' => $shipping,
                'action' => $actionButtons,
                'order_notes' => $orderNotes,
                'created_at' => $progress->created_at->toDateTimeString(),
                'order_created_at' => $date,
                'order_created_raw' => optional($progress->order)->order_date
                    ? Carbon::parse($progress->order->order_date)->toIso8601String()
                    : null,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'has_more' => ($start + $length) < $totalData,
            'total_remaining' => $totalRemaining,
        ]);
    }

    public function getCompleteList()
    {
        return view('erp.pages.production.waiting-list.complete-list');
    }

    public function dataCompleteList(Request $request)
    {
        $orders = Order::with([
            'customer',
            'orderItems.product',
            'orderItems.productBundle.items.product',
        ])
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
