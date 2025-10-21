<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountTransaction;
use App\Models\Account;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AccountListController extends Controller
{
    private function applyFilter(Request $request, $accountId)
    {
        $filter = $request->get('filter', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = AccountTransaction::where('account_id', $accountId);

        switch ($filter) {
            case 'today':
                $query->whereDate('transaction_date', Carbon::today());
                break;

            case 'last_7_days':
                $query->whereBetween('transaction_date', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()]);
                break;

            case 'this_month':
                $query->whereMonth('transaction_date', Carbon::now()->month)
                    ->whereYear('transaction_date', Carbon::now()->year);
                break;

            case 'last_30_days':
                $query->whereBetween('transaction_date', [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()]);
                break;

            case 'year_to_date':
                $query->whereBetween('transaction_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                break;

            case 'yearly':
                $query->whereYear('transaction_date', Carbon::now()->year);
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    $query->whereBetween('transaction_date', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                }
                break;
        }

        return $query;
    }


    public function dataExpense(Request $request)
    {
        $expense = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Expense');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $expense->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $expense->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $expense->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $expense->orderBy('transaction_date', 'desc');

        return DataTables::of($expense)
            ->addIndexColumn()
            ->addColumn('account_type', function ($expense) {
                return $expense->account->type ?? '-';
            })
            ->addColumn('particular', function ($expense) {
                return $expense->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($expense) {
                return \Carbon\Carbon::parse($expense->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($expense) {
                return 'Rp ' . number_format($expense->debit);
            })
            ->addColumn('credit', function ($expense) {
                return 'Rp ' . number_format($expense->credit);
            })
            ->addColumn('note', function ($expense) {
                return $expense->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getExpense()
    {
        $accountTypes = Account::where('name', 'Expense')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.expense', compact('accountTypes'));
    }

    public function dataBank(Request $request)
    {
        $bank = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Bank');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $bank->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $bank->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $bank->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $bank->orderBy('transaction_date', 'desc');

        return DataTables::of($bank)
            ->addIndexColumn()
            ->addColumn('account_type', function ($bank) {
                return $bank->account->type ?? '-';
            })
            ->addColumn('particular', function ($bank) {
                return $bank->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($bank) {
                return \Carbon\Carbon::parse($bank->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($bank) {
                return 'Rp ' . number_format($bank->debit);
            })
            ->addColumn('credit', function ($bank) {
                return 'Rp ' . number_format($bank->credit);
            })
            ->addColumn('note', function ($bank) {
                return $bank->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getBank()
    {
        $accountTypes = Account::where('name', 'Bank')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.bank', compact('accountTypes'));
    }

    public function dataCash(Request $request)
    {
        $cash = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Cash');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $cash->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $cash->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $cash->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $cash->orderBy('transaction_date', 'desc');

        return DataTables::of($cash)
            ->addIndexColumn()
            ->addColumn('account_type', function ($cash) {
                return $cash->account->type ?? '-';
            })
            ->addColumn('particular', function ($cash) {
                return $cash->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($cash) {
                return \Carbon\Carbon::parse($cash->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($cash) {
                return 'Rp ' . number_format($cash->debit);
            })
            ->addColumn('credit', function ($cash) {
                return 'Rp ' . number_format($cash->credit);
            })
            ->addColumn('note', function ($cash) {
                return $cash->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getCash()
    {
        $accountTypes = Account::where('name', 'Cash')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.cash', compact('accountTypes'));
    }

    public function dataSale(Request $request)
    {
        $sale = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Sale');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $sale->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $sale->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $sale->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $sale->orderBy('transaction_date', 'desc');

        return DataTables::of($sale)
            ->addIndexColumn()
            ->addColumn('account_type', function ($sale) {
                return $sale->account->type ?? '-';
            })
            ->addColumn('particular', function ($sale) {
                return $sale->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($sale) {
                return \Carbon\Carbon::parse($sale->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($sale) {
                return 'Rp ' . number_format($sale->debit);
            })
            ->addColumn('credit', function ($sale) {
                return 'Rp ' . number_format($sale->credit);
            })
            ->addColumn('note', function ($sale) {
                return $sale->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getSale()
    {
        $accountTypes = Account::where('name', 'Sale')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.sale', compact('accountTypes'));
    }

    public function dataPurchase(Request $request)
    {
        $purchase = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Purchase');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchase->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $purchase->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $purchase->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $purchase->orderBy('transaction_date', 'desc');

        return DataTables::of($purchase)
            ->addIndexColumn()
            ->addColumn('account_type', function ($purchase) {
                return $purchase->account->type ?? '-';
            })
            ->addColumn('particular', function ($purchase) {
                return $purchase->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($purchase) {
                return \Carbon\Carbon::parse($purchase->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($purchase) {
                return 'Rp ' . number_format($purchase->debit);
            })
            ->addColumn('credit', function ($purchase) {
                return 'Rp ' . number_format($purchase->credit);
            })
            ->addColumn('note', function ($purchase) {
                return $purchase->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getPurchase()
    {
        $accountTypes = Account::where('name', 'Purchase')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.purchase', compact('accountTypes'));
    }

    public function dataCapital(Request $request)
    {
        $capital = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Capital');
            });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $capital->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search_particular')) {
            $capital->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        if ($request->filled('search_account_type')) {
            $capital->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        $capital->orderBy('transaction_date', 'desc');

        return DataTables::of($capital)
            ->addIndexColumn()
            ->addColumn('account_type', function ($capital) {
                return $capital->account->type ?? '-';
            })
            ->addColumn('particular', function ($capital) {
                return $capital->particular ?? '-';
            })
            ->addColumn('transaction_date', function ($capital) {
                return \Carbon\Carbon::parse($capital->transaction_date)->format('d F Y');
            })
            ->addColumn('debit', function ($capital) {
                return 'Rp ' . number_format($capital->debit);
            })
            ->addColumn('credit', function ($capital) {
                return 'Rp ' . number_format($capital->credit);
            })
            ->addColumn('note', function ($capital) {
                return $capital->note ?? '-';
            })
            ->rawColumns(['note'])
            ->make(true);
    }

    public function getCapital()
    {
        $accountTypes = Account::where('name', 'Capital')
        ->select('type')
        ->distinct()
        ->pluck('type');

        return view('erp.pages.account-list.capital', compact('accountTypes'));
    }
}
