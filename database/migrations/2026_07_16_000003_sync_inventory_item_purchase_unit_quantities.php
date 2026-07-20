<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_items_2 as inventory_item')
            ->join('purchase_items as purchase_item', 'purchase_item.id', '=', 'inventory_item.purchase_item_id')
            ->whereNull('inventory_item.deleted_at')
            ->whereNull('purchase_item.deleted_at')
            ->whereNotNull('purchase_item.product_unit_conversion_id')
            ->update([
                'inventory_item.quantity' => DB::raw('purchase_item.quantity'),
                'inventory_item.unit_name' => DB::raw('purchase_item.unit_name'),
                'inventory_item.unit_conversion_value' => DB::raw('purchase_item.unit_conversion_value'),
                'inventory_item.qty_base' => DB::raw('purchase_item.qty_base'),
                'inventory_item.remaining_stock_in' => DB::raw('purchase_item.qty_base'),
                'inventory_item.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // This synchronizes duplicated purchase-unit data and is not reversible.
    }
};
