<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\OrderItem;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class FinancialStatementController extends Controller
{
    // ✅ Tampilan awal (summary)
    public function profitLoss(Request $request)
    {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit] = $this->calculatePL($start, $end);

        return view('erp.pages.financial-statement.profit-loss', compact(
            'netRevenue',
            'cogs',
            'saleReturn',
            'grossProfit',
            'expenses',
            'netProfit'
        ));
    }

    // ✅ Summary Ajax
    public function profitLossSummary(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $start  = $request->input('start_date');
        $end    = $request->input('end_date');

        [$start, $end] = $this->getDateRange($filter, $start, $end);
        [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit] = $this->calculatePL($start, $end);

        return response()->json([
            'netRevenue'  => $netRevenue,
            'cogs'        => $cogs,
            'saleReturn'  => $saleReturn,
            'grossProfit' => $grossProfit,
            'expenses'    => $expenses,
            'netProfit'   => $netProfit,
        ]);
    }

    // ✅ View Daily Breakdown
    public function profitLossDailyView()
    {
        return view('erp.pages.financial-statement.profit-loss-daily');
    }

    // ✅ Ajax Daily Breakdown
    public function profitLossDaily(Request $request)
    {
        $filter = $request->input('filter', 'this_month');
        $start  = $request->input('start_date');
        $end    = $request->input('end_date');

        [$start, $end] = $this->getDateRange($filter, $start, $end);

        // ambil semua transaksi per hari
        $sales = AccountTransaction::selectRaw('DATE(transaction_date) as day, SUM(credit) as revenue')
            ->whereHas('account', fn($q) => $q->where('name', 'Sale'))
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $returns = SaleReturn::selectRaw('DATE(return_date) as day, SUM(total_amount) as total')
            ->whereBetween('return_date', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $cogs = OrderItem::whereHas('order', function ($q) use ($start, $end) {
            $q->whereBetween('order_date', [$start, $end])
                ->where('status', 'Sale List');
        })
            ->with(['product.inventoryStock', 'productBundle.items.product.inventoryStock'])
            ->get()
            ->groupBy(fn($item) => $item->order->order_date->toDateString())
            ->map(function ($group) {
                return $group->sum(function ($item) {
                    if ($item->product_id && !$item->product_bundle_id) {
                        // Produk biasa
                        $cost = $item->product->avg_cost
                            ?? $item->product->inventoryStock->avg_cost
                            ?? 0;
                        return $item->quantity * $cost;
                    } elseif ($item->product_bundle_id) {
                        // jumlahkan avg_cost semua product di bundle
                        $bundleCost = $item->productBundle->items->sum(function ($bundleItem) {
                            $cost = $bundleItem->product->avg_cost
                                ?? $bundleItem->product->inventoryStock->avg_cost
                                ?? 0;
                            return $cost; // ❌ jangan dikali $bundleItem->quantity lagi
                        });

                        // kali dengan quantity dari order_items
                        return $item->quantity * $bundleCost;
                    }
                    return 0;
                });
            });

        $expenses = AccountTransaction::selectRaw('DATE(transaction_date) as day, SUM(debit) as total')
            ->whereHas('account', fn($q) => $q->where('name', 'Expense'))
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        // gabungkan semua tanggal unik
        $dates = collect($sales->keys())
            ->merge($returns->keys())
            ->merge($cogs->keys())
            ->merge($expenses->keys())
            ->unique()
            ->sort();

        $data = [];
        foreach ($dates as $day) {
            $revenue    = $sales[$day] ?? 0;
            $cog        = $cogs[$day] ?? 0;
            $saleReturn = $returns[$day] ?? 0;
            $gross      = $revenue - $cog - $saleReturn;
            $exp        = $expenses[$day] ?? 0;
            $net        = $gross - $exp;

            $data[] = [
                'date'        => $day,
                'revenue'     => $revenue,
                'cogs'        => $cog,
                'saleReturn'  => $saleReturn,
                'grossProfit' => $gross,
                'expenses'    => $exp,
                'netProfit'   => $net,
            ];
        }

        // ✅ format DataTables
        return response()->json([
            'draw' => intval($request->input('draw')),   // kiriman dari DataTables
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data,
        ]);
    }

    // ✅ Helper
    private function calculatePL($start, $end)
    {
        $salesCredit = AccountTransaction::whereHas('account', fn($q) => $q->where('name', 'Sale'))
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('credit');

        $salesDebit = AccountTransaction::whereHas('account', fn($q) => $q->where('name', 'Sale'))
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('debit');

        $netRevenue = $salesCredit;

        $saleReturn = SaleReturn::whereBetween('return_date', [$start, $end])->sum('total_amount') ?? 0;

        $cogs = OrderItem::whereHas('order', function ($q) use ($start, $end) {
            $q->whereBetween('order_date', [$start, $end])
                ->where('status', 'Sale List');
        })
            ->with(['product.inventoryStock', 'productBundle.items.product.inventoryStock'])
            ->get()
            ->sum(function ($item) {
                if ($item->product_id && !$item->product_bundle_id) {
                    // Produk biasa
                    $cost = $item->product->avg_cost
                        ?? $item->product->inventoryStock->avg_cost
                        ?? 0;
                    return $item->quantity * $cost;
                } elseif ($item->product_bundle_id) {
                    // jumlahkan avg_cost semua product di bundle
                    $bundleCost = $item->productBundle->items->sum(function ($bundleItem) {
                        $cost = $bundleItem->product->avg_cost
                            ?? $bundleItem->product->inventoryStock->avg_cost
                            ?? 0;
                        return $cost; // ❌ jangan dikali $bundleItem->quantity lagi
                    });

                    // kali dengan quantity dari order_items
                    return $item->quantity * $bundleCost;
                }
                return 0;
            });

        $grossProfit = $netRevenue - $cogs - $salesDebit;

        $expenses = AccountTransaction::whereHas('account', fn($q) => $q->where('name', 'Expense'))
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('debit');

        $netProfit = $grossProfit - $expenses;

        return [$netRevenue, $cogs, $saleReturn, $grossProfit, $expenses, $netProfit];
    }

    // ✅ Date Range Helper
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
