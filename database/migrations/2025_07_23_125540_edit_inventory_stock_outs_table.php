<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_stock_outs', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_stock_outs', 'inventory_id')) {
                $table->unsignedBigInteger('inventory_id')->nullable()->after('id');
            }
            $table->string('invoice_number')->nullable()->after('user_id');
            $table->string('waybill_number')->nullable()->after('change_date');
            $table->string('waybill_image')->nullable()->after('waybill_number');
        });

        DB::table('inventory_stock_outs')
            ->whereNotIn('inventory_id', function ($query) {
                $query->select('id')->from('inventories');
            })->delete();

        Schema::table('inventory_stock_outs', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_stock_outs', 'inventory_id_foreign')) {
                $table->foreign('inventory_id')
                    ->references('id')
                    ->on('inventories')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stock_outs', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
            $table->dropColumn('invoice_number');
            $table->dropColumn('waybill_number');
            $table->dropColumn('waybill_image');
        });
    }
};
