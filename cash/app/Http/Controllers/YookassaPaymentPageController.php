<?php

namespace App\Http\Controllers;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Http\Requests\YookassaWebhookRequest;
use App\Interfaces\Payments\PaymentProviderInterface;
use App\Jobs\PlayWallets\PlayWalletPaymentJob;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class YookassaPaymentPageController extends Controller
{
    public function webhook(
        YookassaWebhookRequest $request,
        PaymentProviderInterface $paymentProvider,
    ): JsonResponse
    {
        $payload = $request->all();

        if (! $paymentProvider->verifyWebhook($payload, (string) $request->header('X-Payment-Signature', ''))) {
            return response()->json([
                'ok' => false,
                'message' => 'Webhook verification failed.',
            ], 401);
        }

        $dto = $request->toDto();
        $payment = Payment::query()
            ->with('order')
            ->where('provider_payment_id', $dto->id())
            ->first();

        if ($payment === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        if (number_format($dto->amount(), 2, '.', '') !== number_format((float) $payment->amount, 2, '.', '')) {
            return response()->json([
                'ok' => false,
                'message' => 'Payment amount mismatch.',
            ], 422);
        }

        $jobDto = DB::transaction(function () use ($dto, $payload, $payment): ?PlayWalletCreateDTO {
            $lockedPayment = Payment::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $order = $lockedPayment->order;

            $lockedPayment->update([
                'status' => $dto->status(),
                'paid_at' => $dto->isPaid() ? now() : null,
                'provider_payload' => $payload,
            ]);

            if ($dto->status() === PaymentsStatusEnum::CANCELED->value) {
                $order->update(['status' => OrderStatusEnum::CANCELED->value]);

                return null;
            }

            if ($dto->status() !== PaymentsStatusEnum::SUCCEEDED->value || ! $dto->isPaid()) {
                return null;
            }

            if (in_array($order->status, [
                OrderStatusEnum::PROCESSING->value,
                OrderStatusEnum::COMPLETED->value,
            ], true)) {
                return null;
            }

            $serviceId = (string) config('services.playwallet.service_id', '');
            if ($serviceId === '') {
                throw new \RuntimeException('PLAYWALLET_SERVICE_ID is not configured.');
            }

            $order->update([
                'status' => OrderStatusEnum::PROCESSING->value,
                'paid_at' => now(),
                'error_message' => null,
            ]);

            return new PlayWalletCreateDTO(
                orderId: $order->id,
                externalOrderId: $order->external_id,
                serviceId: $serviceId,
                login: $order->steam_login,
                amount: $order->amount,
            );
        });

        if ($jobDto !== null) {
            PlayWalletPaymentJob::dispatch($jobDto);
        }

        return response()->json([
            'ok' => true,
            'message' => $jobDto === null ? 'Webhook already processed.' : 'Order queued for fulfillment.',
        ]);
    }
}
