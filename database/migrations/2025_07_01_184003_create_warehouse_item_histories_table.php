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
        Schema::create('warehouse_item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_item_id')->constrained('warehouse_items')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('set null');
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->onDelete('set null');

            $table->string('reference_number')->nullable();
            $table->date('reference_date')->nullable();
            $table->decimal('price', 15, 2)->default(0);

            $table->integer('stock_in')->default(0);
            $table->integer('stock_out')->default(0);
            $table->decimal('valuation', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_item_histories');
    }
};
