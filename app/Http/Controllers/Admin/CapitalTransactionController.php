<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountTransaction;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Account;
use Illuminate\Support\Str;


class CapitalTransactionController extends Controller
{
    public function index()
    {
        return view('erp.pages.capital-transactions.index');
    }

    public function dataCapitalTransaction(Request $request)
    {
        $capitalTransactions = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Capital');
            });

        if ($request->filter) {
            switch ($request->filter) {
                case 'today':
                    $capitalTransactions->whereDate('transaction_date', Carbon::today());
                    break;
                case 'last_7_days':
                    $capitalTransactions->whereBetween('transaction_date', [Carbon::now()->subDays(7), Carbon::now()]);
                    break;
                case 'this_month':
                    $capitalTransactions->whereMonth('transaction_date', Carbon::now()->month)
                        ->whereYear('transaction_date', Carbon::now()->year);
                    break;
                case 'last_30_days':
                    $capitalTransactions->whereBetween('transaction_date', [Carbon::now()->subDays(30), Carbon::now()]);
                    break;
                case 'year_to_date':
                    $capitalTransactions->whereBetween('transaction_date', [Carbon::now()->startOfYear(), Carbon::now()]);
                    break;
                case 'yearly':
                    $capitalTransactions->whereYear('transaction_date', Carbon::now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $capitalTransactions->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
                    }
                    break;
                default:
                    // all time -> no filter
                    break;
            }
        }

        return DataTables::of($capitalTransactions)
            ->addIndexColumn()
            ->addColumn('type', function ($capitalTransactions) {
                return $capitalTransactions->account->type;
            })
            ->addColumn('transaction_date', function ($capitalTransactions) {
                return $capitalTransactions->transaction_date;
            })
            ->addColumn('credit', function ($capitalTransactions) {
                return 'Rp ' . number_format($capitalTransactions->credit, 0, ',', '.');
            })
            ->addColumn('note', function ($capitalTransactions) {
                return $capitalTransactions->note;
            })
            ->addColumn('action', function ($capitalTransactions) {
                return view('erp.pages.capital-transactions.partials.action-button', compact('capitalTransactions'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $transactionTypes = Account::where('name', 'Capital')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        return view('erp.pages.capital-transactions.create-capital-transaction', compact('transactionTypes', 'cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|exists:accounts,id',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'credit' => 'required|numeric|min:1',
            'note' => 'nullable',
            'particular' => 'nullable',
        ]);

        $amount   = $request->credit;
        $groupId  = Str::uuid();

        $capitalAccount   = Account::findOrFail($request->transaction_type);
        $cashBankAccount  = Account::findOrFail($request->cash_bank_account_id);

        $capitalParticular = $cashBankAccount->name . ' - ' . $cashBankAccount->type;
        $sourceParticular  = $capitalAccount->name . ' - ' . $capitalAccount->type;

        // cek nama transaction type (misal: "Owner Contribution" atau "Withdraw Money")
        if ($capitalAccount->type === 'Owner Contribution') {
            // Capital = credit, CashBank = debit
            AccountTransaction::create([
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $capitalAccount->id,
                'debit'                 => 0,
                'credit'                => $amount,
                'note'                  => $request->note,
                'particular'            => $capitalParticular,
                'transaction_group_id'  => $groupId,
            ]);

            AccountTransaction::create([
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => $amount,
                'credit'                => 0,
                'note'                  => $request->note,
                'particular'            => $sourceParticular,
                'transaction_group_id'  => $groupId,
            ]);
        } elseif ($capitalAccount->type === 'Withdraw Money') {
            // Capital = debit, CashBank = credit
            AccountTransaction::create([
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $capitalAccount->id,
                'debit'                 => $amount,
                'credit'                => 0,
                'note'                  => $request->note,
                'particular'            => $capitalParticular,
                'transaction_group_id'  => $groupId,
            ]);

            AccountTransaction::create([
                'transaction_date'      => $request->transaction_date,
                'account_id'            => $cashBankAccount->id,
                'debit'                 => 0,
                'credit'                => $amount,
                'note'                  => $request->note,
                'particular'            => $sourceParticular,
                'transaction_group_id'  => $groupId,
            ]);
        }

        $capitalAccount->accountClosingBalance();
        $cashBankAccount->accountClosingBalance();

        return redirect('/erp/capital-transactions')->with('success', 'Capital Transaction created successfully');
    }

    public function delete($id)
    {
        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/capital-transactions')->with('error', 'Data tidak ditemukan');
        }

        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        return redirect('/erp/capital-transactions')->with('success', 'Capital Transaction deleted successfully');
    }

    public function edit($id)
    {
        $cashAccounts = Account::where('name', 'Cash')->get();
        $bankAccounts = Account::where('name', 'Bank')->get();

        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/capital-transactions')->with('error', 'Data tidak ditemukan');
        }

        $debitTransaction = $transactions->first(fn($trx) => (float)$trx->debit > 0);
        $creditTransaction = $transactions->first(fn($trx) => (float)$trx->credit > 0);

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/capital-transactions')->with('error', 'Data tidak lengkap');
        }

        $capitalTransaction = $creditTransaction;
        $accounts = Account::all();
        $transactionTypes = Account::where('name', 'Capital')->get();

        return view('erp.pages.capital-transactions.edit-capital-transaction', compact(
            'capitalTransaction',
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
            'transaction_type' => 'required|exists:accounts,id',
            'cash_bank_account_id' => 'required|exists:accounts,id',
            'credit' => 'required|numeric|min:0',
            'note' => 'nullable',
        ]);

        $transactions = AccountTransaction::where('transaction_group_id', $id)->get();

        if ($transactions->isEmpty()) {
            return redirect('/erp/capital-transactions')->with('error', 'Transaction not found.');
        }

        $debitTransaction = $transactions->first(fn($trx) => (float) $trx->debit > 0);
        $creditTransaction = $transactions->first(fn($trx) => (float) $trx->credit > 0);

        if (!$debitTransaction || !$creditTransaction) {
            return redirect('/erp/capital-transactions')->with('error', 'Incomplete transaction data.');
        }

        $amount            = $request->credit;
        $capitalAccount    = Account::findOrFail($request->transaction_type);
        $cashBankAccount   = Account::findOrFail($request->cash_bank_account_id);

        $capitalParticular = $cashBankAccount->name . ' - ' . $cashBankAccount->type;
        $sourceParticular  = $capitalAccount->name . ' - ' . $capitalAccount->type;

        if ($capitalAccount->type === 'Owner Contribution') {
            // Capital = credit, CashBank = debit
            $creditTransaction->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $capitalAccount->id,
                'debit'            => 0,
                'credit'           => $amount,
                'note'             => $request->note,
                'particular'       => $capitalParticular,
            ]);

            $debitTransaction->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => $amount,
                'credit'           => 0,
                'note'             => $request->note,
                'particular'       => $sourceParticular,
            ]);
        } elseif ($capitalAccount->type === 'Withdraw Money') {
            // Capital = debit, CashBank = credit
            $debitTransaction->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $capitalAccount->id,
                'debit'            => $amount,
                'credit'           => 0,
                'note'             => $request->note,
                'particular'       => $capitalParticular,
            ]);

            $creditTransaction->update([
                'transaction_date' => $request->transaction_date,
                'account_id'       => $cashBankAccount->id,
                'debit'            => 0,
                'credit'           => $amount,
                'note'             => $request->note,
                'particular'       => $sourceParticular,
            ]);
        }

        // Update saldo
        $capitalAccount->accountClosingBalance();
        $cashBankAccount->accountClosingBalance();

        return redirect('/erp/capital-transactions')->with('success', 'Capital Transaction updated successfully');
    }
}
