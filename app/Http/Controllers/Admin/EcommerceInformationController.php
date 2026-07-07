<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EcommerceInformation;

class EcommerceInformationController extends Controller
{
    public function index()
    {
        $information = EcommerceInformation::first();
        return view('erp.pages.ecommerce-information.index', compact('information'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        $information = EcommerceInformation::first();

        if ($information) {
            $information->update([
                'phone_number' => $request->phone_number,
            ]);
        } else {
            EcommerceInformation::create([
                'phone_number' => $request->phone_number,
            ]);
        }

        return redirect()->back()->with('success', 'Information updated successfully.');
    }
}
