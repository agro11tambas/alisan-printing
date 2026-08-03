<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\FinancialReport;
use App\Models\OrderItem;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class FinancialStatementController extends Controller
{
    public function profitLoss(Request $request)
    {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        // [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit] = $this->calculatePL($start, $end);

        // return view('erp.pages.financial-statement.profit-loss', compact(
        //     'netRevenue',
        //     'cogs',
        //     'saleReturn',
        //     'grossProfit',
        //     'expenses',
        //     'netProfit'
        // ));

        [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit, $cogsFixed, $grossProfitFixed, $netProfitFixed] = $this->calculatePL($start, $end);

        return view('erp.pages.financial-statement.profit-loss', compact(
            'netRevenue',
            'cogs',
            'saleReturn',
            'grossProfit',
            'expenses',
            'netProfit',
            'cogsFixed',
            'grossProfitFixed',
            'netProfitFixed'
        ));
    }

    public function profitLossSummary(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $start  = $request->input('start_date');
        $end    = $request->input('end_date');

        [$start, $end] = $this->getDateRange($filter, $start, $end);

        // [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit] = $this->calculatePL($start, $end);

        // return response()->json([
        //     'netRevenue'  => $netRevenue,
        //     'cogs'        => $cogs,
        //     'saleReturn'  => $saleReturn,
        //     'grossProfit' => $grossProfit,
        //     'expenses'    => $expenses,
        //     'netProfit'   => $netProfit,
        // ]);

        [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit, $cogsFixed, $grossProfitFixed, $netProfitFixed] = $this->calculatePL($start, $end);

        return response()->json([
            'netRevenue'           => $netRevenue,
            'cogs'                 => $cogs,
            'saleReturn'           => $saleReturn,
            'grossProfit'          => $grossProfit,
            'expenses'             => $expenses,
            'netProfit'            => $netProfit,
            // 🔹 versi fixed cost
            'cogsFixed'            => $cogsFixed,
            'grossProfitFixed'     => $grossProfitFixed,
            'netProfitFixed'       => $netProfitFixed,
        ]);
    }

    public function profitLossDailyView()
    {
        return view('erp.pages.financial-statement.profit-loss-daily');
    }

    public function profitLossDaily(Request $request)
    {
        $filter = $request->input('filter', 'this_month');
        $start  = $request->input('start_date');
        $end    = $request->input('end_date');

        [$start, $end] = $this->getDateRange($filter, $start, $end);

        $reports = FinancialReport::whereBetween('date', [$start, $end])
            ->selectRaw('DATE(date) AS report_date')
            ->selectRaw('SUM(revenue) AS revenue, SUM(cogs) AS cogs, SUM(cogs_fixed_cost) AS cogs_fixed')
            ->selectRaw('SUM(gross_profit) AS gross_profit, SUM(gross_profit_at_fixed_cost) AS gross_profit_fixed')
            ->selectRaw('SUM(expense) AS expenses, SUM(net_profit) AS net_profit, SUM(net_profit_at_fixed_cost) AS net_profit_fixed')
            ->groupByRaw('DATE(date)')
            ->orderBy('report_date')
            ->get()
            ->map(fn($report) => [
                'date' => $report->report_date,
                'revenue' => (float) $report->revenue,
                'cogs' => (float) $report->cogs,
                'cogs_fixed' => (float) $report->cogs_fixed,
                'grossProfit' => (float) $report->gross_profit,
                'grossProfitFixed' => (float) $report->gross_profit_fixed,
                'expenses' => (float) $report->expenses,
                'netProfit' => (float) $report->net_profit,
                'netProfitFixed' => (float) $report->net_profit_fixed,
            ]);

        // 🔹 Pagination (lazy-load)
        $length = (int) $request->input('length', 15);
        $startIndex = (int) $request->input('start', 0);
        $paginated = $reports->slice($startIndex, $length)->values();
        $hasMore = $reports->count() > ($startIndex + $length);

        // 🔹 Total summary tetap utuh
        $totalSummary = [
            'total_revenue'       => $reports->sum('revenue'),
            'total_cogs'          => $reports->sum('cogs'),
            'total_cogs_fixed'    => $reports->sum('cogs_fixed'),
            'total_gross'         => $reports->sum('grossProfit'),
            'total_gross_fixed'   => $reports->sum('grossProfitFixed'),
            'total_expense'       => $reports->sum('expenses'),
            'total_net'           => $reports->sum('netProfit'),
            'total_net_fixed'     => $reports->sum('netProfitFixed'),
        ];

        // ✅ Return JSON format lazy-load
        return response()->json([
            'data' => $paginated,
            'summary' => $totalSummary,
            'has_more' => $hasMore,
            'recordsTotal' => $reports->count(),
            'recordsFiltered' => $reports->count(),
        ]);
    }

    // private function calculatePL($start, $end)
    // {
    //     $reports = \App\Models\FinancialReport::whereBetween('date', [$start, $end]);

    //     $totalRevenue = (clone $reports)->whereNotIn('transaction_type', ['sale_return'])->sum('revenue');
    //     $totalCogs    = (clone $reports)->sum('cogs');
    //     $totalExpense = (clone $reports)->sum('expense');
    //     $saleReturn   = (clone $reports)->where('transaction_type', 'sale_return')->sum('revenue');

    //     $grossProfit  = $totalRevenue - $totalCogs - abs($saleReturn);
    //     $netProfit    = $grossProfit - $totalExpense;

    //     return [
    //         $totalRevenue,      // Net Revenue
    //         $totalCogs,         // COGS
    //         abs($saleReturn),   // Sale Return (positif untuk tampilan)
    //         $grossProfit,       // Gross Profit
    //         $totalExpense,      // Expenses
    //         $netProfit          // Net Profit
    //     ];
    // }

    private function calculatePL($start, $end)
    {
        $totals = FinancialReport::whereBetween('date', [$start, $end])
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type <> 'sale_return' OR transaction_type IS NULL THEN revenue ELSE 0 END), 0) AS revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'sale_return' THEN revenue ELSE 0 END), 0) AS sale_return")
            ->selectRaw('COALESCE(SUM(cogs), 0) AS cogs, COALESCE(SUM(cogs_fixed_cost), 0) AS cogs_fixed')
            ->selectRaw('COALESCE(SUM(expense), 0) AS expense')
            ->first();

        // 🔹 Ambil total untuk versi avg cost
        $totalRevenue = (float) $totals->revenue;
        $totalCogs = (float) $totals->cogs;
        $totalExpense = (float) $totals->expense;
        $saleReturn = (float) $totals->sale_return;

        // 🔹 Hitungan normal (avg cost)
        $grossProfit  = $totalRevenue - $totalCogs - abs($saleReturn);
        $netProfit    = $grossProfit - $totalExpense;

        // 🔹 Tambahkan hitungan versi Fixed Cost
        $totalCogsFixed = (float) $totals->cogs_fixed;
        $grossProfitFixed = $totalRevenue - $totalCogsFixed - abs($saleReturn);
        $netProfitFixed   = $grossProfitFixed - $totalExpense;

        // Kembalikan dua versi hasil
        return [
            $totalRevenue,        // Net Revenue
            $totalCogs,           // COGS (avg)
            abs($saleReturn),     // Sale Return
            $grossProfit,         // Gross Profit (avg)
            $totalExpense,        // Expense
            $netProfit,           // Net Profit (avg)
            $totalCogsFixed,      // COGS (fixed)
            $grossProfitFixed,    // Gross Profit (fixed)
            $netProfitFixed,      // Net Profit (fixed)
        ];
    }

    private function getDateRange($filter, $start = null, $end = null)
    {
        switch ($filter) {
            case 'today':
                return [now()->toDateString(), now()->toDateString()];
            case 'last_7_days':
                return [now()->subDays(7)->toDateString(), now()->toDateString()];
            case 'this_month':
                return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
            case 'last_30_days':
                return [now()->subDays(30)->toDateString(), now()->toDateString()];
            case 'year_to_date':
                return [now()->startOfYear()->toDateString(), now()->toDateString()];
            case 'yearly':
                return [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()];
            case 'custom':
                return [$start, $end];
            case 'all':
            default:
                return ['1970-01-01', now()->toDateString()];
        }
    }
}
