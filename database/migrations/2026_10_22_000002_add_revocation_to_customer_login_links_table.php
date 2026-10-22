<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_login_links', function (Blueprint $table) {
            // expires_at null = tidak pernah kedaluwarsa. Link dimatikan lewat
            // tombol cabut, bukan lewat umur.
            $table->timestamp('expires_at')->nullable()->change();

            if (! Schema::hasColumn('customer_login_links', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('used_ip');
            }

            if (! Schema::hasColumn('customer_login_links', 'revoked_by')) {
                $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users');
            }

            if (! Schema::hasColumn('customer_login_links', 'use_count')) {
                // Link boleh dipakai berkali-kali, jadi used_at hanya menyimpan
                // pemakaian terakhir. Hitungan ini yang memperlihatkan kalau satu
                // link dipakai jauh lebih sering dari seharusnya.
                $table->unsignedInteger('use_count')->default(0)->after('used_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_login_links', function (Blueprint $table) {
            if (Schema::hasColumn('customer_login_links', 'revoked_by')) {
                $table->dropForeign(['revoked_by']);
                $table->dropColumn('revoked_by');
            }

            if (Schema::hasColumn('customer_login_links', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }

            if (Schema::hasColumn('customer_login_links', 'use_count')) {
                $table->dropColumn('use_count');
            }
        });
    }
};
