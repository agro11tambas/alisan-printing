<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Expense;
use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index()
    {
        return view('erp.pages.expenses.index');
    }

    public function dataExpense(Request $request)
    {
        $expense = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Expense');
            });

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $expense->whereDate('transaction_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $expense->whereBetween('transaction_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $expense->whereMonth('transaction_date', Carbon::now()->month)
                        ->whereYear('transaction_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $expense->whereBetween('transaction_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $expense->whereBetween('transaction_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $expense->whereYear('transaction_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $expense->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        return DataTables::of($expense)
            ->addIndexColumn()
            ->addColumn('type', function ($expense) {
                return $expense->account->type;
            })
            ->addColumn('transaction_date', function ($expense) {
                return $expense->transaction_date;
            })
            ->addColumn('debit', function ($expense) {
                return 'Rp ' . number_format($expense->debit, 0, ',', '.');
            })
            ->addColumn('note', function ($expense) {
                return $expense->note;
            })
            ->addColumn('action', function ($expense) {
                return view('erp.pages.expenses.partials.action-button', compact('expense'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $transactionTypes = Account::where('name', 'Expense')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.expenses.create-expense', compact('transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required',
            'transaction_type' => 'required|exists:accounts,id',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'debit' => 'required',
            'note' => 'nullable',
            'particular' => 'nullable',
        ]);

        $amount = $request->debit;
        $groupId = Str::uuid();

        $expenseAccount = Account::findOrFail($request->transaction_type);
        $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);

        $expenseParticular = $cashBankAccount->name . ' - ' . $cashBankAccount->type;
        $sourceParticular = $expenseAccount->name . ' - ' . $expenseAccount->type;

        AccountTransaction::create([
            'transaction_date' => $request->transaction_date,
            'account_id' => $request->transaction_type,
            'debit' => $amount,
            'credit' => 0,
            'note' => $request->note,
            'particular' => $expenseParticular,
            'transaction_group_id' => $groupId,
        ]);

        AccountTransaction::create([
            'transaction_date' => $request->transaction_date,
            'account_id' => $request->cash_bank_account_id,
            'debit' => 0,
            'credit' => $amount,
            'note' => $request->note,
            'particular' => $sourceParticular,
            'transaction_group_id' => $groupId,
        ]);

        $expenseAccount->accountClosingBalance();
        $cashBankAccount->accountClosingBalance();

        return redirect('/erp/expenses')->with('success', 'Expense created successfully');
    }

    public function delete($id)
    {
        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/expenses')->with('error', 'Data tidak ditemukan');
        }

        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        return redirect('/erp/expenses')->with('success', 'Expense deleted successfully');
    }

    public function edit($id)
    {
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/expenses')->with('error', 'Data tidak ditemukan');
        }

        $debitTransaction = $transactions->first(fn($trx) => (float)$trx->debit > 0);
        $creditTransaction = $transactions->first(fn($trx) => (float)$trx->credit > 0);

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/expenses')->with('error', 'Data tidak lengkap');
        }

        $expense = $debitTransaction;
        $accounts = Account::all();
        $transactionTypes = Account::where('name', 'Expense')->get();

        return view('erp.pages.expenses.edit-expense', compact(
            'expense',
            'debitTransaction',
            'creditTransaction',
            'accounts',
            'transactionTypes',
            'cashAccounts',
            'bankAccounts'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id', // expense account
            'cash_bank_account_id' => 'required|exists:accounts,id', // bank / cash
            'debit' => 'required|numeric|min:0',
            'note' => 'nullable',
        ]);

        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/expenses')->with('error', 'Transaction not found.');
        }

        // Pastikan ambil transaksi debit dan kredit dengan aman
        $debitTransaction = $transactions->first(fn($trx) => (float) $trx->debit > 0);
        $creditTransaction = $transactions->first(fn($trx) => (float) $trx->credit > 0);

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/expenses')->with('error', 'Incomplete transaction data.');
        }

        $amount = $request->debit;
        $expenseAccount = Account::findOrFail($request->transaction_type);     // Misal: Biaya Listrik
        $cashBankAccount = Account::findOrFail($request->cash_bank_account_id);     // Misal: Bank

        $expenseParticular = $cashBankAccount->name . ' - ' . $cashBankAccount->type;
        $sourceParticular = $expenseAccount->name . ' - ' . $expenseAccount->type;

        // Update transaksi debit (biaya)
        $debitTransaction->update([
            'transaction_date' => $request->transaction_date,
            'account_id' => $expenseAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'note' => $request->note,
            'particular' => $expenseParticular,
        ]);

        // Update transaksi kredit (bank/cash)
        $creditTransaction->update([
            'transaction_date' => $request->transaction_date,
            'account_id' => $cashBankAccount->id,
            'debit' => 0,
            'credit' => $amount,
            'note' => $request->note,
            'particular' => $sourceParticular,
        ]);

        // Update saldo
        $expenseAccount->accountClosingBalance();
        $cashBankAccount->accountClosingBalance();

        return redirect('/erp/expenses')->with('success', 'Expense updated successfully');
    }
}
