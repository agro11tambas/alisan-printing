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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index(); // tanggal transaksi
            $table->string('transaction_type'); // sale, purchase, return, expense, etc
            $table->unsignedBigInteger('reference_id')->nullable(); // id referensi transaksi
            $table->string('reference_table')->nullable(); // nama tabel referensi (orders, purchases, dll)
            
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('cogs', 15, 2)->default(0);
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->decimal('expense', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
