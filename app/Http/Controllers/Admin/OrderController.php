<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Products;
use App\Models\Customers;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAddresses;
use Carbon\Carbon;
use App\Models\Discount;
use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function showDetail($id)
    {
        $order = Order::with(['customer', 'items.histories'])->findOrFail($id);
        return view('erp.pages.orders.order-detail', compact('order'));
    }

    public function create()
    {
        $products = Products::all();
        $customers = Customers::with('addresses')->get();
        $discount = Discount::first();
        $transactionTypes = Account::where('name', 'Sale')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.orders.create-order', compact('products', 'customers', 'discount', 'cashAccounts', 'bankAccounts', 'transactionTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_date'            => 'required|date',
            'customers'             => 'required|array',
            'customers.*'           => 'exists:customers,id',
            'addresses'             => 'required|array',
            'addresses.*'           => 'exists:customer_addresses,id',
            'payment_status'        => 'required|string',
            'paid_amount'           => 'required|numeric|min:0',
            'status'                => 'required|string',
            'notes'                 => 'nullable|string',
            'product'               => 'required|array',
            'product.*'             => 'exists:products,id',
            'qty'                   => 'required|array',
            'qty.*'                 => 'numeric|min:1',
            'price'                 => 'required|array',
            'price.*'               => 'numeric|min:0',
            'total'                 => 'required|array',
            'total.*'               => 'numeric|min:0',
            'sub_total'             => 'required|numeric|min:0',
            'total_amount'          => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'transaction_date'      => 'required|date',
            'transaction_type'      => 'required|exists:accounts,id',
            'particular'            => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $orderDate = Carbon::parse($request->order_date);
            $orderDateFormatted = $orderDate->format('dmy');
            $todayOrderCount = Order::whereDate('order_date', $orderDate->toDateString())->count();
            $orderSequence = $todayOrderCount + 1;
            $prefix = $request->status === 'Draft' ? 'SO' : 'INV';
            $orderNumber = $prefix . '/' . $orderSequence . '/ALS/' . $orderDateFormatted;

            $addressModel = CustomerAddresses::find($request->addresses[0]);

            $totalDiscount = 0;
            $grandTotal = 0;

            foreach ($request->product as $index => $productId) {
                $product = Products::with('categories', 'discounts')->find($productId);
                $qty = $request->qty[$index];
                $originalPrice = $product->price;
                $totalBeforeDiscount = $originalPrice * $qty;

                $discount = $product->getApplicableDiscount($product);
                $discountTotal = 0;
                $subtotal = $totalBeforeDiscount;

                if ($discount) {
                    $eligible = false;
                    if ($discount->minimum_based_on === 'Quantity of Items' && $qty >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    } elseif ($discount->minimum_based_on === 'Purchase Amount' && $totalBeforeDiscount >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    }

                    if ($eligible) {
                        $discountTotal = $discount->type === 'Percentage'
                            ? $totalBeforeDiscount * ($discount->amount / 100)
                            : $discount->amount;
                        $discountTotal = min($discountTotal, $totalBeforeDiscount);
                        $subtotal -= $discountTotal;
                    }
                }

                $totalDiscount += $discountTotal;
                $grandTotal += $subtotal;
            }

            $paidAmount = $request->payment_status === 'Paid' ? $grandTotal : $request->paid_amount;
            $remainingAmount = $grandTotal - $paidAmount;

            $groupId = Str::uuid();
            $saleAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            $order = Order::create([
                'customer_id'      => $request->customers[0],
                'order_number'     => $orderNumber,
                'order_date'       => $request->order_date,
                'total_amount'     => $request->total_amount,
                'status'           => $request->status,
                'payment_status'   => $request->payment_status,
                'paid_amount'      => $paidAmount,
                'shipping_address' => $addressModel?->address,
                'google_maps'      => $addressModel?->google_maps,
                'notes'            => $request->notes,
                'discount'         => $totalDiscount,
                'grand_total'      => $grandTotal,
                'remaining_amount' => $remainingAmount,
            ]);

            foreach ($request->product as $index => $productId) {
                $product = Products::with('categories', 'discounts')->find($productId);
                $qty = $request->qty[$index];
                $originalPrice = $product->price;
                $totalBeforeDiscount = $originalPrice * $qty;

                $discount = $product->getApplicableDiscount($product);
                $discountTotal = 0;
                $subtotal = $totalBeforeDiscount;

                if ($discount) {
                    $eligible = false;
                    if ($discount->minimum_based_on === 'Quantity of Items' && $qty >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    } elseif ($discount->minimum_based_on === 'Purchase Amount' && $totalBeforeDiscount >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    }

                    if ($eligible) {
                        $discountTotal = $discount->type === 'Percentage'
                            ? $totalBeforeDiscount * ($discount->amount / 100)
                            : $discount->amount;
                        $discountTotal = min($discountTotal, $totalBeforeDiscount);
                        $subtotal -= $discountTotal;
                    }
                }

                $finalPrice = $subtotal / $qty;

                OrderItem::create([
                    'order_id'             => $order->id,
                    'product_id'           => $productId,
                    'product_name'         => '',
                    'quantity'             => $qty,
                    'completed_quantity'   => 0,
                    'price'                => $originalPrice,
                    'subtotal'             => $totalBeforeDiscount,
                    'discount_price'       => $finalPrice,
                    'total_after_discount' => $subtotal,
                ]);
            }

            AccountTransaction::create([
                'order_id'             => $order->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $saleAccount->id,
                'debit'                => 0,
                'credit'               => $grandTotal,
                'note'                 => $request->note,
                'particular'           => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            AccountTransaction::create([
                'order_id'             => $order->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $cashBankAccount->id,
                'debit'                => $grandTotal,
                'credit'               => 0,
                'note'                 => $request->note,
                'particular'           => $saleAccount->name . ' - ' . $saleAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            $order->transaction_group_id = $groupId;
            $order->save();

            DB::commit();

            return redirect("/erp/orders/" . strtolower(str_replace(' ', '-', $order->status)))
                ->with('success', 'Order berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan order: ' . $e->getMessage());
        }
    }



    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->findOrFail($id);

            OrderItem::where('order_id', $order->id)->delete();

            if ($order->transaction_group_id) {
                AccountTransaction::where('transaction_group_id', $order->transaction_group_id)->delete();
            }

            $order->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Order berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus order: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);

        $transactions = AccountTransaction::where('order_id', $order->id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/orders/sale-list')->with('error', 'Transaksi tidak ditemukan.');
        }

        $transactionGroupId = $transactions->first()->transaction_group_id;

        if (!$transactionGroupId) {
            return redirect('/erp/orders/sale-list')->with('error', 'Transaction group ID tidak ditemukan.');
        }

        $groupTransactions = AccountTransaction::where('transaction_group_id', $transactionGroupId)->get();

        $debitTransaction = $groupTransactions->first(function ($trx) {
            return (float) $trx->debit > 0;
        });

        $creditTransaction = $groupTransactions->first(function ($trx) {
            return (float) $trx->credit > 0;
        });

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/orders/sale-list')->with('error', 'Transaksi debit/kredit tidak lengkap.');
        }

        $products = Products::all();
        $customers = Customers::with('addresses')->get();

        $transactionTypes = Account::where('name', 'Sale')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();
        $accounts = Account::all();

        $sale = $creditTransaction;

        return view('erp.pages.orders.edit-order', compact(
            'order',
            'products',
            'customers',
            'transactionTypes',
            'cashAccounts',
            'bankAccounts',
            'accounts',
            'sale',
            'debitTransaction',
            'creditTransaction'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'order_date'            => 'required|date',
            'customers'             => 'required|array',
            'customers.*'           => 'exists:customers,id',
            'address_id'            => 'required|exists:customer_addresses,id',
            'payment_status'        => 'required|string',
            'paid_amount'           => 'required|numeric|min:0',
            'status'                => 'required|string',
            'notes'                 => 'nullable|string',
            'product'               => 'required|array',
            'product.*'             => 'exists:products,id',
            'qty'                   => 'required|array',
            'qty.*'                 => 'numeric|min:1',
            'price'                 => 'required|array',
            'price.*'               => 'numeric|min:0',
            'total'                 => 'required|array',
            'total.*'               => 'numeric|min:0',
            'sub_total'             => 'required|numeric|min:0',
            'total_amount'          => 'required|numeric|min:0',
            'cash_bank_account_id'  => 'required|exists:accounts,id',
            'note'                  => 'nullable|string',
            'transaction_date'      => 'required|date',
            'transaction_type'      => 'required|exists:accounts,id',
            'particular'            => 'nullable|string',
            'order_item_ids'        => 'nullable|array',
            'order_item_ids.*'      => 'nullable|integer|exists:order_items,id',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->findOrFail($id);
            $address = CustomerAddresses::findOrFail($request->address_id);
            $groupId = $order->transaction_group_id ?? Str::uuid();

            $totalDiscount = 0;
            $grandTotal = 0;

            $existingItemIds = $order->orderItems->pluck('id')->toArray();
            $submittedItemIds = $request->order_item_ids ?? [];

            foreach ($request->product as $index => $productId) {
                $qty = $request->qty[$index];
                $product = Products::with('categories', 'discounts')->find($productId);
                $price = $product->price;
                $totalBeforeDiscount = $price * $qty;

                $discount = $product->getApplicableDiscount($product);
                $discountTotal = 0;
                $subtotal = $totalBeforeDiscount;

                if ($discount) {
                    $eligible = false;
                    if ($discount->minimum_based_on === 'Quantity of Items' && $qty >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    } elseif ($discount->minimum_based_on === 'Purchase Amount' && $totalBeforeDiscount >= $discount->minimum_qty_or_amount) {
                        $eligible = true;
                    }

                    if ($eligible) {
                        $discountTotal = $discount->type === 'Percentage'
                            ? $totalBeforeDiscount * ($discount->amount / 100)
                            : $discount->amount;
                        $discountTotal = min($discountTotal, $totalBeforeDiscount);
                        $subtotal -= $discountTotal;
                    }
                }

                $totalDiscount += $discountTotal;
                $grandTotal += $subtotal;
                $finalPrice = $subtotal / $qty;

                $dataItem = [
                    'order_id'             => $order->id,
                    'product_id'           => $productId,
                    'product_name'         => '',
                    'quantity'             => $qty,
                    'completed_quantity'   => 0,
                    'price'                => $price,
                    'subtotal'             => $totalBeforeDiscount,
                    'discount_price'       => $finalPrice,
                    'total_after_discount' => $subtotal,
                ];

                $itemId = $submittedItemIds[$index] ?? null;
                if ($itemId && in_array($itemId, $existingItemIds)) {
                    OrderItem::find($itemId)?->update($dataItem);
                } else {
                    OrderItem::create($dataItem);
                }
            }

            $toDelete = array_diff($existingItemIds, array_filter($submittedItemIds));
            if (!empty($toDelete)) {
                OrderItem::whereIn('id', $toDelete)->delete();
            }

            $paidAmount = $request->payment_status === 'Paid' ? $grandTotal : $request->paid_amount;
            $remainingAmount = $grandTotal - $paidAmount;

            $order->update([
                'customer_id'         => $request->customers[0],
                'order_date'          => $request->order_date,
                'status'              => $request->status,
                'payment_status'      => $request->payment_status,
                'payment_method'      => $request->payment_method ?? null,
                'paid_amount'         => $paidAmount,
                'remaining_amount'    => $remainingAmount,
                'discount'            => $totalDiscount,
                'grand_total'         => $grandTotal,
                'total_amount'        => $request->total_amount,
                'shipping_address'    => $address->address,
                'google_maps'         => $address->google_maps,
                'notes'               => $request->notes,
                'transaction_group_id' => $groupId,
            ]);

            AccountTransaction::where('transaction_group_id', $groupId)->delete();

            $saleAccount = Account::findOrFail($request->transaction_type);
            $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

            AccountTransaction::create([
                'order_id'             => $order->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $saleAccount->id,
                'debit'                => 0,
                'credit'               => $grandTotal,
                'note'                 => $request->note,
                'particular'           => $cashBankAccount->name . ' - ' . $cashBankAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            AccountTransaction::create([
                'order_id'             => $order->id,
                'transaction_date'     => $request->transaction_date,
                'account_id'           => $cashBankAccount->id,
                'debit'                => $grandTotal,
                'credit'               => 0,
                'note'                 => $request->note,
                'particular'           => $saleAccount->name . ' - ' . $saleAccount->type,
                'transaction_group_id' => $groupId,
            ]);

            DB::commit();

            return redirect("/erp/orders/" . strtolower(str_replace(' ', '-', $order->status)))
                ->with('success', 'Order berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }
}
