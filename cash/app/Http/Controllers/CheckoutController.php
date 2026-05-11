<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

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
                'amount' => $order->amount,
                'total' => $order->total,
                'error_message' => $order->error_message,
            ],
        ]);
    }
}

