<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'Dashboard', 'slug' => 'dashboard'],
            ['name' => 'Products Module', 'slug' => 'products'],
            ['name' => 'Discounts Module', 'slug' => 'discounts'],
            ['name' => 'Inventory Module', 'slug' => 'inventory'],
            ['name' => 'Sales Module', 'slug' => 'sales'],
            ['name' => 'Production Module', 'slug' => 'production'],
            ['name' => 'Delivery Module', 'slug' => 'delivery'],
            ['name' => 'Purchases Module', 'slug' => 'purchases'],
            ['name' => 'Warehouse Module', 'slug' => 'warehouse'],
            ['name' => 'Expenses Module', 'slug' => 'expenses'],
            ['name' => 'Capital Transaction Module', 'slug' => 'capital-transaction'],
            ['name' => 'Accounts Module', 'slug' => 'accounts'],
            ['name' => 'Financial Report', 'slug' => 'financial-report'],
            ['name' => 'Shop Manager', 'slug' => 'shop-manager'],
            ['name' => 'Customer Module', 'slug' => 'customer'],
            ['name' => 'Supplier Module', 'slug' => 'supplier'],
            ['name' => 'Invoice Module', 'slug' => 'invoice'],
            ['name' => 'Operator Module', 'slug' => 'operator'],
            ['name' => 'Machine Module', 'slug' => 'machine'],
            ['name' => 'Adjustment Module', 'slug' => 'adjustment'],
            ['name' => 'Design Module', 'slug' => 'design'],
            ['name' => 'Settings', 'slug' => 'settings'],
            ['name' => 'HPP FIFO Module', 'slug' => 'fifo-cost'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
