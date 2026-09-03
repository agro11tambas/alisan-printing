<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
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
        $filter = strtolower(trim($request->get('filter', 'this_month')));
        $start  = $request->get('start_date');
        $end    = $request->get('end_date');

        [$startDate, $endDate] = $this->getDateRange($filter, $start, $end);

        $docColumn        = 'status';
        $SALE_ORDER       = 'Sale Order';
        $SALE_LIST        = 'Sale List';
        $PURCHASE_ORDER   = 'Purchase Orders';
        $PURCHASE_LIST    = 'Purchase List';

        $accounts = Account::all();
        $transactionTotals = collect();

        if ($filter !== 'all') {
            $transactionQuery = AccountTransaction::query();
            if ($startDate && $endDate) {
                $transactionQuery->whereBetween('transaction_date', [$startDate, $endDate]);
            }

            $transactionTotals = $transactionQuery
                ->selectRaw('account_id, SUM(debit) AS debit, SUM(credit) AS credit')
                ->groupBy('account_id')
                ->get()
                ->keyBy('account_id');
        }

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
            $transactionTotal = $transactionTotals->get($acc->id);
            $debit = (float) ($transactionTotal?->debit ?? 0);
            $credit = (float) ($transactionTotal?->credit ?? 0);

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

        $saleSummary = $saleBase->selectRaw(
            "COALESCE(SUM(CASE WHEN status = 'Sale Order' THEN grand_total ELSE 0 END), 0) AS total_sale_order,
            SUM(CASE WHEN status = 'Sale Order' THEN 1 ELSE 0 END) AS count_sale_order,
            COALESCE(SUM(CASE WHEN status = 'Sale List' THEN grand_total ELSE 0 END), 0) AS total_sale_list,
            SUM(CASE WHEN status = 'Sale List' THEN 1 ELSE 0 END) AS count_sale_list,
            SUM(CASE WHEN status = 'Sale List' AND payment_status = 'Paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = 'Sale List' AND payment_status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
            SUM(CASE WHEN status = 'Sale List' AND payment_status = 'Overdue' THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN status = 'Sale List' AND payment_status = 'Partially Paid' THEN 1 ELSE 0 END) AS partially_paid_count,
            COALESCE(SUM(CASE WHEN status = 'Sale List' THEN paid_amount ELSE 0 END), 0) AS received_total"
        )->first();

        $response['totalSaleOrder'] = (float) $saleSummary->total_sale_order;
        $response['countSaleOrder'] = (int) $saleSummary->count_sale_order;
        $response['totalSaleList'] = (float) $saleSummary->total_sale_list;
        $response['countSaleList'] = (int) $saleSummary->count_sale_list;
        $response['saleListStatus'] = [
            'paid' => (int) $saleSummary->paid_count,
            'unpaid' => (int) $saleSummary->unpaid_count,
            'overdue' => (int) $saleSummary->overdue_count,
            'partially_paid' => (int) $saleSummary->partially_paid_count,
        ];
        $response['receivedFromCustomer'] = (float) $saleSummary->received_total;
        $response['receivableFromCustomer'] = (float) $saleSummary->total_sale_list - (float) $saleSummary->received_total;

        $purchaseBase = Purchase::query();
        if ($startDate && $endDate) {
            $purchaseBase->whereBetween('purchase_date', [$startDate, $endDate]);
        }

        $purchaseSummary = $purchaseBase->selectRaw(
            "COALESCE(SUM(CASE WHEN status = 'Purchase Orders' THEN total_amount ELSE 0 END), 0) AS total_purchase_order,
            SUM(CASE WHEN status = 'Purchase Orders' THEN 1 ELSE 0 END) AS count_purchase_order,
            COALESCE(SUM(CASE WHEN status = 'Purchase List' THEN total_amount ELSE 0 END), 0) AS total_purchase_list,
            SUM(CASE WHEN status = 'Purchase List' THEN 1 ELSE 0 END) AS count_purchase_list,
            SUM(CASE WHEN status = 'Purchase List' AND payment_status = 'Paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = 'Purchase List' AND payment_status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
            SUM(CASE WHEN status = 'Purchase List' AND payment_status = 'Overdue' THEN 1 ELSE 0 END) AS overdue_count,
            SUM(CASE WHEN status = 'Purchase List' AND payment_status = 'Partially Paid' THEN 1 ELSE 0 END) AS partially_paid_count,
            COALESCE(SUM(CASE WHEN status = 'Purchase List' THEN COALESCE(paid_amount_product, 0) + COALESCE(paid_amount_freight, 0) ELSE 0 END), 0) AS paid_total"
        )->first();

        $response['totalPurchaseOrder'] = (float) $purchaseSummary->total_purchase_order;
        $response['countPurchaseOrder'] = (int) $purchaseSummary->count_purchase_order;
        $response['totalPurchaseList'] = (float) $purchaseSummary->total_purchase_list;
        $response['countPurchaseList'] = (int) $purchaseSummary->count_purchase_list;
        $response['purchaseListStatus'] = [
            'paid' => (int) $purchaseSummary->paid_count,
            'unpaid' => (int) $purchaseSummary->unpaid_count,
            'overdue' => (int) $purchaseSummary->overdue_count,
            'partially_paid' => (int) $purchaseSummary->partially_paid_count,
        ];
        $response['paidToSupplier'] = (float) $purchaseSummary->paid_total;
        $response['payableToSupplier'] = (float) $purchaseSummary->total_purchase_list - (float) $purchaseSummary->paid_total;

        $response['grossProfit'] = $response['totalSaleList'] - $response['totalPurchaseList'];
        $response['netProfit']   = $response['grossProfit'] - $response['totalExpenseAccount'];

        // Harga modal per produk diambil dari sisa batch FIFO, bukan rata-rata
        // bergerak: stok yang tersisa dinilai dengan harga pembelian yang
        // benar-benar masih menempel padanya.
        $fifoCost = DB::table('cost_layers')
            ->select('product_id', DB::raw('SUM(qty_remaining * unit_cost) / NULLIF(SUM(qty_remaining), 0) AS unit_cost'))
            ->groupBy('product_id');

        $inventoryValue = InventoryStock::join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->leftJoinSub($fifoCost, 'fifo', 'fifo.product_id', '=', 'inventory_stocks.product_id')
            ->whereNull('products.deleted_at') // kalau soft delete
            ->sum(DB::raw('inventory_stocks.inventory_stock * COALESCE(fifo.unit_cost, products.avg_cost)'));

        $productionValue = ProductionStock::join('products', 'products.id', '=', 'production_stocks.product_id')
            ->leftJoinSub($fifoCost, 'fifo', 'fifo.product_id', '=', 'production_stocks.product_id')
            ->whereNull('products.deleted_at')
            ->sum(DB::raw('production_stocks.available_quantity * COALESCE(fifo.unit_cost, products.avg_cost)'));

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
                // 🔥 all time = tanpa filter tanggal
                $startDate = null;
                $endDate   = null;
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate   = Carbon::now()->endOfMonth();
                break;
        }

        return [$startDate, $endDate];
    }
}
