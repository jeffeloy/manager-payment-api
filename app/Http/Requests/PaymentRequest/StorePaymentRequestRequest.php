<?php

namespace App\Http\Requests\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployee() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $userCurrency = $this->user()?->currency;

                if ($userCurrency !== null && $this->currency !== $userCurrency) {
                    $validator->errors()->add(
                        'currency',
                        "The currency must match your local currency [{$userCurrency}]."
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge([
                'currency' => strtoupper((string) $this->currency),
            ]);
        }
    }
}
