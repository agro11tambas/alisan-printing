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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        // $designs = Design::with(['order.customer', 'items.product'])->orderBy('created_at', 'desc');

        $designs = Design::with([
            'order.customer',
            'order.customerAddress',
            'items.product',
            'items.orderItem',
        ])
            ->whereHas('items')
            ->orderBy('created_at', 'desc');

        // ✅ Filter tanggal
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

        // ✅ Filter pencarian
        if ($request->filled('search_keyword')) {
            $keyword = $request->search_keyword;

            switch ($request->search_type) {
                case 'customer':
                    $keyword = "%{$keyword}%";

                    $designs->where(function ($q) use ($keyword) {

                        // Cari berdasarkan nama customer
                        $q->whereHas('order.customer', function ($sub) use ($keyword) {
                            $sub->where('name', 'like', $keyword);
                        });

                        // Cari berdasarkan business_name
                        $q->orWhereHas('order.customerAddress', function ($sub) use ($keyword) {
                            $sub->where('business_name', 'like', $keyword);
                        });
                    });
                    break;

                // case 'product':
                //     $keyword = trim(strtolower($request->search_keyword));

                //     $designs->whereHas('items.product', function ($q) use ($keyword) {
                //         $q->whereRaw(
                //             "LOWER(name) COLLATE utf8mb4_general_ci LIKE ?",
                //             ["%{$keyword}%"]
                //         );
                //     });
                //     break;

                default:
                    $designs->where('design_number', 'like', "%{$keyword}%");
                    break;
            }
        }

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $designs->whereHas('items.product', function ($q) use ($productKeyword) {
                // 🔍 Pencarian berdasarkan nama ATAU SKU
                $q->where(function ($sub) use ($productKeyword) {
                    $sub->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"])
                        ->orWhereRaw("LOWER(sku) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
                });
            });
        }

        // ✅ Filter status
        if ($request->filled('status')) {
            $designs->where('status', $request->status);
        }

        // ✅ Hindari query count dua kali
        $totalQuery = clone $designs;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $designs->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy load)
        return response()->json([
            'data' => $data->map(function ($design) {
                // 📅 Format tanggal
                $orderCreatedAt = Carbon::parse($design->created_at)->format('Y-m-d H:i');
                $date = $design->date ? Carbon::parse($design->date)->format('j M y') : '-';

                // 🏷️ Badge status
                $status = $design->status ?? 'Pending';
                $class = match ($status) {
                    'Pending' => 'bg-soft-warning text-warning',
                    'Verified' => 'bg-soft-primary text-primary',
                    default => 'bg-soft-dark text-dark',
                };
                $statusBadge = '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';

                // 🧾 Design number + verified/pending badge
                $verifiedBadge = '';
                if ($design->status === 'Verified') {
                    $verifiedBadge = '<span class="badge bg-soft-success text-success ms-1">Verified</span>';
                } elseif ($design->status === 'Pending') {
                    $verifiedBadge = '<span class="badge bg-soft-warning text-warning ms-1">Pending</span>';
                }

                $editedBadge = '';
                if ($design->status_edited == 1) {
                    $editedBadge = '<span class="badge bg-soft-primary text-primary ms-1">Edited</span>';
                }

                $designNumberHtml = '
                    <div>
                        <div>
                            <span class="me-2">' . e($design->design_number) . '</span>'
                    . $verifiedBadge
                    . $editedBadge . '
                        </div>
                        <small class="text-muted">' . $orderCreatedAt . '</small>
                    </div>';

                // 📦 Product list partial
                $productList = view('erp.pages.designs.partials.product-list', compact('design'))->render();

                // 📸 Proof photos
                $images = json_decode($design->proof_photos ?? '[]', true);
                if (empty($images)) {
                    $proofPhotos = '<span class="text-muted small">No proof</span>';
                } else {
                    $proofPhotos = '<div class="d-flex flex-wrap gap-2">';
                    foreach ($images as $img) {
                        $src = asset('uploads/proofs/' . $img);
                        $proofPhotos .= '
                        <a href="' . $src . '" data-lightbox="proof-' . $design->id . '" 
                           class="border rounded overflow-hidden" 
                           style="width:50px;height:50px;display:inline-block;">
                            <img src="' . $src . '" class="img-fluid" style="object-fit:cover;width:100%;height:100%;">
                        </a>';
                    }
                    $proofPhotos .= '</div>';
                }

                // ⚙️ Action buttons partial
                $allUploaded = $design->items->every(fn($item) => !empty($item->preview_image));
                $actionButtons = view('erp.pages.designs.partials.action-button', compact('design', 'allUploaded'))->render();

                $customerHtml = '
                    <div style="white-space: normal; word-break: break-word; max-width:180px;">
                        <div class="fw-semibold">' . e($design->order?->customerAddress?->business_name ?? '-') . '</div>
                        <small class="text-muted">' . e($design->order?->customer?->name ?? '-') . '</small>
                    </div>
                ';

                $whatsappNumber = (function ($phone) {
                    $num = preg_replace('/\D/', '', $phone ?? '');

                    if (strpos($num, '0') === 0) {
                        $num = '62' . substr($num, 1);
                    }
                    if (strpos($num, '8') === 0) {
                        $num = '62' . $num;
                    }

                    return $num;
                })($design->order?->customer?->phone);

                $orderNote = $design->order?->notes
                    ? '<div class="text-muted small mt-1" style="white-space:normal;">' . e($design->order->notes) . '</div>'
                    : '<div class="text-muted small mt-1">-</div>';

                return [
                    'id' => $design->id,
                    'design_number' => $designNumberHtml,
                    'customer' => $customerHtml,
                    'status' => $statusBadge,
                    'products' => $productList,
                    'proof_photos' => $proofPhotos,
                    'action' => $actionButtons,
                    'order_note' => $orderNote,
                    'whatsapp' => '
                        <a href="https://wa.me/' . $whatsappNumber . '"
                            target="_blank"
                            class="btn btn-success btn-sm"
                            style="padding:6px 10px;">
                            Chat
                        </a>
                    ',
                    'created_at' => $orderCreatedAt,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    // public function verify(Request $request, $id)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $design = Design::with([
    //             'order.customer',
    //             'items.product',
    //             'items.orderItem',
    //         ])->findOrFail($id);

    //         $order = $design->order;

    //         $printingItems = $design->items->filter(function ($designItem) {
    //             return $designItem->orderItem?->mode === 'printing';
    //         });

    //         if ($printingItems->isEmpty()) {
    //             DB::rollBack();

    //             if ($request->ajax()) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Tidak ada item printing yang perlu diverifikasi.'
    //                 ], 422);
    //             }

    //             return redirect()->back()->with('error', 'Tidak ada item printing yang perlu diverifikasi.');
    //         }

    //         $design->update([
    //             'verification_status' => 'approved',
    //             'status'              => 'Verified',
    //             'verified_by'         => Auth::id(),
    //             'verified_at'         => now(),
    //         ]);

    //         foreach ($printingItems as $designItem) {
    //             $designItem->update([
    //                 'verification_status' => 'approved',
    //             ]);
    //         }

    //         $orderProgress = OrderProgress::create([
    //             'order_id'       => $order->id,
    //             'design_id'      => $design->id,
    //             'date'           => now()->format('Y-m-d'),
    //             'status'         => 'Pending',
    //             'notes'          => null,
    //             'invoice_number' => $order->order_number,
    //         ]);

    //         foreach ($printingItems as $designItem) {
    //             OrderProgressItem::create([
    //                 'order_progress_id'          => $orderProgress->id,
    //                 'design_item_id'             => $designItem->id,
    //                 'order_item_id'              => $designItem->order_item_id,
    //                 'product_id'                 => $designItem->product_id,

    //                 'product_unit_conversion_id' => $designItem->product_unit_conversion_id,
    //                 'unit_name'                  => $designItem->unit_name,
    //                 'unit_conversion_value'      => $designItem->unit_conversion_value,

    //                 'quantity'                   => $designItem->quantity,
    //                 'completed_quantity'         => 0,
    //             ]);

    //             $productionStock = \App\Models\ProductionStock::firstOrCreate(
    //                 [
    //                     'product_id' => $designItem->product_id,
    //                     'production_warehouse_id' => 2,
    //                 ],
    //                 [
    //                     'opening_stock'          => 0,
    //                     'available_quantity'    => 0,
    //                     'finished_product_stock' => 0,
    //                     'canceled_product_stock' => 0,
    //                     'pending_waiting_list'   => 0,
    //                 ]
    //             );

    //             $qtyBase = $designItem->quantity * ($designItem->unit_conversion_value ?? 1);

    //             $productionStock->increment('pending_waiting_list', $qtyBase);
    //         }

    //         $deliveryOrder = DeliveryOrder::firstOrCreate(
    //             [
    //                 'order_id'   => $order->id,
    //                 'design_id'  => $design->id,
    //             ],
    //             [
    //                 'delivery_number'   => $order->order_number,
    //                 'delivery_date'     => now()->format('Y-m-d'),
    //                 'note'              => $design->notes ?? '',
    //                 'status'            => 'Ongoing',
    //                 'customer'          => $order->customer->name,
    //                 'shipping_address'  => $order->shipping_address,
    //                 'google_map_link'   => $order->google_maps,
    //                 'created_by'        => Auth::id(),
    //             ]
    //         );

    //         foreach ($orderProgress->items as $progressItem) {
    //             DeliveryOrderItem::create([
    //                 'delivery_order_id'       => $deliveryOrder->id,
    //                 'order_progress_id'       => $orderProgress->id,
    //                 'order_item_id'           => $progressItem->order_item_id,
    //                 'order_progress_item_id'  => $progressItem->id,
    //                 'design_item_id'          => $progressItem->design_item_id,
    //                 'product_id'              => $progressItem->product_id,
    //                 'status'                  => $orderProgress->status,
    //                 'progress_qty'            => $progressItem->quantity,
    //                 'ready_qty'               => 0,
    //                 'note'                    => null,
    //             ]);
    //         }

    //         DB::commit();

    //         if ($request->ajax()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Design verified successfully.'
    //             ]);
    //         }

    //         return redirect()->back()->with('success', 'Design verified successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         Log::error('Error verifying design: ' . $e->getMessage());

    //         if ($request->ajax()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Failed to verify design: ' . $e->getMessage()
    //             ], 500);
    //         }

    //         return redirect()->back()->with('error', 'Failed to verify design: ' . $e->getMessage());
    //     }
    // }

    public function verify(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $design = Design::with([
                'order.customer',
                'items.product',
                'items.orderItem',
            ])->findOrFail($id);

            $order = $design->order;

            // AMBIL PRINTING + POLOSAN
            $verifyItems = $design->items->filter(function ($designItem) {
                return in_array($designItem->orderItem?->mode, ['printing', 'polosan']);
            });

            if ($verifyItems->isEmpty()) {
                DB::rollBack();

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak ada item yang perlu diverifikasi.'
                    ], 422);
                }

                return redirect()->back()->with('error', 'Tidak ada item yang perlu diverifikasi.');
            }


            $design->update([
                'verification_status' => 'approved',
                'status'              => 'Verified',
                'verified_by'         => Auth::id(),
                'verified_at'         => now(),
            ]);

            foreach ($verifyItems as $designItem) {
                $designItem->update([
                    'verification_status' => 'approved',
                ]);
            }

            $orderProgress = OrderProgress::create([
                'order_id'       => $order->id,
                'design_id'      => $design->id,
                'date'           => now()->format('Y-m-d'),
                'status'         => 'Pending',
                'notes'          => null,
                'invoice_number' => $order->order_number,
            ]);

            foreach ($verifyItems as $designItem) {
                OrderProgressItem::create([
                    'order_progress_id'          => $orderProgress->id,
                    'design_item_id'             => $designItem->id,
                    'order_item_id'              => $designItem->order_item_id,
                    'product_id'                 => $designItem->product_id,

                    'product_unit_conversion_id' => $designItem->product_unit_conversion_id,
                    'unit_name'                  => $designItem->unit_name,
                    'unit_conversion_value'      => $designItem->unit_conversion_value,

                    'quantity'                   => $designItem->quantity,
                    'completed_quantity'         => 0,
                ]);

                // Semua mode mengikuti alur produksi yang sama:
                // waiting list -> assign -> assign list -> progress -> delivery.
                $productionStock = \App\Models\ProductionStock::firstOrCreate(
                    [
                        'product_id' => $designItem->product_id,
                        'production_warehouse_id' => 2,
                    ],
                    [
                        'opening_stock'          => 0,
                        'available_quantity'    => 0,
                        'finished_product_stock' => 0,
                        'canceled_product_stock' => 0,
                        'pending_waiting_list'   => 0,
                    ]
                );

                $qtyBase = $designItem->quantity * ($designItem->unit_conversion_value ?? 1);

                $productionStock->increment('pending_waiting_list', $qtyBase);
            }

            $deliveryOrder = DeliveryOrder::firstOrCreate(
                [
                    'order_id'   => $order->id,
                    'design_id'  => $design->id,
                ],
                [
                    'delivery_number'   => $order->order_number,
                    'delivery_date'     => now()->format('Y-m-d'),
                    'note'              => $design->notes ?? '',
                    'status'            => 'Ongoing',
                    'customer'          => $order->customer?->name ?? '-',
                    'shipping_address'  => $order->shipping_address,
                    'google_map_link'   => $order->google_maps,
                    'created_by'        => Auth::id(),
                ]
            );

            $orderProgress->load('items');

            foreach ($orderProgress->items as $progressItem) {
                DeliveryOrderItem::create([
                    'delivery_order_id'       => $deliveryOrder->id,
                    'order_progress_id'       => $orderProgress->id,
                    'order_item_id'           => $progressItem->order_item_id,
                    'order_progress_item_id'  => $progressItem->id,
                    'design_item_id'          => $progressItem->design_item_id,
                    'product_id'              => $progressItem->product_id,
                    'status'                  => 'Pending',
                    'progress_qty'            => $progressItem->quantity,
                    'ready_qty'               => 0,
                    'shipped_qty'             => 0,
                    'note'                    => null,
                    'product_unit_conversion_id' => $progressItem->product_unit_conversion_id,
                    'unit_name'                  => $progressItem->unit_name,
                    'unit_conversion_value'      => $progressItem->unit_conversion_value,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Design verified successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'Design verified successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error verifying design: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify design: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to verify design: ' . $e->getMessage());
        }
    }

    public function unverify(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $design = Design::with(['order', 'items.product'])->findOrFail($id);
            $order = $design->order;

            // Pastikan hanya design yang sudah verified yang bisa di-unverify
            if ($design->status !== 'Verified') {
                return back()->with('error', 'Design belum diverifikasi atau sudah di-unverify.');
            }

            // Ambil OrderProgress dan DeliveryOrder terkait design ini
            $orderProgress = OrderProgress::where('design_id', $design->id)->first();
            $deliveryOrder = DeliveryOrder::where('design_id', $design->id)->first();

            // 🔹 Kembalikan stok pending_waiting_list di ProductionStock
            if ($orderProgress && $orderProgress->items) {
                foreach ($orderProgress->items as $progressItem) {
                    $productionStock = \App\Models\ProductionStock::where('product_id', $progressItem->product_id)
                        ->where('production_warehouse_id', 2)
                        ->first();

                    if ($productionStock) {
                        $qtyBase = $progressItem->quantity * ($progressItem->unit_conversion_value ?? 1);

                        $productionStock->decrement('pending_waiting_list', $qtyBase);
                        if ($productionStock->pending_waiting_list < 0) {
                            $productionStock->update(['pending_waiting_list' => 0]);
                        }
                    }
                }
            }

            // 🔹 Hapus DeliveryOrderItem dan DeliveryOrder
            if ($deliveryOrder) {
                $deliveryOrder->items()->delete();
                $deliveryOrder->delete();
            }

            // 🔹 Hapus OrderProgressItem dan OrderProgress
            if ($orderProgress) {
                $orderProgress->items()->delete();
                $orderProgress->delete();
            }

            // 🔹 Update kembali status design ke Unverified
            $design->update([
                'verification_status' => 'pending',
                'status'              => 'Pending',
                'verified_by'         => null,
                'verified_at'         => null,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Design unverified successfully.'
                ]);
            }

            return redirect()->back()->with('success', 'Design berhasil di-unverify.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error unverify design: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unverify design: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Gagal unverify design: ' . $e->getMessage());
        }
    }
}
