<?php

namespace App\Support;

class PhoneNormalizer
{
    /**
     * Normalisasi nomor HP Indonesia ke format 08xxxxxxxxxx.
     *
     * Menerima +62, 62, spasi, titik, tanda hubung, dan kurung.
     * Mengembalikan null jika nomor tidak wajar.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '620')) {
            $digits = '62'.substr($digits, 3);
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        if (preg_match('/^08[0-9]{8,11}$/', $digits) !== 1) {
            return null;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }
}
