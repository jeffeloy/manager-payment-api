<?php

namespace App\Http\Requests\PaymentRequest;

use App\Enums\PaymentRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPaymentRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::enum(PaymentRequestStatus::class)],
        ];
    }
}
