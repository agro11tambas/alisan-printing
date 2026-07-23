<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use PhpOffice\PhpSpreadsheet\Reader\Xls\RC4;
use Yajra\DataTables\Facades\DataTables;
use App\Models\AccountTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function getAccount()
    {
        return view('erp.pages.account.index');
    }

    public function dataAccount(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $account = Account::query();
        $hasDefault = Account::where('is_default', true)->exists();
        $hasDefaultPurchase = Account::where('is_default_purchase', true)->exists();

        // 🔍 Filter by name
        if ($request->filled('name')) {
            $account->where('name', 'like', '%' . $request->name . '%');
        }

        // 🔍 Filter by type
        if ($request->filled('type')) {
            $account->where('type', 'like', '%' . $request->type . '%');
        }

        // ✅ Hitung total data sebelum pagination
        $totalQuery = clone $account;
        $totalData = $totalQuery->count();

        // ✅ Ambil data sesuai offset dan limit
        $data = $account
            ->orderBy('name', 'asc')
            ->orderBy('id', 'asc')
            ->skip($start)
            ->take($length)
            ->get();

        // ✅ Format JSON ringan (lazy-load)
        return response()->json([
            'data' => $data->map(function ($account) use ($hasDefault, $hasDefaultPurchase) {
                // 🏷️ Nama + Badge Default
                $saleBadge = $account->is_default
                    ? ' <span class="badge bg-success ms-2">Default Sale</span>'
                    : '';
                $purchaseBadge = $account->is_default_purchase
                    ? ' <span class="badge bg-primary ms-2">Default Purchase</span>'
                    : '';
                $name = e($account->name).$saleBadge.$purchaseBadge;

                // 📘 Type
                $type = e($account->type ?? '-');

                // ⚙️ Action Partial
                $action = view('erp.pages.account.partials.action-button', compact(
                    'account',
                    'hasDefault',
                    'hasDefaultPurchase'
                ))->render();

                return [
                    'id' => $account->id,
                    'name' => $name,
                    'type' => $type,
                    'has_default' => $hasDefault,
                    'action' => $action,
                ];
            }),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create()
    {
        return view('erp.pages.account.create-account');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        Account::create([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect('/erp/accounts')->with('success', 'Account created successfully');
    }

    public function edit($id)
    {
        $account = Account::find($id);

        return view('erp.pages.account.edit-account', compact('account'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        $account = Account::findOrFail($id);

        $account->name = $request->name;
        $account->type = $request->type;
        $account->save();

        return redirect('/erp/accounts')->with('success', 'Account updated successfully');
    }

    public function delete($id)
    {
        try {
            $account = Account::findOrFail($id);

            // 🔒 Cek apakah akun ini default
            if ($account->is_default || $account->is_default_purchase) {
                return redirect('/erp/accounts')->with(
                    'error',
                    'Account ini masih menjadi Default Sale atau Default Purchase. Hapus status default terlebih dahulu.'
                );
            }

            // 🔹 Kalau bukan default, hapus
            $account->delete();

            return redirect('/erp/accounts')->with('success', 'Account deleted successfully');
        } catch (\Throwable $e) {
            return redirect('/erp/accounts')->with('error', 'Gagal menghapus account: ' . $e->getMessage());
        }
    }

    public function markAsDefault($id)
    {
        DB::beginTransaction();
        try {
            Account::query()->update(['is_default' => false]);
            $account = Account::findOrFail($id);
            $account->update(['is_default' => true]);

            DB::commit();
            return redirect()->back()->with('success', "{$account->name} berhasil dijadikan default account.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menetapkan default account: ' . $e->getMessage());
        }
    }

    public function removeDefault($id)
    {
        $account = Account::findOrFail($id);

        if (!$account->is_default) {
            return redirect()->back()->with('error', 'Account ini bukan default.');
        }

        $account->update(['is_default' => false]);
        return redirect()->back()->with('success', "{$account->name} tidak lagi menjadi default account.");
    }

    public function markAsDefaultPurchase($id)
    {
        DB::beginTransaction();
        try {
            Account::query()->update(['is_default_purchase' => false]);
            $account = Account::findOrFail($id);
            $account->update(['is_default_purchase' => true]);

            DB::commit();

            return redirect()->back()->with(
                'success',
                "{$account->name} berhasil dijadikan default account Purchase."
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menetapkan default account Purchase: '.$e->getMessage());
        }
    }

    public function removeDefaultPurchase($id)
    {
        $account = Account::findOrFail($id);

        if (! $account->is_default_purchase) {
            return redirect()->back()->with('error', 'Account ini bukan default Purchase.');
        }

        $account->update(['is_default_purchase' => false]);

        return redirect()->back()->with(
            'success',
            "{$account->name} tidak lagi menjadi default account Purchase."
        );
    }
}
