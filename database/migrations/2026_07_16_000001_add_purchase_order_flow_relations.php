<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('parent_purchase_id')
                ->nullable()
                ->after('id')
                ->constrained('purchases')
                ->nullOnDelete();
            $table->string('approval_status', 20)
                ->default('Draft')
                ->after('status');
            $table->index(['parent_purchase_id', 'status']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('source_purchase_item_id')
                ->nullable()
                ->after('purchase_id')
                ->constrained('purchase_items')
                ->nullOnDelete();
            $table->index(['source_purchase_item_id', 'purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['source_purchase_item_id']);
            $table->dropIndex(['source_purchase_item_id', 'purchase_id']);
            $table->dropColumn('source_purchase_item_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['parent_purchase_id']);
            $table->dropIndex(['parent_purchase_id', 'status']);
            $table->dropColumn(['parent_purchase_id', 'approval_status']);
        });
    }
};
