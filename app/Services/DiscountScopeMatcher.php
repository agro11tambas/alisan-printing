<?php

namespace App\Services;

use App\Models\Discount;

/**
 * Penentu apakah satu baris order masuk sasaran sebuah diskon.
 *
 * Sejak "Apply On" boleh lebih dari satu, aturannya AND: baris harus cocok
 * dengan semua scope yang dipilih. Contohnya diskon "Category + Mode" hanya
 * kena pada baris yang produknya ada di kategori terpilih DAN mode barisnya
 * ada di daftar mode terpilih.
 *
 * Scope yang datanya tidak tersedia di konteks pemanggil dilewati, bukan
 * dianggap gagal. Tapi kalau SEMUA scope-nya di luar jangkauan konteks, diskon
 * dianggap tidak berlaku — tanpa penjaga itu diskon tanpa syarat yang bisa
 * diuji akan bocor ke semua baris.
 */
class DiscountScopeMatcher
{
    /**
     * @param  array{product_id?: int|null, category_ids?: array<int>, mode?: string|null}  $line
     * @param  array<string>  $supportedScopes  Scope yang datanya tersedia di konteks ini.
     */
    public static function matches(Discount $discount, array $line, array $supportedScopes): bool
    {
        $scopes = self::evaluableScopes($discount, $supportedScopes);

        if (empty($scopes)) {
            return false;
        }

        foreach ($scopes as $scope) {
            if (! self::scopeMatches($discount, $scope, $line)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string>  $supportedScopes
     * @return array<string>
     */
    public static function evaluableScopes(Discount $discount, array $supportedScopes): array
    {
        return array_values(array_intersect($discount->apply_on_list, $supportedScopes));
    }

    private static function scopeMatches(Discount $discount, string $scope, array $line): bool
    {
        return match ($scope) {
            // "Product" tidak lagi bisa dipilih di form, tapi tetap dievaluasi
            // supaya diskon lama yang memakainya tidak berubah perilakunya.
            'Product' => $discount->products
                ->contains('id', (int) ($line['product_id'] ?? 0)),
            'Category' => $discount->categories
                ->pluck('id')
                ->intersect($line['category_ids'] ?? [])
                ->isNotEmpty(),
            'Mode' => ($line['mode'] ?? null) !== null
                && $discount->priceModes->pluck('slug')->contains($line['mode']),
            default => false,
        };
    }
}
