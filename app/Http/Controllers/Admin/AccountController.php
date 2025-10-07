<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use PhpOffice\PhpSpreadsheet\Reader\Xls\RC4;
use Yajra\DataTables\Facades\DataTables;
use App\Models\AccountTransaction;
use Carbon\Carbon;

class AccountController extends Controller
{
    public function getAccount()
    {
        return view('erp.pages.account.index');
    }

    public function dataAccount(Request $request)
    {
        $account = Account::query();

        if ($request->filled('name')) {
            $account->where('name', 'like', '%' . request()->name . '%');
        }

        if ($request->filled('type')) {
            $account->where('type', 'like', '%' . request()->type . '%');
        }

        return DataTables::of($account)
            ->addIndexColumn()
            ->addColumn('name', function ($account) {
                return $account->name;
            })
            ->addColumn('type', function ($account) {
                return $account->type;
            })
            ->addColumn('action', function ($account) {
                return view('erp.pages.account.partials.action-button', compact('account'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
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

        $account = Account::where('id', $id);

        $account->update([
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect('/erp/accounts')->with('success', 'Account updated successfully');
    }

    public function delete($id)
    {
        $account = Account::where('id', $id);
        $account->delete();

        return redirect('/erp/accounts')->with('success', 'Account deleted successfully');
    }
}
