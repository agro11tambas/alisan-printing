<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;

class PurchaseDetailController extends Controller
{
    public function getPurchaseOrderDetail($id)
    {
        $purchase = Purchase::with([
            'purchaseItems.purchaseProduct',
            'purchaseItems.purchaseListItems',
            'purchaseLists.purchaseItems',
        ])->findOrFail($id);

        return view('erp.pages.purchases.purchase-orders.detail-purchase', compact('purchase'));
    }

    public function getPurchaseListDetail($id)
    {
        $purchase = Purchase::with([
            'parentPurchase',
            'purchaseItems.purchaseProduct',
            // Realisasi Stock In dibaca dari inventory item, bukan dari kolom
            // purchase_items.stock_in yang bisa basi kalau history stock in diedit.
            'purchaseItems.inventoryItems',
        ])->findOrFail($id);

        return view('erp.pages.purchases.purchase-list.detail-purchase', compact('purchase'));
    }

    public function getPurchaseReturnDetail($id)
    {
        $purchase = Purchase::with('purchaseItems')->findOrFail($id);

        return view('erp.pages.purchases.purchase-returns.detail-purchase', compact('purchase'));
    }
}
