<?php

namespace Tests\Feature;

use App\Models\EcommerceVariantOption;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EcommerceVariantOptionWithoutLidTest extends TestCase
{
    public function test_variant_option_supports_allow_without_lid_flag(): void
    {
        Schema::create('ecommerce_variant_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $migration = require database_path(
            'migrations/2026_07_27_160000_add_allow_without_lid_to_ecommerce_variant_options_table.php'
        );
        $migration->up();

        $this->assertTrue(
            Schema::hasColumn('ecommerce_variant_options', 'allow_without_lid')
        );

        $option = new EcommerceVariantOption([
            'allow_without_lid' => 0,
        ]);

        $this->assertFalse($option->allow_without_lid);

        $option->allow_without_lid = 1;

        $this->assertTrue($option->allow_without_lid);
    }
}
