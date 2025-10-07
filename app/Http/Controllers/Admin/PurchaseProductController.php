<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseProduct;
use Yajra\DataTables\Facades\DataTables;

class PurchaseProductController extends Controller
{
    public function getPurchaseProducts()
    {
        return view('erp.pages.purchase-products.index');
    }

    public function dataPurchaseProducts()
    {
        $purchaseProducts = PurchaseProduct::with(['purchaseItems', 'inventoryItem'])->latest();

        return DataTables::of($purchaseProducts)
            ->addIndexColumn()
            ->addColumn('name', function ($purchaseProduct) {
                return $purchaseProduct->name;
            })
            ->addColumn('action', function ($purchaseProduct) {
                return view('erp.pages.purchase-products.partials.action-button', compact('purchaseProduct'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        return view('erp.pages.purchase-products.create-purchase-product');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $purchaseProduct = PurchaseProduct::create([
            'name' => $request->name,
        ]);

        return redirect('/erp/purchase-products')->with('success', 'Purchase Product created successfully.');
    }

    public function edit($id)
    {
        $purchaseProduct = PurchaseProduct::findOrFail($id);
        return view('erp.pages.purchase-products.edit-purchase-product', compact('purchaseProduct'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $purchaseProduct = PurchaseProduct::findOrFail($id);
        $purchaseProduct->update([
            'name' => $request->name,
        ]);

        $purchaseProduct->warehouseItem?->update([
            'product_name' => $request->name,
        ]);

        return redirect('/erp/purchase-products')->with('success', 'Purchase Product updated successfully.');
    }

    public function delete($id)
    {
        $purchaseProduct = PurchaseProduct::findOrFail($id);
        $purchaseProduct->delete();
        return redirect('/erp/purchase-products')->with('success', 'Purchase Product deleted successfully.');
    }
}
