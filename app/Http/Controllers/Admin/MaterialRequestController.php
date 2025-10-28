<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryStockIn;
use App\Models\InventoryStockInHistory;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\ProductionStock;
use App\Models\Products;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\Facades\DataTables;

class MaterialRequestController extends Controller
{
    public function getMaterialRequest()
    {
        return view('erp.pages.production.request-stock.request-stock');
    }

    public function dataMaterialRequest(Request $request)
    {
        $materialRequest = MaterialRequest::with(['items.product', 'requestedBy']);

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $materialRequest->whereDate('requested_at', Carbon::today());
                    break;
                case 'last_7_days':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $materialRequest->whereMonth('requested_at', Carbon::now()->month)
                        ->whereYear('requested_at', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $materialRequest->whereYear('requested_at', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $materialRequest->whereBetween('requested_at', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        if ($request->has('progress_status')) {
            if ($request->progress_status === 'completed') {
                $materialRequest->whereDoesntHave('items', function ($q) {
                    $q->whereColumn('requested_qty', '>', 'received_qty');
                });
            } elseif ($request->progress_status === 'progress') {
                $materialRequest->whereHas('items', function ($q) {
                    $q->whereColumn('requested_qty', '>', 'received_qty');
                });
            }
        }

        $materialRequest = $materialRequest->latest()->get();

        return DataTables::of($materialRequest)
            ->addIndexColumn()
            ->addColumn('requested_by', function ($materialRequest) {
                return $materialRequest->requestedBy->name;
            })
            ->addColumn('requested_at', function ($materialRequest) {
                return $materialRequest->requested_at;
            })
            ->addColumn('items', function ($materialRequest) {
                return view('erp.pages.production.request-stock.partials.material-request-items', compact('materialRequest'))->render();
            })
            ->addColumn('warehouse_status', function ($materialRequest) {
                $status = strtolower($materialRequest->warehouse_status);

                switch ($status) {
                    case 'verified':
                        return '<div class="badge bg-soft-success text-success">' . $materialRequest->warehouse_status . '</div>';
                    case 'not verified':
                        return '<div class="badge bg-soft-danger text-danger">' . $materialRequest->warehouse_status . '</div>';
                    default:
                        return '<div class="badge bg-soft-primary text-primary">' . ($materialRequest->warehouse_status ?? 'Pending') . '</div>';
                }
            })
            ->addColumn('status', function ($materialRequest) {
                $status = strtolower($materialRequest->status);

                switch ($status) {
                    case 'verified':
                        return '<div class="badge bg-soft-success text-success">' . $materialRequest->status . '</div>';
                    case 'not verified':
                        return '<div class="badge bg-soft-danger text-danger">' . $materialRequest->status . '</div>';
                    default:
                        return '<div class="badge bg-soft-primary text-primary">' . $materialRequest->status . '</div>';
                }
            })
            ->addColumn('action', function ($materialRequest) {
                return view('erp.pages.production.request-stock.partials.action-button', compact('materialRequest'))->render();
            })
            ->rawColumns(['action', 'items', 'status', 'warehouse_status'])
            ->make(true);
    }

    public function dataDeletedRequestStock(Request $request)
    {
        $materialRequest = MaterialRequest::onlyTrashed()->with(['items.product', 'requestedBy']);
        // dd($materialRequest);

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $materialRequest->whereDate('requested_at', Carbon::today());
                    break;
                case 'last_7_days':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $materialRequest->whereMonth('requested_at', Carbon::now()->month)
                        ->whereYear('requested_at', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $materialRequest->whereBetween('requested_at', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $materialRequest->whereYear('requested_at', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $materialRequest->whereBetween('requested_at', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        $materialRequest = $materialRequest->latest()->get();

        return DataTables::of($materialRequest)
            ->addIndexColumn()
            ->addColumn('requested_by', function ($materialRequest) {
                return $materialRequest->requestedBy->name ?? '-';
            })
            ->addColumn('requested_at', function ($materialRequest) {
                return $materialRequest->requested_at ?? '-';
            })
            ->addColumn('items', function ($materialRequest) {
                return view('erp.pages.production.request-stock.partials.material-request-items', compact('materialRequest'))->render();
            })
            // ->addColumn('deleted_by', function ($materialRequest) {
            //     return $materialRequest->deletedBy ? $materialRequest->deletedBy->name : '-';
            // })
            ->addColumn('deleted_at', function ($materialRequest) {
                return $materialRequest->deleted_at ? $materialRequest->deleted_at->format('d-m-Y H:i') : '-';
            })
            ->addColumn('action', function ($materialRequest) {
                return '
                    <div class="d-flex gap-2">
                        <button type="button" 
                            class="btn btn-success btn-sm me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalRestoreRequestStock"
                            data-id="' . $materialRequest->id . '" 
                            data-name="Request #' . $materialRequest->id . '"
                            data-url="' . route('request-stocks.restore', $materialRequest->id) . '">
                                Restore
                        </button>
                        <button type="button" 
                            class="btn btn-danger btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalForceDeleteRequestStock"
                            data-id="' . $materialRequest->id . '" 
                            data-name="Request #' . $materialRequest->id . '"
                            data-url="' . route('request-stocks.forceDelete', $materialRequest->id) . '">
                                Hapus Permanen
                        </button>
                    </div>
                ';
            })

            ->rawColumns(['action', 'items'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::with(['categories', 'discounts', 'categories.discounts'])->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
            ];
        })->toArray();

        return view('erp.pages.production.request-stock.create-request-stock', compact('productsJson'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'requested_by' => 'required',
            'requested_at' => 'required',
            'product' => 'required|array',
            'product.*' => 'required',
            'qty'                  => 'required|array',
            'qty.*'                => 'numeric|min:1',
        ]);

        DB::beginTransaction();

        try {
            $materialRequestNumber = \App\Services\MaterialRequestService::generateRequestNumber();

            $requestStock = MaterialRequest::create([
                'material_request_number' => $materialRequestNumber,
                'requested_by' => $request->user()->id,
                'requested_at' => $request->requested_at,
            ]);

            foreach ($request->product as $key => $productId) {
                MaterialRequestItem::create([
                    'material_request_id' => $requestStock->id,
                    'material_request_number' => $materialRequestNumber,
                    'product_id' => $productId,
                    'requested_qty' => $request->qty[$key],
                ]);
            }

            $inventory = Inventory::create([
                'material_request_id'     => $requestStock->id,
                'material_request_number' => $materialRequestNumber,
                'date'         => $request->requested_at,
                'status'       => 'Stock Out',
            ]);

            foreach ($requestStock->items as $item) {
                InventoryItem::create([
                    'inventory_id'      => $inventory->id,
                    'product_id'        => $item->product_id,
                    'inventory_warehouse_id' => 1,
                    'material_request_item_id'     => $item->id,
                    'quantity'          => $item->requested_qty,
                    'stock_in'          => 0,
                    'remaining_stock_in' => $item->requested_qty,
                    'stock_out'         => 0,
                ]);
            }

            DB::commit();
            return redirect("/erp/productions/material-request")->with('success', 'Order berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store order: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan order: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $requestStock = MaterialRequest::with(['items.product'])->findOrFail($id);

            // ✅ Semua item sudah di-issued penuh
            $isFullyIssued = $requestStock->items->every(function ($item) {
                return $item->issued_qty >= $item->requested_qty;
            });

            // ✅ Semua item sudah di-received penuh
            $isFullyReceived = $requestStock->items->every(function ($item) {
                return $item->received_qty >= $item->requested_qty;
            });

            // Kalau belum fully issued/received → nggak boleh delete
            if (!($isFullyIssued || $isFullyReceived)) {
                DB::rollBack();
                return back()->with('error', 'Request Stock belum selesai issued atau received, tidak dapat dihapus.');
            }

            // 🔹 Buat Stock In otomatis (karena barang dikembalikan ke gudang)
            $inventory = Inventory::create([
                'material_request_id'     => $requestStock->id,
                'material_request_number' => $requestStock->material_request_number, // ✅ tetap pakai nomor MR
                'inventory_type'          => 'stock_in',
                'date'                    => now(),
                'note'                    => 'Auto Stock In (Delete Material Request)',
                'status'                  => 'Stock In',
                'user_id'                 => Auth::id(),
            ]);

            // 🔹 Tambah semua item ke InventoryItem
            foreach ($requestStock->items as $item) {
                InventoryItem::create([
                    'inventory_id'             => $inventory->id,
                    'product_id'               => $item->product_id,
                    'material_request_item_id' => $item->id,
                    'inventory_warehouse_id'   => 1,
                    'quantity'                 => $item->requested_qty,
                    'price'                    => 0,
                    'stock_in'                 => 0,
                    'remaining_stock_in'       => $item->requested_qty,
                    'stock_out'                => 0,
                ]);

                // 🔹 Soft delete tiap item
                $item->delete();
            }

            // 🔹 Soft delete Material Request
            $requestStock->delete();

            DB::commit();

            return redirect("/erp/productions/material-request")
                ->with('success', 'Request Stock dihapus dan Stock In otomatis berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delete material request: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus Request Stock: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $materialRequest = MaterialRequest::with('items')->findOrFail($id);

        $products = Products::all();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
            ];
        })->toArray();

        return view('erp.pages.production.request-stock.edit-request-stock', compact('materialRequest', 'productsJson'));
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'requested_by' => 'required',
    //         'requested_at' => 'required|date',
    //         'product'      => 'required|array',
    //         'product.*'    => 'required|exists:products,id',
    //         'qty'          => 'required|array',
    //         'qty.*'        => 'numeric|min:1',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         $materialRequest = MaterialRequest::with(['items', 'inventory.items'])->findOrFail($id);

    //         if ($materialRequest->hasStockOut()) {
    //             DB::rollBack();
    //             return back()->with('error', 'Request Stock ini sudah memiliki Stock Out dan tidak dapat diubah lagi.');
    //         }

    //         // Update header MaterialRequest
    //         $materialRequest->update([
    //             'requested_by' => $request->user()->id,
    //             'requested_at' => $request->requested_at,
    //         ]);

    //         // Update Inventory header
    //         $inventory = $materialRequest->inventory;
    //         if ($inventory) {
    //             $inventory->update([
    //                 'date'   => $request->requested_at,
    //                 'status' => 'Stock Out',
    //             ]);
    //         }

    //         // Tandai item lama yang tidak ada di request → soft delete
    //         $existingItemIds = $materialRequest->items->pluck('id')->toArray();
    //         $incomingItemIds = $request->item_id ?? []; // item_id hidden input di form
    //         $itemsToDelete   = array_diff($existingItemIds, $incomingItemIds);

    //         if (!empty($itemsToDelete)) {
    //             MaterialRequestItem::whereIn('id', $itemsToDelete)->delete();
    //             InventoryItem::whereIn('material_request_item_id', $itemsToDelete)->delete();
    //         }

    //         // Loop item baru & update/insert
    //         foreach ($request->product as $key => $productId) {
    //             $qty = $request->qty[$key];

    //             if (isset($incomingItemIds[$key]) && $incomingItemIds[$key]) {
    //                 // Update item lama
    //                 $reqItem = MaterialRequestItem::find($incomingItemIds[$key]);
    //                 if ($reqItem) {
    //                     $reqItem->update([
    //                         'product_id'    => $productId,
    //                         'requested_qty' => $qty,
    //                     ]);

    //                     $invItem = InventoryItem::where('material_request_item_id', $reqItem->id)->first();
    //                     if ($invItem) {
    //                         $invItem->update([
    //                             'product_id'         => $productId,
    //                             'quantity'           => $qty,
    //                             'remaining_stock_in' => $qty - $invItem->stock_out, // jaga konsistensi
    //                         ]);
    //                     }
    //                 }
    //             } else {
    //                 // Insert item baru
    //                 $newReqItem = MaterialRequestItem::create([
    //                     'material_request_id' => $materialRequest->id,
    //                     'product_id'          => $productId,
    //                     'requested_qty'       => $qty,
    //                 ]);

    //                 if ($inventory) {
    //                     InventoryItem::create([
    //                         'inventory_id'             => $inventory->id,
    //                         'product_id'               => $productId,
    //                         'material_request_item_id' => $newReqItem->id,
    //                         'quantity'                 => $qty,
    //                         'stock_in'                 => 0,
    //                         'remaining_stock_in'       => $qty,
    //                         'stock_out'                => 0,
    //                     ]);
    //                 }
    //             }
    //         }

    //         DB::commit();
    //         return redirect("/erp/productions/material-request")->with('success', 'Request Stock berhasil diperbarui.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error update request stock: ' . $e->getMessage());
    //         return back()->with('error', 'Gagal update request stock: ' . $e->getMessage());
    //     }
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'requested_by' => 'required',
            'requested_at' => 'required|date',
            'product'      => 'required|array',
            'product.*'    => 'required|exists:products,id',
            'qty'          => 'required|array',
            'qty.*'        => 'numeric|min:1',
        ]);

        DB::beginTransaction();

        try {
            $materialRequest = MaterialRequest::with(['items', 'inventory.items'])->findOrFail($id);

            if ($materialRequest->hasStockOut()) {
                DB::rollBack();
                return back()->with('error', 'Request Stock ini sudah memiliki Stock Out dan tidak dapat diubah lagi.');
            }

            // ✅ Pastikan nomor tetap ada (kalau belum, buat baru)
            if (!$materialRequest->material_request_number) {
                $materialRequest->material_request_number = \App\Services\MaterialRequestService::generateRequestNumber();
                $materialRequest->save();
            }

            // 🔹 Update header Material Request
            $materialRequest->update([
                'requested_by' => $request->user()->id,
                'requested_at' => $request->requested_at,
            ]);

            // 🔹 Update Inventory header
            $inventory = $materialRequest->inventory;
            if ($inventory) {
                $inventory->update([
                    'material_request_number' => $materialRequest->material_request_number, // ✅ sinkron nomor
                    'date'                    => $request->requested_at,
                    'status'                  => 'Stock Out',
                ]);
            }

            // 🔹 Hapus item lama yang tidak ada di form
            $existingItemIds = $materialRequest->items->pluck('id')->toArray();
            $incomingItemIds = $request->item_id ?? [];
            $itemsToDelete   = array_diff($existingItemIds, $incomingItemIds);

            if (!empty($itemsToDelete)) {
                MaterialRequestItem::whereIn('id', $itemsToDelete)->delete();
                InventoryItem::whereIn('material_request_item_id', $itemsToDelete)->delete();
            }

            // 🔹 Loop item baru & update/insert
            foreach ($request->product as $key => $productId) {
                $qty = $request->qty[$key];

                if (isset($incomingItemIds[$key]) && $incomingItemIds[$key]) {
                    // 🔸 Update item lama
                    $reqItem = MaterialRequestItem::find($incomingItemIds[$key]);
                    if ($reqItem) {
                        $reqItem->update([
                            'product_id'    => $productId,
                            'requested_qty' => $qty,
                        ]);

                        $invItem = InventoryItem::where('material_request_item_id', $reqItem->id)->first();
                        if ($invItem) {
                            $invItem->update([
                                'product_id'               => $productId,
                                'quantity'                 => $qty,
                                'remaining_stock_in'       => $qty - $invItem->stock_out,
                                'material_request_number'  => $materialRequest->material_request_number, // ✅ tambah ini
                            ]);
                        }
                    }
                } else {
                    // 🔸 Insert item baru
                    $newReqItem = MaterialRequestItem::create([
                        'material_request_id' => $materialRequest->id,
                        'product_id'          => $productId,
                        'requested_qty'       => $qty,
                    ]);

                    if ($inventory) {
                        InventoryItem::create([
                            'inventory_id'             => $inventory->id,
                            'product_id'               => $productId,
                            'material_request_item_id' => $newReqItem->id,
                            'quantity'                 => $qty,
                            'stock_in'                 => 0,
                            'remaining_stock_in'       => $qty,
                            'stock_out'                => 0,
                            'material_request_number'  => $materialRequest->material_request_number, // ✅ simpan juga di item baru
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect("/erp/productions/material-request")->with('success', 'Request Stock berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update request stock: ' . $e->getMessage());
            return back()->with('error', 'Gagal update request stock: ' . $e->getMessage());
        }
    }

    public function markAsVerified($id)
    {
        DB::beginTransaction();

        try {
            // ambil request stock beserta relasinya
            $requestStock = MaterialRequest::with(['items', 'inventory.items'])->findOrFail($id);

            // update header MaterialRequest
            $requestStock->update([
                'status' => 'Verified',
            ]);

            foreach ($requestStock->items as $item) {
                $item->update([
                    'received_qty' => $item->issued_qty,
                    'issued_qty' => 0,
                ]);

                $productionStock = ProductionStock::where('product_id', $item->product_id)->first();
                if ($productionStock) {
                    $productionStock->increment('available_quantity', $item->received_qty);
                }
            }

            DB::commit();
            return redirect("/erp/productions/material-request")->with('success', 'Order berhasil diverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update order: ' . $e->getMessage());
            return back()->with('error', 'Gagal memverifikasi order: ' . $e->getMessage());
        }
    }

    public function forceDelete($id)
    {
        DB::beginTransaction();

        try {
            $materialRequest = MaterialRequest::onlyTrashed()->findOrFail($id);

            // 🔥 trigger booted() => otomatis hapus semua relasi yang di-cascade
            $materialRequest->forceDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Request Stock beserta item & relasinya berhasil dihapus permanen!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete material request gagal', [
                'material_request_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal menghapus permanen request stock!');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();

        try {
            $materialRequest = MaterialRequest::onlyTrashed()->findOrFail($id);

            // restore MaterialRequest
            $materialRequest->restore();

            // kalau ada relasi yang ikut soft delete (misal items), ikut restore juga
            if (method_exists($materialRequest, 'items')) {
                $materialRequest->items()->withTrashed()->restore();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Request Stock berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore material request gagal', [
                'material_request_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan request stock!');
        }
    }
}
