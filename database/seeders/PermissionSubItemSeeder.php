<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\PermissionSubItem;

class PermissionSubItemSeeder extends Seeder
{
    public function run(): void
    {
        $subItems = [
            // Products Module
            'products' => [
                ['name' => 'Produk', 'slug' => 'product-list'],
                ['name' => 'Produk Bundle', 'slug' => 'product-bundles'],
                ['name' => 'Kategori Produk', 'slug' => 'product-categories'],
                ['name' => 'Merek Produk', 'slug' => 'product-tags'],
            ],

            // Inventory Module
            'inventory' => [
                ['name' => 'Opening Stock & Rate', 'slug' => 'opening-stock-rate'],
                ['name' => 'Opening Stock Production', 'slug' => 'opening-stock-production'],
                ['name' => 'Stock Opname', 'slug' => 'stock-opname'],
                ['name' => 'Stock Opname Production', 'slug' => 'stock-opname-production'],
            ],

            // Sales Module
            'sales' => [
                ['name' => 'Sale Orders (Draft)', 'slug' => 'sale-orders'],
                ['name' => 'Sale List', 'slug' => 'sale-list'],
                ['name' => 'Sale Return', 'slug' => 'sale-returns'],
            ],

            // Production Module
            'production' => [
                ['name' => 'Waiting List', 'slug' => 'waiting-list'],
                ['name' => 'Request Stocks', 'slug' => 'request-stocks'],
                ['name' => 'Report Items', 'slug' => 'report-items'],
                ['name' => 'Canceled Products', 'slug' => 'canceled-products'],
                ['name' => 'Assign List', 'slug' => 'assign-list'],
            ],

            // Delivery Module
            'delivery' => [
                ['name' => 'Delivery Orders', 'slug' => 'delivery-orders'],
                ['name' => 'Delivery List', 'slug' => 'delivery-list'],
            ],

            // Purchases Module
            'purchases' => [
                ['name' => 'Purchase Orders', 'slug' => 'purchase-orders'],
                ['name' => 'Purchase List', 'slug' => 'purchase-list'],
                ['name' => 'Purchase Return', 'slug' => 'purchase-returns'],
            ],

            // Warehouse Module
            'warehouse' => [
                ['name' => 'Stock In', 'slug' => 'stock-in'],
                ['name' => 'Stock Out', 'slug' => 'stock-out'],
                ['name' => 'Report Items', 'slug' => 'warehouse-report-items'],
            ],

            // Accounts Module
            'accounts' => [
                ['name' => 'Manage Accounts', 'slug' => 'manage-accounts'],
                ['name' => 'Account List - Bank', 'slug' => 'account-bank'],
                ['name' => 'Account List - Cash', 'slug' => 'account-cash'],
                ['name' => 'Account List - Sale', 'slug' => 'account-sale'],
                ['name' => 'Account List - Purchase', 'slug' => 'account-purchase'],
                ['name' => 'Account List - Expense', 'slug' => 'account-expense'],
                ['name' => 'Account List - Capital', 'slug' => 'account-capital'],
                ['name' => 'Manage Opening Balance', 'slug' => 'manage-opening-balance'],
            ],

            // Adjustment Module
            'adjustment' => [
                ['name' => 'Canceled', 'slug' => 'canceled'],
                ['name' => 'Defect', 'slug' => 'defect'],
                ['name' => 'Reject', 'slug' => 'reject'],
            ],
        ];

        foreach ($subItems as $parentSlug => $items) {
            $parent = Permission::where('slug', $parentSlug)->first();

            if ($parent) {
                foreach ($items as $item) {
                    PermissionSubItem::firstOrCreate(
                        [
                            'slug' => $item['slug'],
                            'permission_id' => $parent->id,
                        ],
                        [
                            'name' => $item['name'],
                            'permission_id' => $parent->id,
                        ]
                    );
                }
            }
        }
    }
}
