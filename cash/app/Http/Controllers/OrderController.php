<?php

namespace App\Http\Controllers;

use App\DTO\OrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new order and payment, then return the provider payment URL.
     */
    public function createOrder(OrderCreateRequest $request, PaymentProviderInterface $paymentProvider): JsonResponse
    {
        $validated = $request->validated();
        $dto = OrderDTO::fromValidated($validated);
        $created = DB::transaction(function () use ($dto, $paymentProvider): array {
            $order = Order::query()->create([
                ...$dto->toOrderAttributes(),
                'status' => OrderStatusEnum::NEW->value,
            ]);
            [$result, $payload] = $paymentProvider->createPayment($order);

            Payment::query()->create([
                'order_id' => $order->id,
                'provider_payment_id' => $result->providerPaymentId,
                'status' => PaymentsStatusEnum::NEW->value,
                'amount' => $order->total,
                'currency' => 'RUB',
                'payment_url' => $result->paymentUrl,
                'provider_payload' => $payload,
            ]);

            return [
                'payment_url' => $result->paymentUrl,
                'order_id' => $order->public_id,
            ];
        });

        return response()->json([
            'ok' => true,
            'data' => $created,
        ]);
    }


    public function orderStatus(string $id): JsonResponse
    {
        $order = Order::query()->with(['payment', 'playWalletOrder'])->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => $order,
        ]);
    }

    public function orderList(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 20), 1), 100);

        return response()->json([
            'ok' => true,
            'data' => Order::query()
                ->with(['payment', 'playWalletOrder'])
                ->latest('id')
                ->paginate($limit),
        ]);
    }
}

