<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tombol Complete di Sale Return.
 *
 * Penanda selesai dipisah dari payment_status supaya status pembayarannya
 * (Partially Paid, dst.) tetap apa adanya walau retur sudah ditutup manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_returns', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('sale_returns', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('completed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn('completed_at');
        });
    }
};
