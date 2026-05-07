<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Orders\OrderProcessor;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderWebhookController extends Controller
{
    public function mockPaid(
        Request $request,
        MockPaymentProvider $provider,
        OrderProcessor $processor,
    ): JsonResponse {
        $signature = (string) $request->header('X-Payment-Signature', '');
        $payload = $request->all();

        if (! $provider->verifyWebhook($payload, $signature)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $payment = Payment::query()
            ->where('provider_payment_id', (string) ($payload['payment_id'] ?? ''))
            ->first();

        if (! $payment) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        if (($payload['status'] ?? null) !== 'paid') {
            return response()->json([
                'ok' => false,
                'message' => 'Unsupported payment status.',
            ], 422);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'provider_payload' => $payload,
        ]);

        $order = $payment->order;
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $processor->processPaidOrder($order);

        return response()->json([
            'ok' => true,
            'data' => [
                'order_id' => $order->public_id,
                'order_status' => $order->fresh()->status,
            ],
        ]);
    }
}

