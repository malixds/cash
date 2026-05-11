<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayWalletOrder extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'status',
        'payload',
        'created_at',
        'updated_at',
    ];
}
