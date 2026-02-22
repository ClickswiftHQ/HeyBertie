<?php

namespace App\Support;

class PostcodeFormatter
{
    /**
     * Normalise a UK postcode to standard format: uppercase with single space before inward code.
     *
     * Examples: "gu166hd" -> "GU16 6HD", "sw1a 1aa" -> "SW1A 1AA", "M1  1AE" -> "M1 1AE"
     */
    public static function format(string $postcode): string
    {
        $cleaned = strtoupper(preg_replace('/\s+/', '', trim($postcode)));

        if (strlen($cleaned) < 5) {
            return $cleaned;
        }

        // Inward code is always the last 3 characters (digit + 2 letters)
        $outward = substr($cleaned, 0, -3);
        $inward = substr($cleaned, -3);

        return $outward.' '.$inward;
    }

    /**
     * Validate a UK postcode format.
     */
    public static function isValid(string $postcode): bool
    {
        $cleaned = preg_replace('/\s+/', '', trim($postcode));

        return (bool) preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/i', $cleaned);
    }
}
