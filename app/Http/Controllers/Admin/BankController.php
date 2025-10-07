<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank;
use Yajra\DataTables\Facades\DataTables;

class BankController extends Controller
{
    public function index()
    {
        return view('erp.pages.bank.index');
    }

    public function dataBank(Request $request)
    {
        $bank = Bank::query();

        if ($request->filled('name')) {
            $bank->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('bank_name')) {
            $bank->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        if ($request->filled('account_number')) {
            $bank->where('account_number', 'like', '%' . $request->account_number . '%');
        }

        return DataTables::of($bank)
            ->addIndexColumn()
            ->addColumn('name', function ($bank) {
                return $bank->name;
            })
            ->addColumn('bank_name', function ($bank) {
                return $bank->bank_name;
            })
            ->addColumn('account_number', function ($bank) {
                return $bank->account_number;
            })
            ->addColumn('action', function ($bank) {
                return view('erp.pages.bank.partials.action-button', compact('bank'));
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.bank.create-bank');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
        ]);

        Bank::create([
            'name' => $request->name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
        ]);

        return redirect('/erp/banks')->with('success', 'Bank created successfully.');
    }

    public function edit($id)
    {
        $bank = Bank::findOrFail($id);
        return view('erp.pages.bank.edit-bank', compact('bank'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
        ]);

        $bank = Bank::findOrFail($id);
        $bank->update([
            'name' => $request->name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
        ]);

        return redirect('/erp/banks')->with('success', 'Bank updated successfully.');
    }

    public function delete($id)
    {
        Bank::findOrFail($id)->delete();
        return redirect('/erp/banks')->with('success', 'Bank deleted successfully.');
    }
}
