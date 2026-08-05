<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('google_maps')->index();
        });

        $defaultAddressIds = DB::table('customer_addresses')
            ->selectRaw('MIN(id) as id')
            ->whereNull('deleted_at')
            ->groupBy('customer_id')
            ->pluck('id');

        if ($defaultAddressIds->isNotEmpty()) {
            DB::table('customer_addresses')
                ->whereIn('id', $defaultAddressIds)
                ->update(['is_default' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropIndex(['is_default']);
            $table->dropColumn('is_default');
        });
    }
};
