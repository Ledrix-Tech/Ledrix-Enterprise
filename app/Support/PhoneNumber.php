<?php

namespace App\Support;

/**
 * Normalize user-entered phones to E.164 (+country + national digits).
 */
class PhoneNumber
{
    /** @var array<string, string> ISO2 => dial code digits (no +) */
    private const DIAL_BY_COUNTRY = [
        'PK' => '92',
        'US' => '1',
        'CA' => '1',
        'GB' => '44',
        'IN' => '91',
        'AE' => '971',
        'SA' => '966',
    ];

    public static function normalize(?string $value, ?string $countryIso = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        // Keep leading +; drop spaces, dashes, parentheses, dots.
        $phone = preg_replace('/[^\d+]/', '', $raw) ?? '';
        $phone = preg_replace('/\++/', '+', $phone) ?? $phone;

        if ($phone === '' || $phone === '+') {
            return null;
        }

        $country = strtoupper(trim((string) $countryIso));
        $dial = self::DIAL_BY_COUNTRY[$country] ?? null;

        if (! str_starts_with($phone, '+')) {
            $national = ltrim($phone, '0');
            if ($national === '') {
                return null;
            }
            if ($dial) {
                return '+'.$dial.$national;
            }
            if (ctype_digit($phone)) {
                return '+'.$phone;
            }

            return null;
        }

        // "+0300…" / "+092…" → drop trunk zeros after +
        if (preg_match('/^\+0+(\d+)$/', $phone, $m)) {
            $rest = $m[1];
            if ($dial && ! str_starts_with($rest, $dial)) {
                return '+'.$dial.ltrim($rest, '0');
            }

            return '+'.$rest;
        }

        // National pasted with country already selected: "+92300…" is fine.
        // If user typed dial code twice after a failed old() reload, leave E164 rule to reject.
        return $phone;
    }

    public static function isValidE164(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $value);
    }
}
