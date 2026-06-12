<?php

namespace App\Models;

use App\Enums\PaymentRequestStatus;
use Database\Factories\PaymentRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    /** @use HasFactory<PaymentRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'currency',
        'exchange_rate',
        'exchange_rate_source',
        'exchange_rate_fetched_at',
        'amount_eur',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'exchange_rate' => 'decimal:8',
            'amount_eur' => 'decimal:4',
            'exchange_rate_fetched_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => PaymentRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
