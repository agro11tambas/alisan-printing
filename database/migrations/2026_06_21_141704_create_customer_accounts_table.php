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
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->string('google_id')->nullable()->unique();

            $table->string('name');
            $table->string('email')->unique();

            $table->string('avatar')->nullable();
            $table->string('whatsapp_number')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('email');
            $table->index('google_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
