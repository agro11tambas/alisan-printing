<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Models\OpeningBalance;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Expense;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboard()
    {
        return view('erp.pages.dashboard.index');
    }

    public function summary(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $start  = $request->get('start_date');
        $end    = $request->get('end_date');

        // Tentukan range tanggal sesuai filter
        [$startDate, $endDate] = $this->getDateRange($filter, $start, $end);

        // ==============================
        // ACCOUNT (by type)
        // ==============================
        $accounts = Account::with(['transactions' => function ($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate]);
            }
        }])->get();

        $response = [
            'totalOpeningBalance'   => 0,
            'totalSaleAccount'      => 0,
            'totalPurchaseAccount'  => 0,
            'totalExpenseAccount'   => 0,
            'totalBankAccount'      => 0,
            'totalCashAccount'      => 0,
            'grossProfit'           => 0,
            'netProfit'             => 0,

            // orders & purchases
            'totalSaleOrder'        => 0,
            'totalSaleOrderList'    => 0,
            'totalSaleList'         => 0,
            'totalPurchaseOrder'    => 0,
            'totalPurchaseOrderList' => 0,
            'totalPurchaseList'     => 0,
        ];

        foreach ($accounts as $acc) {
            $debit  = $acc->transactions->sum('debit');
            $credit = $acc->transactions->sum('credit');

            if ($filter === 'all') {
                $total = ($acc->opening_balance ?? 0) + ($acc->closing_balance ?? 0);
            } else {
                $total = ($acc->opening_balance ?? 0) + ($debit - $credit);
            }

            // TOTAL PER NAME (misalnya Bank, Cash, Expense)
            $response['totalByName'][$acc->name] =
                ($response['totalByName'][$acc->name] ?? 0) + $total;

            // BREAKDOWN PER TYPE DI BAWAH NAME
            $response['breakdownByName'][$acc->name][$acc->type] =
                ($response['breakdownByName'][$acc->name][$acc->type] ?? 0) + $total;
        }

        // ==============================
        // SALE ORDERS
        // ==============================
        $saleOrdersBase = Order::query();
        if ($startDate && $endDate) {
            $saleOrdersBase->whereBetween('order_date', [$startDate, $endDate]);
        }

        $saleOrders = clone $saleOrdersBase;

        $response['totalSaleOrder']     = $saleOrders->sum('grand_total');
        $response['totalSaleOrderList'] = $saleOrdersBase->count();
        $response['totalSaleList']      = $saleOrdersBase->count();

        $response['saleListStatus'] = [
            'paid'           => (clone $saleOrdersBase)->where('payment_status', 'Paid')->count(),
            'unpaid'         => (clone $saleOrdersBase)->where('payment_status', 'Unpaid')->count(),
            'overdue'        => (clone $saleOrdersBase)->where('payment_status', 'Overdue')->count(),
            'partially_paid' => (clone $saleOrdersBase)->where('payment_status', 'Partially Paid')->count(),
        ];

        $response['receivedFromCustomer']   = $saleOrdersBase->sum('paid_amount');
        $response['receivableFromCustomer'] = $saleOrdersBase->sum('grand_total') - $saleOrdersBase->sum('paid_amount');

        // ==============================
        // PURCHASE ORDERS
        // ==============================
        $purchaseOrdersBase = Purchase::query();
        if ($startDate && $endDate) {
            $purchaseOrdersBase->whereBetween('purchase_date', [$startDate, $endDate]);
        }

        $purchaseOrders = clone $purchaseOrdersBase;

        $response['totalPurchaseOrder']     = $purchaseOrders->sum('total_amount');
        $response['totalPurchaseOrderList'] = $purchaseOrdersBase->count();
        $response['totalPurchaseList']      = $purchaseOrdersBase->count();

        $response['purchaseListStatus'] = [
            'paid'           => (clone $purchaseOrdersBase)->where('payment_status', 'Paid')->count(),
            'unpaid'         => (clone $purchaseOrdersBase)->where('payment_status', 'Unpaid')->count(),
            'overdue'        => (clone $purchaseOrdersBase)->where('payment_status', 'Overdue')->count(),
            'partially_paid' => (clone $purchaseOrdersBase)->where('payment_status', 'Partially Paid')->count(),
        ];

        $response['paidToSupplier']   = $purchaseOrdersBase->sum('paid_amount');
        $response['payableToSupplier'] = $purchaseOrdersBase->sum('total_amount') - $purchaseOrdersBase->sum('paid_amount');


        // ==============================
        // PROFIT
        // ==============================
        $response['grossProfit'] = $response['totalSaleAccount'] - $response['totalPurchaseAccount'];
        $response['netProfit']   = $response['grossProfit'] - $response['totalExpenseAccount'];

        return response()->json($response);
    }

    private function getDateRange($filter, $start, $end)
    {
        $startDate = null;
        $endDate   = null;

        switch ($filter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate   = Carbon::today()->endOfDay();
                break;
            case 'last_7_days':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate   = Carbon::now()->endOfDay();
                break;
            case 'last_30_days':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                $endDate   = Carbon::now()->endOfDay();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate   = Carbon::now()->endOfMonth();
                break;
            case 'year_to_date':
                $startDate = Carbon::now()->startOfYear();
                $endDate   = Carbon::now()->endOfDay();
                break;
            case 'yearly':
                $startDate = Carbon::now()->startOfYear();
                $endDate   = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $startDate = $start ? Carbon::parse($start)->startOfDay() : null;
                $endDate   = $end ? Carbon::parse($end)->endOfDay() : null;
                break;
            case 'all':
            default:
                $startDate = null;
                $endDate   = null;
                break;
        }

        return [$startDate, $endDate];
    }
}
