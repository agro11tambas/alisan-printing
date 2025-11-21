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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $materialRequest = MaterialRequest::with(['items.product', 'requestedBy', 'verifiedBy']);

        // ✅ Filter tanggal
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

        if ($request->filled('search_product')) {
            $productKeyword = trim(strtolower($request->search_product));

            $materialRequest->whereHas('items.product', function ($q) use ($productKeyword) {
                $q->where(function ($sub) use ($productKeyword) {
                    $sub->whereRaw("LOWER(name) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"])
                        ->orWhereRaw("LOWER(sku) COLLATE utf8mb4_general_ci LIKE ?", ["%{$productKeyword}%"]);
                });
            });
        }

        // ✅ Filter status progress
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

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $materialRequest;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $materialRequest->latest()->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($item) {
                // 👤 Requested by
                $requestedBy = e($item->requestedBy->name ?? '-');

                // 📅 Tanggal
                $requestedAt = $item->requested_at
                    ? Carbon::parse($item->created_at)->format('d M Y H:i')
                    : '-';

                // 📦 Items partial
                $itemsHtml = view('erp.pages.production.request-stock.partials.material-request-items', [
                    'materialRequest' => $item
                ])->render();

                // 🏷️ Warehouse Status
                $whStatus = strtolower($item->warehouse_status);
                $warehouseBadge = match ($whStatus) {
                    'verified' => '<div class="badge bg-soft-success text-success">' . e($item->warehouse_status) . '</div>',
                    'not verified' => '<div class="badge bg-soft-danger text-danger">' . e($item->warehouse_status) . '</div>',
                    default => '<div class="badge bg-soft-primary text-primary">' . e($item->warehouse_status ?? 'Pending') . '</div>',
                };

                // 🏷️ Status
                $status = strtolower($item->status);
                $statusBadge = match ($status) {
                    'verified' => '<div class="badge bg-soft-success text-success">' . e($item->status) . '</div>',
                    'not verified' => '<div class="badge bg-soft-danger text-danger">' . e($item->status) . '</div>',
                    default => '<div class="badge bg-soft-primary text-primary">' . e($item->status) . '</div>',
                };

                // 👨 Verified by
                $verifiedBy = e($item->verifiedBy->name ?? '-');

                // ⚙️ Action partial
                $action = view('erp.pages.production.request-stock.partials.action-button', [
                    'materialRequest' => $item
                ])->render();

                return [
                    'id' => $item->id,
                    'requested_by' => $requestedBy,
                    'requested_at' => $requestedAt,
                    'items' => $itemsHtml,
                    'warehouse_status' => $warehouseBadge,
                    'status' => $statusBadge,
                    'verified_by' => $verifiedBy,
                    'action' => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
            ->addColumn('action', function ($m) {
                // Hitung total issued & received untuk menentukan tombol restore
                $totalIssued = $m->items->sum('issued_qty');
                $totalReceived = $m->items->sum('received_qty');
                $isEmpty = $totalIssued == 0 && $totalReceived == 0;

                $restoreBtn = '';
                if ($isEmpty) {
                    $restoreBtn = '
                    <button type="button"
                        class="btn btn-success btn-sm me-1"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRestoreRequestStock"
                        data-id="' . $m->id . '"
                        data-name="Request #' . $m->id . '"
                        data-url="' . route('request-stocks.restore', $m->id) . '">
                        Restore
                    </button>
                ';
                }

                $forceDeleteBtn = '
                    <button type="button"
                        class="btn btn-danger btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalForceDeleteRequestStock"
                        data-id="' . $m->id . '"
                        data-name="Request #' . $m->id . '"
                        data-url="' . route('request-stocks.forceDelete', $m->id) . '">
                        Hapus Permanen
                    </button>
                ';

                return '<div class="d-flex gap-2">' . $restoreBtn . $forceDeleteBtn . '</div>';
            })
            ->rawColumns(['action', 'items'])
            ->make(true);
    }

    public function RequestSummary(Request $request)
    {
        $filterStart = null;
        $filterEnd = null;

        // Tentukan rentang filter
        switch ($request->filter) {

            case 'today':
                $filterStart = Carbon::today();
                $filterEnd   = Carbon::today()->endOfDay();
                break;

            case 'last_7_days':
                $filterStart = Carbon::now()->subDays(7);
                $filterEnd   = Carbon::now();
                break;

            case 'this_month':
                $filterStart = Carbon::now()->startOfMonth();
                $filterEnd   = Carbon::now()->endOfMonth();
                break;

            case 'last_30_days':
                $filterStart = Carbon::now()->subDays(30);
                $filterEnd   = Carbon::now();
                break;

            case 'yearly':
                $filterStart = Carbon::now()->startOfYear();
                $filterEnd   = Carbon::now()->endOfYear();
                break;

            case 'year_to_date':
                $filterStart = Carbon::now()->startOfYear();
                $filterEnd   = Carbon::now();
                break;

            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $filterStart = Carbon::parse($request->start_date)->startOfDay();
                    $filterEnd   = Carbon::parse($request->end_date)->endOfDay();
                }
                break;

            case 'all':
            default:
                // Tidak filter tanggal
                break;
        }

        $query = Products::query()
            ->leftJoin('material_request_items', 'material_request_items.product_id', '=', 'products.id')
            ->leftJoin('material_requests', 'material_requests.id', '=', 'material_request_items.material_request_id');

        // Search product
        if ($request->filled('search_product')) {
            $keyword = $request->search_product;
            $query->where(function ($q) use ($keyword) {
                $q->where('products.name', 'LIKE', "%{$keyword}%")
                    ->orWhere('products.sku', 'LIKE', "%{$keyword}%");
            });
        }

        // === SUM requested qty with DATE FILTER applied INSIDE SUM() ===
        $summary = $query
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as sku',
                DB::raw("
                COALESCE(SUM(
                    CASE 
                        WHEN " . ($filterStart ? "material_requests.requested_at >= '{$filterStart}' AND material_requests.requested_at <= '{$filterEnd}'" : "1=1") . "
                        THEN material_request_items.requested_qty
                        ELSE 0
                    END
                ), 0) AS total_requested_qty
            ")
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_requested_qty', 'DESC')
            ->get();

        return response()->json([
            'data' => $summary,
            'total_products' => $summary->count(),
            'total_requested_sum' => $summary->sum('total_requested_qty'),
        ]);
    }


    // public function create()
    // {
    //     $products = Products::with('inventoryStock')->orderBy('name', 'asc')->get();

    //     $productsJson = $products->map(function ($product) {
    //         return [
    //             'id' => $product->id,
    //             'name' => $product->name,
    //             'sku'  => $product->sku,
    //             'inventory_stock' => optional($product->inventoryStock)->inventory_stock ?? 0,
    //         ];
    //     })->toArray();

    //     return view('erp.pages.production.request-stock.create-request-stock', compact('productsJson'));
    // }

    public function create(Request $request)
    {
        // 🔹 Ambil daftar ID produk dari parameter URL (?products=1,2,3)
        $selectedIds = collect(explode(',', $request->get('products', '')))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->toArray();

        // 🔹 Ambil semua produk dengan stok inventory
        $products = \App\Models\Products::with('inventoryStock', 'productionStocks')
            ->orderBy('name', 'asc')
            ->get();

        // 🔹 Produk dalam bentuk JSON untuk Select2
        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
                'inventory_stock' => optional($product->inventoryStock)->inventory_stock ?? 0,
                'pending_waiting_list' => optional($product->productionStocks)->pending_waiting_list ?? 0,
            ];
        })->toArray();

        // 🔹 Produk yang terpilih dari halaman sebelumnya (Report Items)
        $selectedProducts = [];
        if (!empty($selectedIds)) {
            $selectedProducts = $products
                ->whereIn('id', $selectedIds)
                ->values()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku'  => $product->sku,
                        'inventory_stock' => optional($product->inventoryStock)->inventory_stock ?? 0,
                        'pending_waiting_list' => optional($product->productionStocks)->pending_waiting_list ?? 0,
                    ];
                })
                ->toArray();
        }

        return view('erp.pages.production.request-stock.create-request-stock', compact('productsJson', 'selectedProducts'));
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

                // 🔹 Update (increment) incoming_stock pada production_stocks
                $productionStock = ProductionStock::firstOrCreate(
                    ['product_id' => $item->product_id],
                    ['incoming_stock' => 0, 'available_stock' => 0]
                );

                $productionStock->increment('incoming_stock', $item->requested_qty);
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

            // ❌ Belum selesai issued/received → tidak boleh delete
            if (!($isFullyIssued || $isFullyReceived)) {
                DB::rollBack();
                return back()->with('error', 'Request Stock belum selesai issued atau received, tidak dapat dihapus.');
            }

            // 🔹 Buat Stock In otomatis (barang dikembalikan ke gudang)
            $inventory = Inventory::create([
                'material_request_id'     => $requestStock->id,
                'material_request_number' => $requestStock->material_request_number,
                'inventory_type'          => 'stock_in',
                'date'                    => now(),
                'note'                    => 'Auto Stock In (Delete Material Request)',
                'status'                  => 'Stock In',
                'user_id'                 => Auth::id(),
            ]);

            // 🔹 Tambahkan semua item ke InventoryItem
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

                // 🔹 Decrement incoming_stock di production_stocks
                $productionStock = \App\Models\ProductionStock::where('product_id', $item->product_id)->first();
                if ($productionStock) {
                    $productionStock->decrement('incoming_stock', $item->requested_qty);
                }

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

    public function deleteEmpty($id)
    {
        DB::beginTransaction();

        try {
            $requestStock = MaterialRequest::with(['items.product'])->findOrFail($id);

            // ✅ Pastikan belum ada issued atau received sama sekali
            $totalIssued = $requestStock->items->sum('issued_qty');
            $totalReceived = $requestStock->items->sum('received_qty');

            if ($totalIssued > 0 || $totalReceived > 0) {
                DB::rollBack();
                return back()->with('error', 'Request Stock sudah memiliki issued atau received, tidak bisa dihapus dengan mode ini.');
            }

            // 🔹 Hapus InventoryItem yang terhubung langsung dengan item request ini
            $itemIds = $requestStock->items->pluck('id'); // ambil semua id item request
            \App\Models\InventoryItem::whereIn('material_request_item_id', $itemIds)->delete();

            // 🔹 Hapus juga inventory yang berhubungan dengan request ini (jika ada)
            $inventories = \App\Models\Inventory::where('material_request_id', $requestStock->id)->get();

            foreach ($inventories as $inventory) {
                // Hapus semua item di inventory tersebut (jaga-jaga)
                \App\Models\InventoryItem::where('inventory_id', $inventory->id)->delete();

                // Soft delete inventory
                $inventory->delete();
            }

            // 🔹 Decrement incoming_stock untuk setiap product
            foreach ($requestStock->items as $item) {
                $productionStock = \App\Models\ProductionStock::where('product_id', $item->product_id)->first();
                if ($productionStock) {
                    $productionStock->decrement('incoming_stock', $item->requested_qty);
                }

                // 🔹 Soft delete item
                $item->delete();
            }

            // 🔹 Soft delete Material Request utama
            $requestStock->delete();

            DB::commit();

            return redirect("/erp/productions/material-request")
                ->with('success', 'Request Stock, Inventory, dan Item terkait berhasil dihapus (decrement incoming stock saja).');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delete empty material request: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus Request Stock: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $materialRequest = MaterialRequest::with('items')->findOrFail($id);

        $products = Products::with(['categories', 'discounts', 'categories.discounts'])->orderBy('name', 'asc')->get();

        $productsJson = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku'  => $product->sku,
            ];
        })->toArray();

        return view('erp.pages.production.request-stock.edit-request-stock', compact('materialRequest', 'productsJson'));
    }

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

            // ✅ Pastikan nomor tetap ada
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
                    'material_request_number' => $materialRequest->material_request_number,
                    'date'                    => $request->requested_at,
                    'status'                  => 'Stock Out',
                ]);
            }

            // 🔹 Hapus item lama yang dihapus dari form
            $existingItemIds = $materialRequest->items->pluck('id')->toArray();
            $incomingItemIds = $request->item_id ?? [];
            $itemsToDelete   = array_diff($existingItemIds, $incomingItemIds);

            if (!empty($itemsToDelete)) {
                foreach ($itemsToDelete as $deleteId) {
                    $oldItem = MaterialRequestItem::find($deleteId);
                    if ($oldItem) {
                        // 🔸 Kurangi stok incoming lama
                        $prodStock = \App\Models\ProductionStock::where('product_id', $oldItem->product_id)->first();
                        if ($prodStock) {
                            $prodStock->decrement('incoming_stock', $oldItem->requested_qty);
                        }

                        $oldItem->delete();
                        InventoryItem::where('material_request_item_id', $deleteId)->delete();
                    }
                }
            }

            // 🔹 Loop item yang dikirim di form
            foreach ($request->product as $key => $productId) {
                $qty = (float) $request->qty[$key];
                $itemId = $incomingItemIds[$key] ?? null;

                if ($itemId) {
                    // 🔸 Update item lama
                    $reqItem = MaterialRequestItem::find($itemId);
                    if ($reqItem) {
                        $oldQty = $reqItem->requested_qty;
                        $oldProduct = $reqItem->product_id;

                        // 🔸 Jika produk sama → sesuaikan selisih qty
                        if ($oldProduct == $productId) {
                            $diff = $qty - $oldQty;
                            if ($diff != 0) {
                                $prodStock = \App\Models\ProductionStock::firstOrCreate(
                                    ['product_id' => $productId],
                                    ['incoming_stock' => 0, 'available_stock' => 0]
                                );
                                $prodStock->increment('incoming_stock', $diff);
                            }
                        } else {
                            // 🔸 Jika produk diganti → kembalikan stok lama, tambahkan stok baru
                            $oldStock = \App\Models\ProductionStock::where('product_id', $oldProduct)->first();
                            if ($oldStock) {
                                $oldStock->decrement('incoming_stock', $oldQty);
                            }
                            $newStock = \App\Models\ProductionStock::firstOrCreate(
                                ['product_id' => $productId],
                                ['incoming_stock' => 0, 'available_stock' => 0]
                            );
                            $newStock->increment('incoming_stock', $qty);
                        }

                        // 🔸 Update data request & inventory
                        $reqItem->update([
                            'product_id'    => $productId,
                            'requested_qty' => $qty,
                        ]);

                        $invItem = InventoryItem::where('material_request_item_id', $reqItem->id)->first();
                        if ($invItem) {
                            $invItem->update([
                                'product_id'              => $productId,
                                'quantity'                => $qty,
                                'remaining_stock_in'      => $qty - $invItem->stock_out,
                                'material_request_number' => $materialRequest->material_request_number,
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
                            'material_request_number'  => $materialRequest->material_request_number,
                        ]);
                    }

                    // 🔹 Tambah incoming_stock untuk item baru
                    $productionStock = \App\Models\ProductionStock::firstOrCreate(
                        ['product_id' => $productId],
                        ['incoming_stock' => 0, 'available_stock' => 0]
                    );
                    $productionStock->increment('incoming_stock', $qty);
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
                'verified_by' => Auth::id(),
            ]);

            foreach ($requestStock->items as $item) {
                $item->update([
                    'received_qty' => $item->issued_qty,
                    'issued_qty' => 0,
                ]);

                $productionStock = ProductionStock::where('product_id', $item->product_id)->first();
                if ($productionStock) {
                    $productionStock->decrement('incoming_stock', $item->received_qty);
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
            $materialRequest = MaterialRequest::onlyTrashed()
                ->with(['items' => function ($q) {
                    $q->withTrashed();
                }])
                ->findOrFail($id);

            // Hitung status issued & received
            $totalIssued = $materialRequest->items->sum('issued_qty');
            $totalReceived = $materialRequest->items->sum('received_qty');
            $isEmpty = $totalIssued == 0 && $totalReceived == 0;

            // ✅ Restore MaterialRequest dan items-nya
            $materialRequest->restore();
            $materialRequest->items()->withTrashed()->restore();

            if ($isEmpty) {
                // 🔹 Restore inventory yang terkait
                $inventories = \App\Models\Inventory::onlyTrashed()
                    ->where('material_request_id', $materialRequest->id)
                    ->get();

                foreach ($inventories as $inventory) {
                    $inventory->restore();

                    // 🔹 Restore inventory items yang terkait inventory ini
                    \App\Models\InventoryItem::onlyTrashed()
                        ->where('inventory_id', $inventory->id)
                        ->restore();
                }

                // 🔹 Restore inventory items yang terhubung langsung via material_request_item_id
                $itemIds = $materialRequest->items->pluck('id');
                \App\Models\InventoryItem::onlyTrashed()
                    ->whereIn('material_request_item_id', $itemIds)
                    ->restore();

                // 🔹 Increment incoming_stock kembali ke production stock
                foreach ($materialRequest->items as $item) {
                    $productionStock = \App\Models\ProductionStock::where('product_id', $item->product_id)->first();
                    if ($productionStock) {
                        $productionStock->increment('incoming_stock', $item->requested_qty);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Request Stock dan Inventory terkait berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore material request gagal', [
                'material_request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Gagal mengembalikan request stock! ' . $e->getMessage());
        }
    }
}
