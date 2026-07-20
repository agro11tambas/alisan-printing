<?php

namespace App\Services;

use App\Models\ProductUnitConversion;

class UnitConversionService
{
    /**
     * Resolve the normalized multiplier from a selected unit to the product base unit.
     *
     * Client-facing ratio_value may be reversed (e.g. Dus=1, Pack=10, Pcs=1000),
     * while conversion_value is normalized when the product is saved
     * (Dus=1000, Pack=100, Pcs=1). Stock calculations must always use the latter.
     */
    public static function resolve(int $productId, mixed $conversionId, string $fallbackUnit = 'Pcs'): array
    {
        if (!is_numeric($conversionId)) {
            return [
                'id' => null,
                'unit_name' => $fallbackUnit,
                'factor' => 1.0,
            ];
        }

        $conversion = ProductUnitConversion::with('unit')
            ->where('product_id', $productId)
            ->findOrFail((int) $conversionId);

        return [
            'id' => $conversion->id,
            'unit_name' => $conversion->unit->name ?? $fallbackUnit,
            'factor' => max(0.0001, (float) $conversion->conversion_value),
        ];
    }
}
