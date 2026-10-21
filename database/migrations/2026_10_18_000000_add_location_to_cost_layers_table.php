<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch yang barangnya masih di gudang sekarang PASIF: dia tidak ikut
     * menilai penjualan sampai barangnya dikirim ke produksi lewat Request
     * Stock. Kolom location membedakan keduanya.
     *
     * Aturan ini hanya berlaku untuk stock in sejak migration ini dijalankan.
     * Stok yang sudah ada sebelumnya dianggap aktif, jadi tanggal hari ini
     * dicatat sebagai titik potongnya.
     */
    public function up(): void
    {
        Schema::table('cost_layers', function (Blueprint $table) {
            $table->string('location', 16)->default('production')->after('source_id');
            $table->index(['product_id', 'location'], 'cost_layers_location_index');
        });

        if (! DB::table('cost_settings')->where('key', 'fifo_passive_warehouse_from')->exists()) {
            DB::table('cost_settings')->insert([
                'key' => 'fifo_passive_warehouse_from',
                'value' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('cost_layers', function (Blueprint $table) {
            $table->dropIndex('cost_layers_location_index');
            $table->dropColumn('location');
        });

        DB::table('cost_settings')->where('key', 'fifo_passive_warehouse_from')->delete();
    }
};
