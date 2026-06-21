<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_accounts', 'password')) {
                $table->string('password')->nullable()->after('whatsapp_number');
            }

            if (! Schema::hasColumn('customer_accounts', 'auth_provider')) {
                $table->string('auth_provider')->default('manual')->after('password');
            }

            if (! Schema::hasColumn('customer_accounts', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('customer_accounts', 'remember_token')) {
                $table->dropColumn('remember_token');
            }

            if (Schema::hasColumn('customer_accounts', 'auth_provider')) {
                $table->dropColumn('auth_provider');
            }

            if (Schema::hasColumn('customer_accounts', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
