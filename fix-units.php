<?php
$items = App\Models\OrderItem::whereNull("product_unit_conversion_id")
    ->whereNull("product_bundle_unit_conversion_id")
    ->get();
foreach($items as $item) {
    if ($item->satuan == "satuan" && $item->product_id) {
        $p = App\Models\Products::find($item->product_id);
        if($p) {
            $id = $p->unitConversions()->where("conversion_value", 1)->value("id") ?? $p->unitConversions()->value("id");
            if($id) {
                $item->product_unit_conversion_id = $id;
                $item->save();
            }
        }
    } elseif ($item->satuan == "bundle" && $item->product_bundle_id) {
        $b = App\Models\ProductBundle::find($item->product_bundle_id);
        if($b) {
            $id = $b->unitConversions()->where("conversion_value", 1)->value("id") ?? $b->unitConversions()->value("id");
            if($id) {
                $item->product_bundle_unit_conversion_id = $id;
                $item->save();
            }
        }
    }
}
echo "Fixed units!";

