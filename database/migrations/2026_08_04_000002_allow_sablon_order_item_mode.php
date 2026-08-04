<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('mode', 30)->default('printing')->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
        DB::table('order_items')->where('mode', 'sablon')->update(['mode' => 'printing']);

            $table->enum('mode', ['printing', 'polosan'])->default('printing')->change();
        });
    }
};
