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
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->constrained('users')->after('deleted_at');
            $table->text('delete_notes')->nullable()->after('status');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->constrained('users')->after('deleted_at');
            $table->text('delete_notes')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
            $table->dropColumn('delete_notes');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
            $table->dropColumn('delete_notes');
        });
    }
};
