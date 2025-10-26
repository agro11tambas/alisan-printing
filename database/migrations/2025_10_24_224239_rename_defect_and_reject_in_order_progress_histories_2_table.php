<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            if (Schema::hasColumn('order_progress_histories_2', 'defect_product')) {
                $table->renameColumn('defect_product', 'defect_quantity');
            }
            if (Schema::hasColumn('order_progress_histories_2', 'reject_product')) {
                $table->renameColumn('reject_product', 'reject_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            if (Schema::hasColumn('order_progress_histories_2', 'defect_quantity')) {
                $table->renameColumn('defect_quantity', 'defect_product');
            }
            if (Schema::hasColumn('order_progress_histories_2', 'reject_quantity')) {
                $table->renameColumn('reject_quantity', 'reject_product');
            }
        });
    }
};
