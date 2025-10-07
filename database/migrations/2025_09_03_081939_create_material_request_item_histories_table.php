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
        Schema::create('material_request_item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_item_id')->constrained('material_request_items')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->string('status')->default('Pending');
            $table->text('note')->nullable();
            $table->foreignId('verified_by')->constrained('users')->onDelete('cascade');
            $table->date('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_request_item_histories');
    }
};
