<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_login_links', function (Blueprint $table) {
            $table->id();
            // Yang login adalah akunnya, bukan baris customer — sama seperti customer_password_reset_tokens.
            $table->foreignId('customer_account_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();
            // sha256 dari kode, bukan kodenya: kalau tabel ini bocor, tidak ada link yang langsung bisa dipakai.
            $table->string('code_hash', 64)->unique();
            $table->boolean('single_use')->default(true);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['expires_at', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_login_links');
    }
};
