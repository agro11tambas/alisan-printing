<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cabut "EcommerceCategory" dari daftar `apply_on`.
 *
 * Sempat ada rencana menyatukan target ecommerce ke dalam `apply_on`, dan
 * migrasi sebelumnya menempelkan "EcommerceCategory" ke baris yang
 * `apply_on_ecommerce`-nya "Category". Rencananya dibatalkan: `apply_on`
 * sekarang cuma berisi "Category" dan "Mode", sedangkan target ecommerce
 * kembali berdiri sendiri di kolom `apply_on_ecommerce`.
 *
 * Kolom `apply_on_ecommerce` sendiri tidak diubah, jadi tidak ada informasi
 * yang hilang — migrasi ini hanya membersihkan sisa penempelan tadi. Di
 * database yang belum sempat menjalankan migrasi itu, tidak ada baris yang
 * cocok dan migrasi ini tidak melakukan apa-apa.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('discounts')
            ->where('apply_on', 'like', '%EcommerceCategory%')
            ->update([
                'apply_on' => DB::raw("NULLIF(TRIM(BOTH ',' FROM REPLACE(REPLACE(apply_on, ',EcommerceCategory', ''), 'EcommerceCategory', '')), '')"),
            ]);
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan: "EcommerceCategory" bukan lagi nilai yang
        // sah untuk `apply_on`, dan sumber datanya tetap utuh di
        // `apply_on_ecommerce`.
    }
};
