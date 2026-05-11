<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider_payment_id',
        'status',
        'amount',
        'currency',
        'payment_url',
        'provider_payload',
        'paid_at',
    ];

    protected $casts = [
        'provider_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

