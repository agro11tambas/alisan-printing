<?php

namespace Database\Seeders;

use App\Models\InventoryWarehouse;
use App\Models\ProductionWarehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionWarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductionWarehouse::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'Gudang Produksi Utama',
                'location' => 'Kantor Pusat',
            ]
        );

        InventoryWarehouse::firstOrCreate(
            [
                'name' => 'Gudang Inventory Utama',
                'location' => 'Kantor Pusat',
            ]
        );
    }
}
