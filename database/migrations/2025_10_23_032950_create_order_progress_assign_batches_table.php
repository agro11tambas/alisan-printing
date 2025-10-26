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
        Schema::create('order_progress_assign_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_progress_id')->constrained('order_progresses_2')->onDelete('cascade');
            $table->string('assign_code')->unique();
            $table->date('assign_date')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('order_progress_assigns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_progress_assigns', 'assign_batch_id')) {
                $table->foreignId('assign_batch_id')->after('id')->nullable()->constrained('order_progress_assign_batches')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_progress_assign_batches');
    }
};
