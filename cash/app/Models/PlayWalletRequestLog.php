<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayWalletRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'play_wallet_order_id',
        'method',
        'endpoint',
        'url',
        'request_body',
        'response_body',
        'http_status',
        'api_status',
        'api_message',
        'created_at',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function playWalletOrder(): BelongsTo
    {
        return $this->belongsTo(PlayWalletOrder::class);
    }
}
