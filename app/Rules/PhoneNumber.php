<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A guest phone number in local (e.g. 077 123 4567) or international
 * (e.g. +44 7911 123456, 0049 151 2345678) form: only digits and common
 * separators, with 7-15 digits in total (the E.164 maximum).
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (! preg_match('/^\+?[0-9\s\-().]+$/', $value)) {
            $fail('The :attribute may only contain digits, spaces, dashes, brackets and a leading +.');

            return;
        }

        $digits = strlen(preg_replace('/\D/', '', $value));

        if ($digits < 7 || $digits > 15) {
            $fail('The :attribute must be a valid phone number, including the country code for international numbers (e.g. +44 7911 123456).');
        }
    }
}
