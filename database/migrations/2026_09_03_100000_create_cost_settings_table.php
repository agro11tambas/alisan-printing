<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setelan modul HPP. Tabel settings yang sudah ada hanya menyimpan boolean
 * (kolom value bertipe tinyint), sedangkan yang dibutuhkan di sini adalah
 * tanggal, jadi dibuat tabel sendiri yang nilainya bebas.
 *
 * Isi pertamanya: fifo_start_date — tanggal mulai pembukuan FIFO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_settings');
    }
};
