<?php

namespace App\Http\Controllers;


use App\DTO\PlayWallets\PlayWalletCreateDTO;
use App\Enums\Payment\PaymentsStatusEnum;
use App\Enums\PlayWalletEnums\ResponseEnum;
use App\Http\Requests\YookassaWebhookRequest;
use App\Interfaces\Orders\IOrderRepository;
use App\Interfaces\Payments\IPaymentRepository;
use App\Interfaces\PlayWallets\IPlayWalletRepository;
use App\Jobs\PlayWallets\PlayWalletPaymentJob;


class YookassaPaymentPageController extends Controller
{

    public function __construct(
        private readonly IPaymentRepository    $paymentRepository,
        private readonly IPlayWalletRepository $playWalletRepository,
    )
    {
    }

    public function webhook(YookassaWebhookRequest $request)
    {
        $dto = $request->toDto();
        if (empty($dto->id())) {
            // TODO: логировать
        }
        $payment = $this->paymentRepository->findByPaymentId($dto->id());
        $payment = $this->paymentRepository->update($payment, $dto->toArray());
        if ($payment->status === PaymentsStatusEnum::SUCCEEDED->value) {
            $order = $payment->order()->first();
            if (!isset($order)) {
                return response()->json([
                    'message' => 'Order not found',
                ])->setStatusCode(code: 404);
            }
            $playWalletOrder = $order->playWalletOrder;

            if ($playWalletOrder !== null && $playWalletOrder->status !== ResponseEnum::ERROR->value) {
                return response()->json(['ok' => true, 'message' => 'Already processing or done']);
            }
            
            // иначе — dispatch
            $playWalletCreateDTO = new PlayWalletCreateDTO(
                orderId: $order->id,
                externalOrderId: $order->external_id,
                serviceId: config('services.playwallet.service_id'),
                login: "123456789", // DEV: 123456789
                amount: $order->amount,
            );
            PlayWalletPaymentJob::dispatch(dto: $playWalletCreateDTO);
        }

        return response()->json([
            'message' => 'Order is processing , please wait',
            'ok' => true,
        ]);
    }
}
