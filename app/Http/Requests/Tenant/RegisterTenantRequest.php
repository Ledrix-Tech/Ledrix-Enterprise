<?php

namespace App\Http\Requests\Tenant;

use App\Rules\E164Phone;
use App\Rules\NotDisposableEmail;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $country = $this->input('country');

        foreach (['phone', 'billing_phone'] as $field) {
            if ($this->filled($field)) {
                $normalized = PhoneNumber::normalize(
                    (string) $this->input($field),
                    is_string($country) ? $country : null
                );
                $this->merge([$field => $normalized]);
            } else {
                $this->merge([$field => null]);
            }
        }

        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
        if ($this->filled('billing_email')) {
            $this->merge(['billing_email' => strtolower(trim((string) $this->input('billing_email')))]);
        }
    }

    public function rules(): array
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns' : 'email:rfc';

        return [
            'pkg_slug'        => ['required', 'string', Rule::exists('central.package_pricings', 'slug')->where('status', 'active')],
            'name'            => ['required', 'string', 'max:255'],
            'email'           => [
                'required',
                $emailRule,
                'max:255',
                Rule::unique('central.tenants', 'email'),
                new NotDisposableEmail,
            ],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'phone'           => ['required', 'string', 'max:20', new E164Phone(required: true)],
            'address'         => ['nullable', 'string', 'max:500'],
            'website'         => ['nullable', 'url', 'max:255'],
            'country'         => ['required', 'string', 'max:5'],
            'billing_name'    => ['required', 'string', 'max:255'],
            'billing_email'   => [
                'required',
                $emailRule,
                'max:255',
                new NotDisposableEmail,
            ],
            'billing_phone'   => ['nullable', 'string', 'max:20', new E164Phone],
            'billing_address' => ['required', 'string', 'max:500'],
            'billing_cycle'   => ['nullable', Rule::in(['monthly', 'yearly'])],
            'referral_code'   => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'pkg_slug.exists' => 'The selected plan is not available.',
            'email.unique'    => 'An account with this email already exists.',
            'email.email'     => 'Please enter a valid work email with a real domain.',
            'billing_email.email' => 'Please enter a valid billing email with a real domain.',
            'phone.required'  => 'Phone number with country code is required.',
        ];
    }
}
