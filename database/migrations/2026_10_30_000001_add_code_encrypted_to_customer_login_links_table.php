<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_login_links', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_login_links', 'code_encrypted')) {
                // Kode asli terenkripsi (APP_KEY) supaya admin bisa menyalin link
                // lagi setelah modal ditutup. Penebusan tetap memakai code_hash.
                // Link lama tidak punya isi di sini: kodenya memang tidak pernah disimpan.
                $table->text('code_encrypted')->nullable()->after('code_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_login_links', function (Blueprint $table) {
            if (Schema::hasColumn('customer_login_links', 'code_encrypted')) {
                $table->dropColumn('code_encrypted');
            }
        });
    }
};
