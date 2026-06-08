<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('invoices')->insert([
            'logo' => 'invoice_logos/1762151159_1751788462_LOGO-ALISAN (1).png',
            'bank_name' => 'BCA',
            'account_number' => '059-071-2647',
            'name' => 'Stefan Lewis',
            'phone' => '',
            'address' => 'Jl. Karya Indah No 32',
            'terms_and_conditions' => '',
            'created_at' => '2026-06-09 01:09:34',
            'updated_at' => '2026-06-09 01:09:34',
        ]);
    }
}
