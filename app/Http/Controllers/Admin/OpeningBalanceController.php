<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OpeningBalance;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\ManageOpeningBalance;

class OpeningBalanceController extends Controller
{
    public function getOpeningBalance()
    {
        $bankAccounts = Account::where('name', 'Bank')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $purchaseAccounts = Account::where('name', 'Purchase')->get();
        $saleAccounts = Account::where('name', 'Sale')->get();
        $expenseAccounts = Account::where('name', 'Expense')->get();
        $capitalAccounts = Account::where('name', 'Capital')->get();

        return view('erp.pages.opening-balance.index', compact('bankAccounts', 'cashAccounts', 'saleAccounts', 'purchaseAccounts', 'expenseAccounts', 'capitalAccounts'));
    }

    public function create()
    {
        $bankAccounts = Account::where('name', 'Bank')->get();
        $cashAccounts = Account::where('name', 'Cash')->get();
        $purchaseAccounts = Account::where('name', 'Purchase')->get();
        $saleAccounts = Account::where('name', 'Sale')->get();
        $expenseAccounts = Account::where('name', 'Expense')->get();
        $capitalAccounts = Account::where('name', 'Capital')->get();

        return view('erp.pages.opening-balance.create-opening-balance', compact('bankAccounts', 'cashAccounts', 'purchaseAccounts', 'saleAccounts', 'expenseAccounts', 'capitalAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.account' => 'required|exists:accounts,id',
        ]);

        foreach ($request->accounts as $data) {
            // Bersihkan format ribuan (hapus titik)
            $debit = isset($data['debit']) ? str_replace('.', '', $data['debit']) : 0;
            $credit = isset($data['credit']) ? str_replace('.', '', $data['credit']) : 0;

            // Pastikan angka murni (bukan string kosong)
            $debit = is_numeric($debit) ? $debit : 0;
            $credit = is_numeric($credit) ? $credit : 0;

            // Simpan ke tabel manage_opening_balance
            ManageOpeningBalance::create([
                'account_id' => $data['account'],
                'debit' => $debit,
                'credit' => $credit,
            ]);

            // Update opening_balance di table accounts
            $account = Account::find($data['account']);
            $account->accountOpeningBalance(); // method existing lo
        }

        return redirect('/erp/accounts/opening-balance')
            ->with('success', 'Opening Balance berhasil ditambahkan.');
    }

    public function edit()
    {
        $bankAccounts = Account::where('name', 'Bank')->with('openingBalance')->get();
        foreach ($bankAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        $cashAccounts = Account::where('name', 'Cash')->with('openingBalance')->get();
        foreach ($cashAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        $purchaseAccounts = Account::where('name', 'Purchase')->with('openingBalance')->get();
        foreach ($purchaseAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        $saleAccounts = Account::where('name', 'Sale')->with('openingBalance')->get();
        foreach ($saleAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        $expenseAccounts = Account::where('name', 'Expense')->with('openingBalance')->get();
        foreach ($expenseAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        $capitalAccounts = Account::where('name', 'Capital')->with('openingBalance')->get();
        foreach ($capitalAccounts as $account) {
            $opening = $account->openingBalance->first();
            $account->opening_debit = $opening?->debit ?? 0;
            $account->opening_credit = $opening?->credit ?? 0;
        }

        return view('erp.pages.opening-balance.edit-opening-balance', compact('bankAccounts', 'cashAccounts', 'purchaseAccounts', 'saleAccounts', 'expenseAccounts', 'capitalAccounts'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.account' => 'required|exists:accounts,id',
        ]);

        foreach ($request->accounts as $data) {
            // 🧹 Bersihkan format ribuan
            $debit = isset($data['debit']) ? str_replace('.', '', $data['debit']) : 0;
            $credit = isset($data['credit']) ? str_replace('.', '', $data['credit']) : 0;

            // Pastikan angka valid
            $debit = is_numeric($debit) ? $debit : 0;
            $credit = is_numeric($credit) ? $credit : 0;

            // 🔍 Cek apakah sudah ada opening balance untuk akun ini
            $opening = ManageOpeningBalance::where('account_id', $data['account'])->first();

            if ($opening) {
                // update
                $opening->update([
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            } else {
                // insert baru
                ManageOpeningBalance::create([
                    'account_id' => $data['account'],
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }

            // 🔄 Hitung ulang saldo opening
            $account = Account::find($data['account']);
            $account->accountOpeningBalance();
        }

        return redirect('/erp/accounts/opening-balance')
            ->with('success', 'Opening Balance berhasil diperbarui.');
    }
}
