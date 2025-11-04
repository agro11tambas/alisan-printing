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
            // 🔹 Hapus foreign key lama (pastikan nama constraint sesuai di DB)
            $table->dropForeign('order_progress_histories_2_order_progress_assign_id_foreign');

            // 🔹 Tambah foreign key baru dengan ON DELETE CASCADE
            $table->foreign('order_progress_assign_id')
                ->references('id')
                ->on('order_progress_assigns')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_histories_2', function (Blueprint $table) {
            // Balikkan perubahan kalau di-rollback
            $table->dropForeign('order_progress_histories_2_order_progress_assign_id_foreign');

            $table->foreign('order_progress_assign_id')
                ->references('id')
                ->on('order_progress_assigns')
                ->onDelete('restrict'); // default behaviour MySQL
        });
    }
};
