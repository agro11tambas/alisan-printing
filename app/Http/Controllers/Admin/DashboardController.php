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
use App\Models\InventoryStock;
use App\Models\ProductionStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        [$startDate, $endDate] = $this->getDateRange($filter, $start, $end);

        $docColumn        = 'status';
        $SALE_ORDER       = 'Sale Order';
        $SALE_LIST        = 'Sale List';
        $PURCHASE_ORDER   = 'Purchase Orders';
        $PURCHASE_LIST    = 'Purchase List';

        $accounts = Account::with(['transactions' => function ($q) use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $q->whereBetween('transaction_date', [$startDate, $endDate]);
            }
        }])->get();

        $response = [
            'totalSaleOrder' => 0,
            'countSaleOrder' => 0,
            'totalSaleList'  => 0,
            'countSaleList'  => 0,

            'totalPurchaseOrder' => 0,
            'countPurchaseOrder' => 0,
            'totalPurchaseList'  => 0,
            'countPurchaseList'  => 0,

            'receivedFromCustomer'   => 0,
            'receivableFromCustomer' => 0,
            'paidToSupplier'         => 0,
            'payableToSupplier'      => 0,

            'totalOpeningBalance'   => 0,
            'totalSaleAccount'      => 0,
            'totalPurchaseAccount'  => 0,
            'totalExpenseAccount'   => 0,
            'totalBankAccount'      => 0,
            'totalCashAccount'      => 0,
            'grossProfit'           => 0,
            'netProfit'             => 0,
            'totalByName'           => [],
            'breakdownByName'       => [],
        ];

        foreach ($accounts as $acc) {
            $debit  = $acc->transactions->sum('debit');
            $credit = $acc->transactions->sum('credit');

            $total = ($filter === 'all')
                ? ($acc->opening_balance ?? 0) + ($acc->closing_balance ?? 0)
                : ($acc->opening_balance ?? 0) + ($debit - $credit);

            $response['totalByName'][$acc->name] =
                ($response['totalByName'][$acc->name] ?? 0) + $total;

            $response['breakdownByName'][$acc->name][$acc->type] =
                ($response['breakdownByName'][$acc->name][$acc->type] ?? 0) + $total;

            if ($acc->name === 'Sale')     $response['totalSaleAccount']     += $total;
            if ($acc->name === 'Purchase') $response['totalPurchaseAccount'] += $total;
            if ($acc->name === 'Expense')  $response['totalExpenseAccount']  += $total;
            if ($acc->name === 'Bank')     $response['totalBankAccount']     += $total;
            if ($acc->name === 'Cash')     $response['totalCashAccount']     += $total;
        }

        $saleBase = Order::query();
        if ($startDate && $endDate) {
            $saleBase->whereBetween('order_date', [$startDate, $endDate]);
        }

        $saleOrderQ = (clone $saleBase)->where($docColumn, $SALE_ORDER);
        $response['totalSaleOrder'] = $saleOrderQ->sum('grand_total');
        $response['countSaleOrder'] = $saleOrderQ->count();

        $saleListQ = (clone $saleBase)->where($docColumn, $SALE_LIST);
        $response['totalSaleList'] = $saleListQ->sum('grand_total');
        $response['countSaleList'] = $saleListQ->count();

        $response['saleListStatus'] = [
            'paid'           => (clone $saleListQ)->where('payment_status', 'Paid')->count(),
            'unpaid'         => (clone $saleListQ)->where('payment_status', 'Unpaid')->count(),
            'overdue'        => (clone $saleListQ)->where('payment_status', 'Overdue')->count(),
            'partially_paid' => (clone $saleListQ)->where('payment_status', 'Partially Paid')->count(),
        ];

        $response['receivedFromCustomer']   = $saleListQ->sum('paid_amount');
        $response['receivableFromCustomer'] = $saleListQ->sum('grand_total') - $saleListQ->sum('paid_amount');

        $purchaseBase = Purchase::query();
        if ($startDate && $endDate) {
            $purchaseBase->whereBetween('purchase_date', [$startDate, $endDate]);
        }

        $purchaseOrderQ = (clone $purchaseBase)->where($docColumn, $PURCHASE_ORDER);
        $response['totalPurchaseOrder'] = $purchaseOrderQ->sum('total_amount');
        $response['countPurchaseOrder'] = $purchaseOrderQ->count();

        $purchaseListQ = (clone $purchaseBase)->where($docColumn, $PURCHASE_LIST);
        $response['totalPurchaseList'] = $purchaseListQ->sum('total_amount');
        $response['countPurchaseList'] = $purchaseListQ->count();

        $response['purchaseListStatus'] = [
            'paid'           => (clone $purchaseListQ)->where('payment_status', 'Paid')->count(),
            'unpaid'         => (clone $purchaseListQ)->where('payment_status', 'Unpaid')->count(),
            'overdue'        => (clone $purchaseListQ)->where('payment_status', 'Overdue')->count(),
            'partially_paid' => (clone $purchaseListQ)->where('payment_status', 'Partially Paid')->count(),
        ];

        $response['paidToSupplier'] = $purchaseListQ->sum(DB::raw('COALESCE(paid_amount_product,0) + COALESCE(paid_amount_freight,0)'));
        $response['payableToSupplier'] = $purchaseListQ->sum(DB::raw('total_amount - (COALESCE(paid_amount_product,0) + COALESCE(paid_amount_freight,0))'));

        $response['grossProfit'] = $response['totalSaleList'] - $response['totalPurchaseList'];
        $response['netProfit']   = $response['grossProfit'] - $response['totalExpenseAccount'];

        $inventoryValue = InventoryStock::join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->whereNull('products.deleted_at') // kalau soft delete
            ->sum(DB::raw('inventory_stocks.inventory_stock * products.avg_cost'));

        $productionValue = ProductionStock::join('products', 'products.id', '=', 'production_stocks.product_id')
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('production_stocks.available_quantity * products.avg_cost'));

        $response['inventoryValue'] = $inventoryValue;
        $response['productionValue'] = $productionValue;
        $response['totalInventoryValuation'] = $inventoryValue + $productionValue;

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
