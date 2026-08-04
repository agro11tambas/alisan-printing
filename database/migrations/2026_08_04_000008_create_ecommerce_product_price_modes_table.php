<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_product_price_modes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecommerce_product_id');
            $table->unsignedBigInteger('price_mode_id');
            $table->timestamps();

            $table->foreign('ecommerce_product_id', 'ecomm_mode_product_fk')
                ->references('id')->on('ecommerce_products')->cascadeOnDelete();
            $table->foreign('price_mode_id', 'ecomm_mode_price_mode_fk')
                ->references('id')->on('price_modes')->cascadeOnDelete();
            $table->unique(['ecommerce_product_id', 'price_mode_id'], 'ecomm_product_mode_unique');
        });

        $now = now();
        $rows = DB::table('ecommerce_products')
            ->crossJoin('price_modes')
            ->where('price_modes.is_active', true)
            ->select([
                'ecommerce_products.id as ecommerce_product_id',
                'price_modes.id as price_mode_id',
            ])
            ->get()
            ->map(fn ($row) => [
                'ecommerce_product_id' => $row->ecommerce_product_id,
                'price_mode_id' => $row->price_mode_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('ecommerce_product_price_modes')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_product_price_modes');
    }
};