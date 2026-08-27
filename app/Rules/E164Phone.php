<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class E164Phone implements ValidationRule
{
    public function __construct(
        private readonly bool $required = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = trim((string) $value);

        if ($phone === '') {
            if ($this->required) {
                $fail('Please enter a valid phone number with country code.');
            }

            return;
        }

        if (! PhoneNumber::isValidE164($phone)) {
            $fail('Enter a valid mobile number with country selected (e.g. Pakistan: 300 1234567).');
        }
    }
}
