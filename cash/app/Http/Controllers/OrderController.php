<?php

namespace App\Http\Controllers;

use App\DTO\OrderDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Providers\MockPaymentProvider;
use App\Providers\YookassaPaymentProvider;
use App\Services\PlayWallet\PlayWalletClientServiceDev;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Create a pending order and mock payment, return URL for the payment page.
     *
     */
    public function createOrder(OrderCreateRequest $request, YookassaPaymentProvider $paymentProvider): JsonResponse
    {
        $validated = $request->validated();
        $dto = OrderDTO::fromValidated($validated);
        $paymentUrl = DB::transaction(function () use ($dto, $paymentProvider): string {
            $order = Order::query()->create([
                'status' => OrderStatusEnum::NEW->value,
                ...$dto->toOrderAttributes()
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

            return $result->paymentUrl;
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'payment_url' => $paymentUrl,
            ],
        ]);
    }


    public function orderStatus(string $id, PlayWalletClientServiceDev $client): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $client->getOrder($id),
        ]);
    }

    public function orderList(Request $request, PlayWalletClientServiceDev $client): JsonResponse
    {
        $offset = (int)$request->integer('offset', 0);
        $limit = (int)$request->integer('limit', 10);

        return response()->json([
            'ok' => true,
            'data' => $client->getOrderList($offset, $limit),
        ]);
    }
}

