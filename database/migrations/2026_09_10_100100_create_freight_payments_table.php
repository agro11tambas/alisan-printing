<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembayaran ongkos angkut dikelompokkan per nomor surat jalan, bukan per
     * purchase. Satu surat jalan bisa memuat beberapa stock in dari beberapa
     * purchase sekaligus, jadi tagihannya memang hidup di level surat jalan.
     *
     * Stock in yang nomor surat jalannya kosong tetap bisa ditagih: kuncinya
     * jatuh ke "SI-<id stock in>" (lihat FreightPaymentController::waybillKey).
     */
    public function up(): void
    {
        Schema::create('freight_payments', function (Blueprint $table) {
            $table->id();
            $table->string('waybill_key')->index();
            $table->string('waybill_number')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->date('transaction_date');
            $table->foreignId('cash_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->uuid('transaction_group_id')->nullable()->index();
            $table->text('proof')->nullable();
            $table->text('note')->nullable();
            $table->string('particular')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_payments');
    }
};
