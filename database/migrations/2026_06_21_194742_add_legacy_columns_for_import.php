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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'woocommerce_customer_id')) {
                $table->unsignedBigInteger('woocommerce_customer_id')->nullable()->after('customer_deposit');
            }
        });

        Schema::table('inventory_items_2', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items_2', 'product_combination_id')) {
                $table->unsignedBigInteger('product_combination_id')->nullable()->after('product_bundle_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('terms_and_conditions')->nullable()->change();
        });

        Schema::table('material_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('material_requests', 'production_id')) {
                $table->unsignedBigInteger('production_id')->nullable()->after('material_request_number');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_image')) {
                $table->string('delivery_image')->nullable()->after('google_maps');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_combination_id')) {
                $table->unsignedBigInteger('product_combination_id')->nullable()->after('product_bundle_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'margin')) {
                $table->decimal('margin', 15, 3)->default(0)->after('opening_rate');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'parent')) {
                $table->unsignedBigInteger('parent')->default(0)->after('description');
            }
        });

        Schema::table('product_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('product_tags', 'parent')) {
                $table->unsignedBigInteger('parent')->default(0)->after('description');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'product_combination_id')) {
                $table->unsignedBigInteger('product_combination_id')->nullable()->after('production_warehouse_id');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'purchase_product_id')) {
                $table->unsignedBigInteger('purchase_product_id')->nullable()->after('product_combination_id');
            }
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'delete_notes')) {
                $table->text('delete_notes')->nullable()->after('note');
            }

            if (!Schema::hasColumn('sale_returns', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            }

            // if (!Schema::hasColumn('sale_returns', 'return_image')) {
            //     $table->string('return_image')->nullable()->after('google_map');
            // }

            // if (!Schema::hasColumn('sale_returns', 'status_edited')) {
            //     $table->boolean('status_edited')->default(false)->after('id');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'woocommerce_customer_id')) {
                $table->dropColumn('woocommerce_customer_id');
            }
        });

        Schema::table('inventory_items_2', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items_2', 'product_combination_id')) {
                $table->unsignedBigInteger('product_combination_id')->nullable()->after('product_bundle_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->text('terms_and_conditions')->nullable(false)->change();
        });

        Schema::table('material_requests', function (Blueprint $table) {
            if (Schema::hasColumn('material_requests', 'production_id')) {
                $table->dropColumn('production_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_image')) {
                $table->dropColumn('delivery_image');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_combination_id')) {
                $table->dropColumn('product_combination_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'margin')) {
                $table->dropColumn('margin');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('product_categories', 'parent')) {
                $table->dropColumn('parent');
            }
        });

        Schema::table('product_tags', function (Blueprint $table) {
            if (Schema::hasColumn('product_tags', 'parent')) {
                $table->dropColumn('parent');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'product_combination_id')) {
                $table->dropColumn('product_combination_id');
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'purchase_product_id')) {
                $table->dropColumn('purchase_product_id');
            }
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sale_returns', 'delete_notes')) {
                $table->dropColumn('delete_notes');
            }

            if (Schema::hasColumn('sale_returns', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }

            // if (Schema::hasColumn('sale_returns', 'return_image')) {
            //     $table->dropColumn('return_image');
            // }

            // if (Schema::hasColumn('sale_returns', 'status_edited')) {
            //     $table->dropColumn('status_edited');
            // }
        });
    }
};
