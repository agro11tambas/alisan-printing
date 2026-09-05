<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog design milik customer.
 *
 * Satu baris = satu design, dan satu customer boleh punya banyak design.
 * Gambarnya disimpan sebagai JSON [{file, note}, ...] — format yang sama
 * dengan design_items.preview_image, supaya design dari katalog ini bisa
 * langsung dipakai ulang di modul Design tanpa konversi apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->json('images')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_designs');
    }
};
