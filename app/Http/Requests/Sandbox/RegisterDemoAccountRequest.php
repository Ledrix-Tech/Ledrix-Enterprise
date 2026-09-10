<?php

namespace App\Http\Requests\Sandbox;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDemoAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        $emailRule = app()->environment('production') ? 'email:rfc,dns' : 'email:rfc';

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                $emailRule,
                'max:255',
                Rule::unique('central.demo_accounts', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email already has a sandbox login. Sign in below.',
        ];
    }
}
