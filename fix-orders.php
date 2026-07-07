<?php
$orders = App\Models\Order::where("total_amount", ">", 1000000)->get();
foreach($orders as $order) {
    $fixedSubTotal = 0;
    foreach($order->orderItems as $item) {
        if ($item->price == 1450000) {
            $item->price = 1450;
            $item->discount_price = 1450;
            $item->subtotal = 1450 * $item->quantity;
            $item->total_after_discount = 1450 * $item->quantity;
        }
        if ($item->product_bundle_id != null && $item->satuan == "satuan") {
            $item->satuan = "bundle";
        }
        $item->save();
        $fixedSubTotal += $item->total_after_discount;
    }
    if ($fixedSubTotal > 0 && $order->total_amount > 1000000) {
        $order->total_amount = $fixedSubTotal;
        $order->grand_total = $fixedSubTotal;
        $order->remaining_amount = $fixedSubTotal;
        $order->save();
    }
}
echo "Fixed orders!";

