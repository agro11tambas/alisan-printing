<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;

class PurchaseDetailController extends Controller
{
    public function getPurchaseOrderDetail($id)
    {
        $purchase = Purchase::with('purchaseItems')->findOrFail($id);
        return view('erp.pages.purchases.purchase-orders.detail-purchase', compact('purchase'));
    }

    public function getPurchaseListDetail($id)
    {
        $purchase = Purchase::with('purchaseItems')->findOrFail($id);
        return view('erp.pages.purchases.purchase-list.detail-purchase', compact('purchase'));
    }

    public function getPurchaseReturnDetail($id)
    {
        $purchase = Purchase::with('purchaseItems')->findOrFail($id);
        return view('erp.pages.purchases.purchase-returns.detail-purchase', compact('purchase'));
    }
}
