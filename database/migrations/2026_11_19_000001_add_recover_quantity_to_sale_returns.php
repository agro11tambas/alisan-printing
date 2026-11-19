<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recover Qty pada Sale Return.
 *
 * Barang yang di-recover masuk ke modul Recover Product (tabel reject_products,
 * dulu QC Reject) supaya bisa dikembalikan ke gudang dari sana. Karena itu
 * reject_products perlu tahu sale return asalnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_return_items', 'recover_quantity')) {
                $table->integer('recover_quantity')->default(0)->after('canceled_quantity');
            }
        });

        Schema::table('reject_products', function (Blueprint $table) {
            if (! Schema::hasColumn('reject_products', 'sale_return_id')) {
                $table->foreignId('sale_return_id')->nullable()->after('product_id')
                    ->constrained('sale_returns')->nullOnDelete();
            }
            if (! Schema::hasColumn('reject_products', 'sale_return_item_id')) {
                $table->foreignId('sale_return_item_id')->nullable()->after('sale_return_id')
                    ->constrained('sale_return_items')->nullOnDelete();
            }
            if (! Schema::hasColumn('reject_products', 'order_item_id')) {
                $table->unsignedBigInteger('order_item_id')->nullable()->after('sale_return_item_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reject_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_return_item_id');
            $table->dropConstrainedForeignId('sale_return_id');
            $table->dropColumn('order_item_id');
        });

        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('recover_quantity');
        });
    }
};
