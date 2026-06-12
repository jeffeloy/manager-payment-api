<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => (float) $this->exchange_rate,
            'exchange_rate_source' => $this->exchange_rate_source,
            'exchange_rate_fetched_at' => $this->exchange_rate_fetched_at?->toISOString(),
            'amount_eur' => (float) $this->amount_eur,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => UserResource::make($this->whenLoaded('user')),
            'reviewed_by' => UserResource::make($this->whenLoaded('reviewer')),
        ];
    }
}
