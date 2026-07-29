<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalizeIndonesian(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function equivalentIndonesianFormats(?string $value): array
    {
        $normalized = self::normalizeIndonesian($value);

        if (! $normalized) {
            return [];
        }

        $formats = [$normalized];

        if (str_starts_with($normalized, '62')) {
            $subscriberNumber = substr($normalized, 2);
            $formats[] = '0'.$subscriberNumber;
            $formats[] = $subscriberNumber;
            $formats[] = '+'.$normalized;
        }

        return array_values(array_unique($formats));
    }
}