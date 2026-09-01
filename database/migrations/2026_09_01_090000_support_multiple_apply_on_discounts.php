<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `discounts.apply_on` berubah dari satu nilai jadi daftar scope.
 *
 * Formatnya string dipisah koma dengan urutan baku:
 * "Product,Category,Mode,EcommerceCategory". Baris lama yang isinya satu nilai
 * tetap valid apa adanya, jadi tidak ada data yang perlu ditulis ulang.
 *
 * Yang perlu ditarik ke dalam daftar cuma `apply_on_ecommerce`, karena mulai
 * sekarang target ecommerce ikut dievaluasi lewat `apply_on` yang sama.
 * Kolomnya sendiri dipertahankan dan tetap disinkronkan oleh controller supaya
 * response API ecommerce tidak berubah bentuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('discounts')
            ->where('apply_on_ecommerce', 'Category')
            ->where('apply_on', 'not like', '%EcommerceCategory%')
            ->update([
                'apply_on' => DB::raw("CASE WHEN apply_on IS NULL OR apply_on = '' THEN 'EcommerceCategory' ELSE CONCAT(apply_on, ',EcommerceCategory') END"),
            ]);
    }

    public function down(): void
    {
        DB::table('discounts')
            ->where('apply_on', 'like', '%EcommerceCategory%')
            ->update([
                'apply_on' => DB::raw("NULLIF(TRIM(BOTH ',' FROM REPLACE(REPLACE(apply_on, ',EcommerceCategory', ''), 'EcommerceCategory', '')), '')"),
            ]);
    }
};
