<?php

namespace App\Http\Controllers;

use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Jobs\PlayWallets\PlayWalletPaymentJob;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

class MockPaymentPageController extends Controller
{
    public function show(string $orderPublicId, string $paymentId)
    {
        $order = Order::query()->where('public_id', $orderPublicId)->firstOrFail();
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider_payment_id', $paymentId)
            ->firstOrFail();

        return response()->view('pages.mock-payment', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    public function complete(
        string $orderPublicId,
        string $paymentId,
    ): RedirectResponse {
        $order = Order::query()->where('public_id', $orderPublicId)->firstOrFail();
        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('provider_payment_id', $paymentId)
            ->firstOrFail();

        if (! in_array($order->status, [
            OrderStatusEnum::PROCESSING->value,
            OrderStatusEnum::COMPLETED->value,
        ], true)) {
            $serviceId = (string) config('services.playwallet.service_id', '');
            if ($serviceId === '') {
                abort(500, 'PLAYWALLET_SERVICE_ID is not configured.');
            }

            $payment->update([
                'status' => PaymentsStatusEnum::SUCCEEDED->value,
                'paid_at' => now(),
            ]);
            $order->update([
                'status' => OrderStatusEnum::PROCESSING->value,
                'paid_at' => now(),
            ]);

            PlayWalletPaymentJob::dispatch(new PlayWalletCreateDTO(
                orderId: $order->id,
                externalOrderId: $order->external_id,
                serviceId: $serviceId,
                login: $order->steam_login,
                amount: $order->amount,
            ));
        }

        return redirect('/?order='.$order->public_id);
    }
}

