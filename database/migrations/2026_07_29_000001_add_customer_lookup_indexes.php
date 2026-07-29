<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name', 'customers_name_lookup_index');
        });

        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->index('name', 'customer_accounts_name_lookup_index');
            $table->index('whatsapp_number', 'customer_accounts_whatsapp_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropIndex('customer_accounts_name_lookup_index');
            $table->dropIndex('customer_accounts_whatsapp_lookup_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_name_lookup_index');
        });
    }
};