<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\OrderProgress;
use App\Models\OrderProgressItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DesignController extends Controller
{
    public function getDesign()
    {
        return view('erp.pages.designs.design');
    }

    public function dataDesign(Request $request)
    {
        $designs = Design::with(['order.customer', 'items.product']);

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $designs->whereDate('date', Carbon::today());
                    break;
                case 'last_7_days':
                    $designs->whereBetween('date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $designs->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $designs->whereBetween('date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $designs->whereBetween('date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $designs->whereYear('date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $designs->whereBetween('date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    break;
            }
        }

        if ($request->filled('search_keyword')) {
            if ($request->search_type === 'customer') {
                $designs->whereHas('order.customer', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search_keyword . '%');
                });
            } else {
                $designs->where('design_number', 'like', '%' . $request->search_keyword . '%');
            }
        }

        if ($request->filled('status')) {
            $designs->where('status', $request->status);
        }

        $designs = $designs->latest();

        return DataTables::of($designs)
            ->addIndexColumn()
            ->addColumn('design_number', function ($design) {
                $date = $design->date ? Carbon::parse($design->date)->format('j M y') : '-';

                $verifiedBadge = '';
                if ($design->status === 'Verified') {
                    $verifiedBadge = '<span class="badge bg-soft-success text-success ms-1">Verified</span>';
                } elseif ($design->status === 'Pending') {
                    $verifiedBadge = '<span class="badge bg-soft-warning text-warning ms-1">Pending</span>';
                }

                return '
                <div>
                    <div><span class="me-2">' . e($design->design_number) . '</span>' . $verifiedBadge . '</div>
                    <small class="text-muted">' . $date . '</small>
                </div>';
            })
            ->addColumn('customer', function ($design) {
                return $design->order?->customer?->name ?? '-';
            })
            ->addColumn('status', function ($design) {
                $status = $design->status ?? 'Pending';
                $class = match ($status) {
                    'Pending' => 'bg-soft-warning text-warning',
                    'Verified' => 'bg-soft-primary text-primary',
                    default => 'bg-soft-dark text-dark',
                };

                return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('products', function ($design) {
                return view('erp.pages.designs.partials.product-list', compact('design'))->render();
            })
            ->addColumn('proof_photos', function ($design) {
                $images = json_decode($design->proof_photos ?? '[]', true);
                if (empty($images)) {
                    return '<span class="text-muted small">No proof</span>';
                }

                $html = '<div class="d-flex flex-wrap gap-2">';
                foreach ($images as $img) {
                    $src = asset('uploads/proofs/' . $img);
                    $html .= '
                <a href="' . $src . '" data-lightbox="proof-' . $design->id . '" class="border rounded overflow-hidden" style="width:50px;height:50px;display:inline-block;">
                    <img src="' . $src . '" class="img-fluid" style="object-fit:cover;width:100%;height:100%;">
                </a>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('action', function ($design) {
                $allUploaded = $design->items->every(function ($item) {
                    return !empty($item->preview_image);
                });

                return view('erp.pages.designs.partials.action-button', compact('design', 'allUploaded'))->render();
            })
            ->rawColumns(['design_number', 'status', 'products', 'action', 'proof_photos'])
            ->make(true);
    }

    public function verify(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $design = Design::with(['order', 'items.product'])->findOrFail($id);
            $order = $design->order;

            $design->update([
                'verification_status' => 'approved',
                'status'              => 'Verified',
                'verified_by'         => Auth::id(),
                'verified_at'         => now(),
            ]);

            $orderProgress = OrderProgress::create([
                'order_id'       => $order->id,
                'design_id'     => $design->id,
                'date'           => now()->format('Y-m-d'),
                'status'         => 'Pending',
                'notes'          => null,
                'invoice_number' => $order->order_number,
            ]);

            foreach ($design->items as $designItem) {
                OrderProgressItem::create([
                    'order_progress_id'  => $orderProgress->id,
                    'design_item_id'     => $designItem->id,
                    'order_item_id'      => $designItem->order_item_id,
                    'product_id'         => $designItem->product_id,
                    'quantity'           => $designItem->quantity,
                    'completed_quantity' => 0,
                ]);
            }

            $deliveryOrder = DeliveryOrder::create([
                'order_id'        => $order->id,
                'design_id'      => $design->id,
                'delivery_number' => $order->order_number,
                'delivery_date'   => now()->format('Y-m-d'),
                'note'            => $design->notes ?? '',
                'status'          => 'Ongoing',
                'customer'       => $order->customer->name,
                'shipping_address' => $order->shipping_address,
                'google_map_link'  => $order->google_maps,
                'created_by'      => Auth::id(),
            ]);

            foreach ($orderProgress->items as $progressItem) {
                DeliveryOrderItem::create([
                    'delivery_order_id'     => $deliveryOrder->id,
                    'order_progress_id'     => $orderProgress->id,
                    'order_item_id'         => $progressItem->order_item_id,
                    'order_progress_item_id' => $progressItem->id,
                    'design_item_id'        => $progressItem->design_item_id,
                    'product_id'            => $progressItem->product_id,
                    'status'                => $orderProgress->status, // Pending
                    'progress_qty'          => $progressItem->quantity,
                    'ready_qty'             => 0,
                    'note'                  => null,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Design verified successfully and Order Progress created.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error verifying design: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to verify design: ' . $e->getMessage()], 500);
        }
    }
}
