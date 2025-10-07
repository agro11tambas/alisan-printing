<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Products;
use App\Models\Supplier;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Inventory;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function getPurchaseOrders()
    {
        return view('erp.pages.purchases.purchase-orders');
    }

    public function dataPurchaseOrders(Request $request)
    {
        $purchases = Purchase::with('supplier')
            ->where('status', 'Purchase Orders');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchases->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('purchase_number')) {
            $purchases->where('purchase_number', 'like', '%' . $request->purchase_number . '%');
        }

        if ($request->filled('supplier_name')) {
            $purchases->whereHas('supplier', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->supplier_name . '%');
            });
        }

        $purchases = $purchases->latest()->get();

        return DataTables::of($purchases)
            ->addIndexColumn()
            ->addColumn('purchase_number', function ($purchase) {
                return $purchase->purchase_number;
            })
            ->addColumn('purchase_date', function ($purchase) {
                return $purchase->purchase_date;
            })
            ->addColumn('supplier', function ($purchase) {
                return $purchase->supplier->name;
            })
            ->addColumn('total_amount', function ($purchase) {
                return 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
            })
            ->addColumn('payment_status', function ($purchase) {
                $payment_status = strtolower($purchase->payment_status);

                switch ($payment_status) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->payment_status . '</div>';
                        break;
                    case 'unpaid':
                        return '<div class="badge bg-soft-danger text-danger">' . $purchase->payment_status . '</div>';
                        break;
                    default:
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->payment_status . '</div>';
                        break;
                }
            })
            ->addColumn('account_name', function ($purchase) {
                return optional($purchase->purchaseAccount)->type ?? '-';
            })
            ->addColumn('payment_method', function ($purchase) {
                return $purchase->payment_method;
            })
            ->addColumn('status', function ($purchase) {
                $status = strtolower($purchase->status);

                switch ($status) {
                    case 'purchase orders':
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->status . '</div>';
                        break;
                    case 'purchase list':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->status . '</div>';
                        break;
                }
            })
            ->addColumn('action', function ($purchase) {
                return view('erp.pages.purchases.partials.action-button', compact('purchase'))->render();
            })
            ->rawColumns(['total_amount', 'payment_status', 'action'])
            ->make(true);
    }

    public function getPurchaseList()
    {
        return view('erp.pages.purchases.purchase-list.purchase-list');
    }

    public function dataPurchaseList(Request $request)
    {
        $purchases = Purchase::with('supplier')
            ->where('status', 'Purchase List');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchases->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('purchase_number')) {
            $purchases->where('purchase_number', 'like', '%' . $request->purchase_number . '%');
        }

        if ($request->filled('supplier_name')) {
            $purchases->whereHas('supplier', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->supplier_name . '%');
            });
        }

        $purchases = $purchases->latest()->get();

        return DataTables::of($purchases)
            ->addIndexColumn()
            ->addColumn('purchase_number', function ($purchase) {
                return $purchase->purchase_number;
            })
            ->addColumn('purchase_date', function ($purchase) {
                return $purchase->purchase_date;
            })
            ->addColumn('supplier', function ($purchase) {
                return $purchase->supplier->name;
            })
            ->addColumn('total_amount', function ($purchase) {
                return 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
            })
            ->addColumn('paid_amount', function ($purchase) {
                return '<span class="text-success">Rp ' . number_format($purchase->paid_amount, 0, ',', '.') . '</span>';
            })
            ->addColumn('remaining_amount', function ($purchase) {
                return '<span class="text-danger">Rp ' . number_format($purchase->remaining_amount, 0, ',', '.') . '</span>';
            })
            ->addColumn('payment_status', function ($purchase) {
                $payment_status = strtolower($purchase->payment_status);

                switch ($payment_status) {
                    case 'paid':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->payment_status . '</div>';
                        break;
                    case 'unpaid':
                        return '<div class="badge bg-soft-danger text-danger">' . $purchase->payment_status . '</div>';
                        break;
                    default:
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->payment_status . '</div>';
                        break;
                }
            })
            ->addColumn('payment_method', function ($purchase) {
                return $purchase->payment_method;
            })
            ->addColumn('status', function ($purchase) {
                $status = strtolower($purchase->status);

                switch ($status) {
                    case 'purchase orders':
                        return '<div class="badge bg-soft-warning text-warning">' . $purchase->status . '</div>';
                        break;
                    case 'purchase list':
                        return '<div class="badge bg-soft-success text-success">' . $purchase->status . '</div>';
                        break;
                }
            })
            ->addColumn('action', function ($purchase) {
                return view('erp.pages.purchases.partials.action-button', compact('purchase'))->render();
            })
            ->rawColumns(['total_amount', 'paid_amount', 'remaining_amount', 'payment_status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::all();
        $suppliers = Supplier::all();

        $transactionTypes = Account::where('name', 'Purchase')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.purchases.create-purchase', compact('products', 'suppliers', 'transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'purchase_date'     => 'required|date',
            'suppliers' => 'required|exists:suppliers,id',
            'product'           => 'required|array',
            'product.*'         => 'exists:purchase_products,id',
            'qty'               => 'required|array',
            'qty.*'             => 'numeric|min:1',
            'price'             => 'required|array',
            'price.*'           => 'numeric|min:0',
            'total'             => 'required|array',
            'total.*'           => 'numeric|min:0',
            'sub_total'         => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'transaction_date'      => 'required|date',
            'transaction_type'      => 'required|exists:accounts,id',
            'particular'            => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchaseDate = Carbon::parse($request->purchase_date);
            $purchaseDateFormatted = $purchaseDate->format('dmy');
            $todayPurchaseCount = Purchase::whereDate('purchase_date', $purchaseDate->toDateString())->count();
            $purchaseSequence = $todayPurchaseCount + 1;
            $prefix = $request->status === 'Purchase Orders' ? 'PO' : ' ';
            $purchaseNumber = $prefix . '/' . $purchaseSequence . '/ALS/' . $purchaseDateFormatted;

            $calculatedTotalAmount = array_sum($request->total);

            $paidAmount = $request->payment_status === 'Paid' ? $calculatedTotalAmount : $request->paid_amount;
            $remainingAmount = $calculatedTotalAmount - $paidAmount;

            $groupId = Str::uuid();
            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            $paidAmount = 0;
            $paymentStatus = 'Unpaid';
            $status = 'Purchase Orders';

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'purchase_date'   => $request->purchase_date,
                'supplier_id'     => $request->suppliers,
                'payment_status'  => $paymentStatus,
                'paid_amount'     => $paidAmount,
                'total_amount'    => $calculatedTotalAmount,
                'remaining_amount' => $remainingAmount,
                'status'          => $status,
                'transaction_type'=> $purchaseAccount->id
            ]);

            foreach ($request->product as $index => $productId) {
                $qty   = $request->qty[$index];
                $price = $request->price[$index];
                $total = $request->total[$index];

                $product = Products::findOrFail($productId);

                PurchaseItem::create([
                    'purchase_id'         => $purchase->id,
                    'purchase_product_id' => $productId,
                    'product_name'        => $product->name,
                    'quantity'            => $qty,
                    'price'               => $price,
                    'subtotal'            => $total,
                ]);
            }

            AccountTransaction::create([
                'purchase_id'             => $purchase->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $purchaseAccount->id,
                'debit'                => $calculatedTotalAmount,
                'credit'               => 0,
                'note'                 => $request->note,
                'particular'           => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            AccountTransaction::create([
                'purchase_id'             => $purchase->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $cashBankAccount->id,
                'debit'                => 0,
                'credit'               => $calculatedTotalAmount,
                'note'                 => $request->note,
                'particular'           => $purchaseAccount->name . ' - ' . $purchaseAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            $purchase->transaction_group_id = $groupId;
            $purchase->save();

            if ($purchase->status === 'Purchase List') {
                $inventory = Inventory::create([
                    'purchase_id'     => $purchase->id,
                    'supplier_id'     => $purchase->supplier_id,
                    'purchase_number' => $purchase->purchase_number,
                    'date'            => $purchase->purchase_date,
                ]);

                foreach (PurchaseItem::where('purchase_id', $purchase->id)->get() as $item) {
                    InventoryItem::create([
                        'inventory_id'         => $inventory->id,
                        'purchase_product_id'  => $item->purchase_product_id,
                        'quantity'             => $item->quantity,
                        'price'                => $item->price,
                        'stock_in'             => 0,
                        'remaining_stock_in'   => $item->quantity,
                        'stock_out'            => 0,
                    ]);
                }
            }

            DB::commit();
            return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase store failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Purchase order failed to create');
        }
    }

    public function edit($id)
    {
        $purchase = Purchase::with('purchaseItems.purchaseProduct')->findOrFail($id);

        $transactions = AccountTransaction::where('purchase_id', $purchase->id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/purchases/purchase-list')->with('error', 'Transaksi tidak ditemukan.');
        }

        $transactionGroupId = $transactions->first()->transaction_group_id;

        if (!$transactionGroupId) {
            return redirect('/erp/purchases/purchase-list')->with('error', 'Transaction group ID tidak ditemukan.');
        }

        $groupTransactions = AccountTransaction::where('transaction_group_id', $transactionGroupId)->get();

        $debitTransaction = $groupTransactions->first(function ($trx) {
            return (float) $trx->debit > 0;
        });

        $creditTransaction = $groupTransactions->first(function ($trx) {
            return (float) $trx->credit > 0;
        });

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/purchases/purchase-list')->with('error', 'Transaksi debit/kredit tidak lengkap.');
        }

        $products = Products::all();
        $suppliers = Supplier::all();

        $transactionTypes = Account::where('name', 'Purchase')->get(); // Sesuaikan filter
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();
        $accounts = Account::all();

        $purchaseAccountTransaction = $debitTransaction;

        return view('erp.pages.purchases.edit-purchase', compact(
            'purchase',
            'products',
            'suppliers',
            'transactionTypes',
            'cashAccounts',
            'bankAccounts',
            'accounts',
            'debitTransaction',
            'creditTransaction',
            'purchaseAccountTransaction'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_date'        => 'required|date',
            'suppliers'         => 'required',
            'suppliers.*'       => 'exists:suppliers,id',
            'payment_status'    => 'required|string',
            'paid_amount'       => 'required|numeric|min:0',
            'purchase_number' => 'required|string|unique:purchases,purchase_number,' . $id,
            'status'            => 'required|string',
            'notes'             => 'nullable|string',
            'product'           => 'required|array',
            'product.*'         => 'exists:purchase_products,id',
            'qty'               => 'required|array',
            'qty.*'             => 'numeric|min:1',
            'price'             => 'required|array',
            'price.*'           => 'numeric|min:0',
            'total'             => 'required|array',
            'total.*'           => 'numeric|min:0',
            'sub_total'         => 'required|numeric|min:0',
            'total_amount'      => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'transaction_date'      => 'required|date',
            'transaction_type'      => 'required|exists:accounts,id',
            'particular'            => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::findOrFail($id);

            if ($request->hasFile('image')) {
                if ($purchase->image && file_exists(public_path('storage/' . $purchase->image))) {
                    unlink(public_path('storage/' . $purchase->image));
                }

                $image = $request->file('image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('storage/uploads/products'), $filename);
                $imagePath = 'uploads/products/' . $filename;
                $purchase->image = $imagePath;
            }

            $calculatedTotalAmount = array_sum($request->total);

            $paidAmount = $request->payment_status === 'Paid' ? $calculatedTotalAmount : $request->paid_amount;
            $remainingAmount = $calculatedTotalAmount - $paidAmount;

            $purchase->update([
                'purchase_date'     => $request->purchase_date,
                'supplier_id'       => $request->suppliers,
                'payment_status'    => $request->payment_status,
                'paid_amount'       => $paidAmount,
                'total_amount'      => $calculatedTotalAmount,
                'remaining_amount'  => $remainingAmount,
                'status'            => $request->status,
            ]);

            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            foreach ($request->product as $key => $productId) {
                $qty   = $request->qty[$key];
                $price = $request->price[$key];
                $total = $request->total[$key];

                $product = Products::findOrFail($productId);

                // $oldStock = $product->stock;
                // $oldAvgCost = $product->avg_cost;

                // $totalCostOld = $oldStock * $oldAvgCost;
                // $totalCostNew = $qty * $price;

                // $newStock = $oldStock + $qty;
                // $newAvgCost = $newStock > 0 ? ($totalCostOld + $totalCostNew) / $newStock : $price;

                // $product->update([
                //     'stock' => $newStock,
                //     'avg_cost' => $newAvgCost,
                // ]);

                PurchaseItem::create([
                    'purchase_id'         => $purchase->id,
                    'purchase_product_id' => $productId,
                    'product_name'        => $product->name,
                    'quantity'            => $qty,
                    'price'               => $price,
                    'subtotal'            => $total,
                ]);
            }

            $groupId = $purchase->transaction_group_id;
            if (!$groupId) {
                $groupId = Str::uuid();
                $purchase->transaction_group_id = $groupId;
                $purchase->save();
            }

            AccountTransaction::where('transaction_group_id', $groupId)->delete();

            $purchaseAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            AccountTransaction::create([
                'purchase_id'             => $purchase->id,
                'transaction_date'        => $request->transaction_date,
                'account_id'              => $purchaseAccount->id,
                'debit'                   => $calculatedTotalAmount,
                'credit'                  => 0,
                'note'                    => $request->note,
                'particular'              => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
                'transaction_group_id'    => $groupId,
            ]);

            AccountTransaction::create([
                'purchase_id'             => $purchase->id,
                'transaction_date'        => $request->transaction_date,
                'account_id'              => $cashBankAccount->id,
                'debit'                   => 0,
                'credit'                  => $calculatedTotalAmount,
                'note'                    => $request->note,
                'particular'              => $purchaseAccount->name . ' - ' . $purchaseAccount->type,
                'transaction_group_id'    => $groupId,
            ]);

            if ($request->status === 'Purchase List') {
                $warehouse = Inventory::firstOrCreate(
                    ['purchase_id' => $purchase->id],
                    [
                        'purchase_number' => $purchase->purchase_number,
                        'date'            => $purchase->purchase_date,
                    ]
                );

                // Hapus item lama agar sinkron
                InventoryItem::where('inventory_id', $warehouse->id)->delete();

                // Ambil ulang item baru dan simpan
                $items = PurchaseItem::where('purchase_id', $purchase->id)->get();

                foreach ($items as $item) {
                    InventoryItem::create([
                        'inventory_id'         => $warehouse->id,
                        'purchase_product_id'  => $item->purchase_product_id,
                        'quantity'             => $item->quantity,
                        'stock_in'             => 0,
                        'remaining_stock_in'   => $item->quantity,
                        'stock_out'            => 0,
                    ]);
                }
            } else {
                // Kalau status bukan "Purchase List", maka hapus data warehouse jika sebelumnya pernah dibuat
                $existingWarehouse = Inventory::where('purchase_id', $purchase->id)->first();
                if ($existingWarehouse) {
                    InventoryItem::where('inventory_id', $existingWarehouse->id)->delete();
                    $existingWarehouse->delete();
                }
            }

            DB::commit();

            switch ($request->payment_status) {
                case 'Paid':
                    return redirect('/erp/purchases/purchase-list')->with('success', 'Purchase saved successfully');
                case 'Unpaid':
                    return redirect('/erp/purchases/purchase-orders')->with('success', 'Purchase saved successfully');
                default:
                    return redirect()->back()->with('success', 'Purchase saved');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Purchase order failed to update' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $purchase = Purchase::with('purchaseItems')->findOrFail($id);

            foreach ($purchase->purchaseItems as $item) {
                $product = Products::findOrFail($item->purchase_product_id);

                $oldStock = $product->stock;
                $oldAvgCost = $product->avg_cost;

                $totalCost = $oldStock * $oldAvgCost - ($item->quantity * $item->price);
                $newStock = $oldStock - $item->quantity;
                $newAvgCost = $newStock > 0 ? $totalCost / $newStock : 0;

                $product->update([
                    'stock' => max(0, $newStock),
                    'avg_cost' => $newAvgCost,
                ]);
            }

            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            if ($purchase->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $purchase->transaction_group_id)->delete();
            }

            if ($purchase->image && file_exists(public_path('storage/' . $purchase->image))) {
                unlink(public_path('storage/' . $purchase->image));
            }

            $purchase->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Purchase deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase delete failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete purchase: ' . $e->getMessage());
        }
    }
}
