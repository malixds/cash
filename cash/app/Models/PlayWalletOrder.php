<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayWalletOrder extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'play_wallet_uuid',
        'status',
        'payload',
    ];
}
