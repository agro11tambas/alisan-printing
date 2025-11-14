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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $expense = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Expense');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $expense->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular (keterangan)
        if ($request->filled('search_particular')) {
            $expense->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $expense->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $expense;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $expense->skip($start)->take($length)->get();

        // ✅ Format JSON (lazy-load)
        return response()->json([
            'data' => $data->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'account_type' => $expense->account->type ?? '-',
                    'particular' => $expense->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($expense->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($expense->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($expense->credit ?? 0, 0, ',', '.'),
                    'note' => $expense->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function getExpense()
    {
        $accountTypes = Account::where('name', 'Expense')
            ->select('type')
            ->distinct()
            ->pluck('type');

        return view('erp.pages.account-list.expense', compact('accountTypes'));
    }

    public function dataCustomerDeposit(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $deposit = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Customer Deposit');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $deposit->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular
        if ($request->filled('search_particular')) {
            $deposit->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $deposit->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // 🔢 Hitung total data
        $totalQuery = clone $deposit;
        $totalData = $totalQuery->count();

        // 📌 Pagination manual
        $data = $deposit->skip($start)->take($length)->get();

        return response()->json([
            'data' => $data->map(function ($d) {
                return [
                    'id' => $d->id,
                    'account_type' => $d->account->type ?? '-',
                    'particular' => $d->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($d->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($d->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($d->credit ?? 0, 0, ',', '.'),
                    'note' => $d->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function getCustomerDeposit()
    {
        $accountTypes = Account::where('name', 'Customer Deposit')
            ->select('type')
            ->distinct()
            ->pluck('type');

        return view('erp.pages.account-list.customer-deposit', compact('accountTypes'));
    }

    public function dataBank(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $bank = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Bank');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $bank->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular (uraian)
        if ($request->filled('search_particular')) {
            $bank->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $bank->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total sebelum pagination
        $totalQuery = clone $bank;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $bank->skip($start)->take($length)->get();

        // ✅ Return JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'account_type' => $bank->account->type ?? '-',
                    'particular' => $bank->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($bank->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($bank->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($bank->credit ?? 0, 0, ',', '.'),
                    'note' => $bank->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $cash = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Cash');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $cash->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular
        if ($request->filled('search_particular')) {
            $cash->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $cash->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $cash;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $cash->skip($start)->take($length)->get();

        // ✅ Return JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($cash) {
                return [
                    'id' => $cash->id,
                    'account_type' => $cash->account->type ?? '-',
                    'particular' => $cash->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($cash->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($cash->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($cash->credit ?? 0, 0, ',', '.'),
                    'note' => $cash->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $sale = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Sale');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $sale->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular
        if ($request->filled('search_particular')) {
            $sale->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $sale->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $sale;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $sale->skip($start)->take($length)->get();

        // ✅ Return JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'account_type' => $sale->account->type ?? '-',
                    'particular' => $sale->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($sale->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($sale->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($sale->credit ?? 0, 0, ',', '.'),
                    'note' => $sale->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $purchase = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Purchase');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $purchase->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular (uraian transaksi)
        if ($request->filled('search_particular')) {
            $purchase->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $purchase->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $purchase;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $purchase->skip($start)->take($length)->get();

        // ✅ Return JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'account_type' => $purchase->account->type ?? '-',
                    'particular' => $purchase->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($purchase->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($purchase->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($purchase->credit ?? 0, 0, ',', '.'),
                    'note' => $purchase->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $capital = AccountTransaction::with('account')
            ->whereHas('account', function ($q) {
                $q->where('name', 'Capital');
            })
            ->orderByDesc('id');

        // 🔹 Filter tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $capital->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        // 🔹 Filter particular
        if ($request->filled('search_particular')) {
            $capital->where('particular', 'like', '%' . $request->search_particular . '%');
        }

        // 🔹 Filter tipe akun
        if ($request->filled('search_account_type')) {
            $capital->whereHas('account', function ($q) use ($request) {
                $q->where('type', 'like', '%' . $request->search_account_type . '%');
            });
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $capital;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $capital->skip($start)->take($length)->get();

        // ✅ Return JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($capital) {
                return [
                    'id' => $capital->id,
                    'account_type' => $capital->account->type ?? '-',
                    'particular' => $capital->particular ?? '-',
                    'transaction_date' => \Carbon\Carbon::parse($capital->transaction_date)->format('d F Y'),
                    'debit' => 'Rp ' . number_format($capital->debit ?? 0, 0, ',', '.'),
                    'credit' => 'Rp ' . number_format($capital->credit ?? 0, 0, ',', '.'),
                    'note' => $capital->note ?? '-',
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
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
