<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('order_progress_assign_batches', 'machine_id')) {
            Schema::table('order_progress_assign_batches', function (Blueprint $table) {
                $table->foreignId('machine_id')->nullable()->after('order_progress_id')->constrained('machines')->nullOnDelete();
            });
        }

        // Mesin sekarang dipilih per batch (per invoice), bukan per produk.
        // Data lama: ambil mesin pertama yang terpakai di dalam batch.
        DB::statement("
            UPDATE order_progress_assign_batches AS b
            INNER JOIN (
                SELECT assign_batch_id, MIN(machine_id) AS machine_id
                FROM order_progress_assigns
                WHERE machine_id IS NOT NULL
                GROUP BY assign_batch_id
            ) AS a ON a.assign_batch_id = b.id
            SET b.machine_id = a.machine_id
            WHERE b.machine_id IS NULL
        ");

        // Samakan mesin seluruh assign di dalam batch dengan mesin batch-nya,
        // supaya history progress tetap membaca mesin yang benar.
        DB::statement("
            UPDATE order_progress_assigns AS a
            INNER JOIN order_progress_assign_batches AS b ON b.id = a.assign_batch_id
            SET a.machine_id = b.machine_id
            WHERE b.machine_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_assign_batches', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });
    }
};
