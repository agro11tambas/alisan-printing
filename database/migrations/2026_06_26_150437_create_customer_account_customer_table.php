<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_account_customer', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_account_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['customer_account_id', 'customer_id'], 'customer_account_customer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_account_customer');
    }
};
