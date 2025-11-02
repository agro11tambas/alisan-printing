<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Expense;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FinancialReport;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    public function index()
    {
        return view('erp.pages.expenses.index');
    }

    public function dataExpense(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $expense = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Expense');
            })
            ->orderByDesc('id');

        // 🔎 Filter tanggal
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

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $expense;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $expense->skip($start)->take($length)->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($expense) {
                // 🏷️ Type (Account Type)
                $type = e($expense->account->type ?? '-');

                // 📅 Transaction date
                $transactionDate = e($expense->transaction_date ?? '-');

                // 💰 Debit (Rupiah format)
                $debit = 'Rp ' . number_format($expense->debit ?? 0, 0, ',', '.');

                // 📝 Note
                $note = e($expense->note ?? '-');

                // ⚙️ Action buttons (from partial)
                $action = view('erp.pages.expenses.partials.action-button', compact('expense'))->render();

                return [
                    'id' => $expense->id,
                    'type' => $type,
                    'transaction_date' => $transactionDate,
                    'debit' => $debit,
                    'note' => $note,
                    'action' => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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

        // ================== CATAT FINANCIAL REPORT ==================
        try {
            $existingReport = FinancialReport::where('transaction_type', 'expense')
                ->where('reference_table', 'account_transactions')
                ->where('reference_id', function ($query) use ($groupId) {
                    $query->select('id')
                        ->from('account_transactions')
                        ->where('transaction_group_id', $groupId)
                        ->where('debit', '>', 0)
                        ->limit(1);
                })
                ->first();

            $transactionDate = Carbon::parse($request->transaction_date);
            $amount = (float) $amount;

            if ($existingReport) {
                // Update jika sebelumnya sudah ada laporan (misal edit pengeluaran)
                $existingReport->update([
                    'date'         => $transactionDate,
                    'expense'      => $amount,
                    'net_profit'   => DB::raw("net_profit - {$amount}"),
                    'notes'        => 'Auto-updated from Expense module',
                ]);
            } else {
                // Buat record baru untuk laporan keuangan
                FinancialReport::create([
                    'date'             => $transactionDate,
                    'transaction_type' => 'expense',
                    'reference_id'     => AccountTransaction::where('transaction_group_id', $groupId)
                        ->where('debit', '>', 0)
                        ->value('id'),
                    'reference_table'  => 'account_transactions',
                    'revenue'          => 0,
                    'cogs'             => 0,
                    'gross_profit'     => 0,
                    'expense'          => $amount,
                    'net_profit'       => 0 - $amount,
                    'notes'            => 'Auto-generated from Expense module',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mencatat laporan keuangan untuk Expense ID group ' . $groupId . ': ' . $e->getMessage());
        }

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

        // ================== UPDATE / CREATE FINANCIAL REPORT ==================
        try {
            $transactionDate = Carbon::parse($request->transaction_date);
            $amount = (float) $request->debit;

            // cari record laporan berdasarkan group_id (transaction_group_id)
            $financialReport = FinancialReport::where('transaction_type', 'expense')
                ->where('reference_table', 'account_transactions')
                ->whereIn('reference_id', $transactions->pluck('id'))
                ->first();

            // ambil transaksi debit (akun biaya)
            $debitTrx = $transactions->first(fn($trx) => (float) $trx->debit > 0);

            if ($financialReport) {
                // update existing report
                $financialReport->update([
                    'date'         => $transactionDate,
                    'reference_id' => $debitTrx->id ?? $financialReport->reference_id,
                    'expense'      => $amount,
                    'net_profit'   => -$amount,
                    'notes'        => 'Auto-updated from Expense edit',
                ]);
            } else {
                // create new report (jika sebelumnya belum ada)
                FinancialReport::create([
                    'date'             => $transactionDate,
                    'transaction_type' => 'expense',
                    'reference_id'     => $debitTrx->id ?? null,
                    'reference_table'  => 'account_transactions',
                    'revenue'          => 0,
                    'cogs'             => 0,
                    'gross_profit'     => 0,
                    'expense'          => $amount,
                    'net_profit'       => -$amount,
                    'notes'            => 'Auto-generated from Expense edit',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui laporan keuangan untuk Expense (group_id: ' . $id . '): ' . $e->getMessage());
        }

        return redirect('/erp/expenses')->with('success', 'Expense updated successfully');
    }
}
