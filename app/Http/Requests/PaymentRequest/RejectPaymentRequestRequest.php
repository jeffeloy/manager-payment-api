<?php

namespace App\Http\Requests\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFinance() ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
