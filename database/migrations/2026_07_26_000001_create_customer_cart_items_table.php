<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_account_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();
            $table->string('cart_item_key', 191);
            $table->unsignedInteger('quantity');
            $table->boolean('is_selected')->default(true);
            $table->json('item_data');
            $table->timestamps();

            $table->unique(['customer_account_id', 'cart_item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_cart_items');
    }
};
