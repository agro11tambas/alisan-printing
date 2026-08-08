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
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->after('operator_id')->constrained('machines')->nullOnDelete();
        });

        // Assign sekarang memakai mesin, jadi operator tidak lagi wajib diisi.
        DB::statement('ALTER TABLE order_progress_assigns MODIFY operator_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_progress_assigns', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });

        DB::statement('ALTER TABLE order_progress_assigns MODIFY operator_id BIGINT UNSIGNED NOT NULL');
    }
};
