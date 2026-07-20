<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'public_id',
        'external_id',
        'steam_login',
        'region',
        'amount',
        'total',
        'promo_code',
        'payment_method',
        'status',
        'error_message',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'total' => 'integer',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function playWalletOrder(): HasOne
    {
        return $this->hasOne(PlayWalletOrder::class);
    }
}

