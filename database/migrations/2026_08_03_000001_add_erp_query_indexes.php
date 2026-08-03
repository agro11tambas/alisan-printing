<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('orders', ['status', 'deleted_at', 'order_date'], 'orders_status_deleted_date_idx');
        $this->addIndex('orders', ['status', 'payment_status', 'deleted_at'], 'orders_status_payment_deleted_idx');

        $this->addIndex('purchases', ['status', 'deleted_at', 'purchase_date'], 'purchases_status_deleted_date_idx');
        $this->addIndex('purchases', ['status', 'payment_status', 'deleted_at'], 'purchases_status_payment_deleted_idx');

        $this->addIndex('sale_returns', ['status', 'deleted_at', 'return_date'], 'sale_returns_status_deleted_date_idx');
        $this->addIndex('sale_returns', ['status', 'payment_status', 'deleted_at'], 'sale_returns_status_payment_deleted_idx');

        $this->addIndex('purchase_returns', ['status', 'deleted_at', 'return_date'], 'purchase_returns_status_deleted_date_idx');
        $this->addIndex('purchase_returns', ['status', 'payment_status', 'deleted_at'], 'purchase_returns_status_payment_deleted_idx');

        $this->addIndex('delivery_lists', ['deleted_at', 'shipment_date'], 'delivery_lists_deleted_shipment_idx');
        $this->addIndex('delivery_lists', ['status', 'deleted_at'], 'delivery_lists_status_deleted_idx');

        $this->addIndex('account_transactions', ['deleted_at', 'transaction_date'], 'account_transactions_deleted_date_idx');

        $this->addIndex('order_progress_items', ['product_id', 'deleted_at'], 'progress_items_product_deleted_idx');
        $this->addIndex('delivery_order_items', ['product_id', 'deleted_at'], 'delivery_order_items_product_deleted_idx');
        $this->addIndex('design_items', ['product_id', 'deleted_at'], 'design_items_product_deleted_idx');
        $this->addIndex('order_progress_assigns', ['product_id', 'deleted_at', 'created_at'], 'progress_assigns_product_deleted_date_idx');
        $this->addIndex('inventory_items_2', ['product_id', 'deleted_at', 'created_at'], 'inventory_items_product_deleted_date_idx');
        $this->addIndex('material_request_items', ['product_id', 'deleted_at', 'created_at'], 'material_items_product_deleted_date_idx');
        $this->addIndex('delivery_list_items', ['product_id', 'deleted_at', 'created_at'], 'delivery_list_items_product_deleted_date_idx');
        $this->addIndex('canceled_products', ['sale_return_id', 'deleted_at'], 'canceled_products_return_deleted_idx');
        $this->addIndex('financial_reports', ['date', 'transaction_type'], 'financial_reports_date_type_idx');
    }

    public function down(): void
    {
        $this->dropIndex('orders', 'orders_status_deleted_date_idx');
        $this->dropIndex('orders', 'orders_status_payment_deleted_idx');
        $this->dropIndex('purchases', 'purchases_status_deleted_date_idx');
        $this->dropIndex('purchases', 'purchases_status_payment_deleted_idx');
        $this->dropIndex('sale_returns', 'sale_returns_status_deleted_date_idx');
        $this->dropIndex('sale_returns', 'sale_returns_status_payment_deleted_idx');
        $this->dropIndex('purchase_returns', 'purchase_returns_status_deleted_date_idx');
        $this->dropIndex('purchase_returns', 'purchase_returns_status_payment_deleted_idx');
        $this->dropIndex('delivery_lists', 'delivery_lists_deleted_shipment_idx');
        $this->dropIndex('delivery_lists', 'delivery_lists_status_deleted_idx');
        $this->dropIndex('account_transactions', 'account_transactions_deleted_date_idx');
        $this->dropIndex('order_progress_items', 'progress_items_product_deleted_idx');
        $this->dropIndex('delivery_order_items', 'delivery_order_items_product_deleted_idx');
        $this->dropIndex('design_items', 'design_items_product_deleted_idx');
        $this->dropIndex('order_progress_assigns', 'progress_assigns_product_deleted_date_idx');
        $this->dropIndex('inventory_items_2', 'inventory_items_product_deleted_date_idx');
        $this->dropIndex('material_request_items', 'material_items_product_deleted_date_idx');
        $this->dropIndex('delivery_list_items', 'delivery_list_items_product_deleted_date_idx');
        $this->dropIndex('canceled_products', 'canceled_products_return_deleted_idx');
        $this->dropIndex('financial_reports', 'financial_reports_date_type_idx');
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumns($table, $columns)
            || Schema::hasIndex($table, $name)
        ) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }
};
