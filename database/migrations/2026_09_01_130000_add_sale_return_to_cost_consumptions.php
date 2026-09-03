<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retur penjualan dicatat sebagai baris cost_consumptions dengan qty negatif,
 * menunjuk batch yang sama dengan penjualan aslinya. Dengan begitu stok yang
 * balik masuk kembali ke batch asalnya, bukan jadi batch baru berharga lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_consumptions', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_return_item_id')->nullable()->after('order_item_id');

            // 1 = barang rusak: biayanya dibalikkan, tapi stoknya tidak kembali
            // ke antrian karena tidak bisa dijual lagi.
            $table->boolean('is_defect')->default(false)->after('is_estimated');

            $table->index('sale_return_item_id');
        });

        Schema::table('order_item_costs', function (Blueprint $table) {
            $table->decimal('returned_qty', 20, 4)->default(0)->after('qty_base');
            $table->decimal('returned_cost', 20, 4)->default(0)->after('total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('cost_consumptions', function (Blueprint $table) {
            $table->dropIndex(['sale_return_item_id']);
            $table->dropColumn(['sale_return_item_id', 'is_defect']);
        });

        Schema::table('order_item_costs', function (Blueprint $table) {
            $table->dropColumn(['returned_qty', 'returned_cost']);
        });
    }
};
