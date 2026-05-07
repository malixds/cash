<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show(string $publicId): JsonResponse
    {
        $order = Order::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        return response()->json([
            'ok' => true,
            'data' => [
                'order_id' => $order->public_id,
                'status' => $order->status,
                'steam_login' => $order->steam_login,
                'amount_rub' => $order->amount_rub,
                'total_rub' => $order->total_rub,
                'error_message' => $order->error_message,
            ],
        ]);
    }
}

