<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🧩 Tambah kolom baru di reject_products
        Schema::table('reject_products', function (Blueprint $table) {
            if (!Schema::hasColumn('reject_products', 'order_progress_history_2_id')) {
                $table->foreignId('order_progress_history_2_id')
                    ->nullable()
                    ->constrained('order_progress_histories_2')
                    ->onDelete('set null')
                    ->after('product_id');
            }
        });

        // 🧩 Tambah kolom baru di defect_products
        Schema::table('defect_products', function (Blueprint $table) {
            if (!Schema::hasColumn('defect_products', 'order_progress_history_2_id')) {
                $table->foreignId('order_progress_history_2_id')
                    ->nullable()
                    ->constrained('order_progress_histories_2')
                    ->onDelete('set null')
                    ->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reject_products', function (Blueprint $table) {
            $table->dropForeign(['order_progress_history_2_id']);
            $table->dropColumn('order_progress_history_2_id');
        });

        Schema::table('defect_products', function (Blueprint $table) {
            $table->dropForeign(['order_progress_history_2_id']);
            $table->dropColumn('order_progress_history_2_id');
        });
    }
};
