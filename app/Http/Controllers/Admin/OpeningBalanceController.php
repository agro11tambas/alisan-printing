<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ManageOpeningBalance;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function getOpeningBalance()
    {
        return view('erp.pages.opening-balance.index', $this->accountsByType(withOpeningBalances: true));
    }

    public function create()
    {
        return view('erp.pages.opening-balance.create-opening-balance', $this->accountsByType());
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
        $accounts = $this->accountsByType(withOpeningBalances: true);

        foreach ($accounts as $group) {
            foreach ($group as $account) {
                $opening = $account->openingBalance->first();
                $account->opening_debit = $opening?->debit ?? 0;
                $account->opening_credit = $opening?->credit ?? 0;
            }
        }

        return view('erp.pages.opening-balance.edit-opening-balance', $accounts);
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

    /** @return array<string, \Illuminate\Support\Collection<int, Account>> */
    private function accountsByType(bool $withOpeningBalances = false): array
    {
        $types = [
            'Bank' => 'bankAccounts',
            'Cash' => 'cashAccounts',
            'Purchase' => 'purchaseAccounts',
            'Sale' => 'saleAccounts',
            'Expense' => 'expenseAccounts',
            'Capital' => 'capitalAccounts',
        ];

        $query = Account::query()->whereIn('name', array_keys($types));
        if ($withOpeningBalances) {
            $query->with('openingBalance');
        }

        $grouped = $query->get()->groupBy('name');
        $result = [];
        foreach ($types as $name => $variable) {
            $result[$variable] = $grouped->get($name, collect());
        }

        return $result;
    }
}
