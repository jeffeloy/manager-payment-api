<?php

namespace App\Http\Requests\Auth;

use App\Rules\CurrencyMatchesCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'country' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/', Rule::in(array_keys(config('countries')))],
            'currency' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                new CurrencyMatchesCountry($this->input('country')),
            ],
            'role' => ['sometimes', 'string', Rule::in(['employee'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country' => strtoupper((string) $this->country),
            'currency' => strtoupper((string) $this->currency),
        ]);
    }
}
