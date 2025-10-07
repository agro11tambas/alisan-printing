<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SaleReturn;

class OrderDetailController extends Controller
{
    public function getOrderDetail($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return view('erp.pages.sales.detail-order', compact('order'));
    }

    public function getSaleOrderDetail($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return view('erp.pages.sales.sale-orders.detail-order', compact('order'));
    }

    public function getSaleListDetail($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return view('erp.pages.sales.sale-list.detail-order', compact('order'));
    }

    public function getSaleReturnDetail($id)
    {
        $return = SaleReturn::with('items.product')->findOrFail($id);
        return view('erp.pages.sales.sale-return.detail-order', compact('return'));
    }
}
