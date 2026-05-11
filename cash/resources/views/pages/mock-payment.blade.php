<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Оплата заказа {{ $order->public_id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 32px; background: #0f1535; color: #fff; }
        .card { max-width: 560px; margin: 0 auto; background: #1a2149; border-radius: 14px; padding: 24px; }
        .muted { color: #b9c2ea; font-size: 14px; }
        .amount { font-size: 32px; margin: 10px 0; }
        button { background: #be2d67; color: #fff; border: 0; border-radius: 10px; padding: 12px 16px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h1>Мок-страница оплаты</h1>
    <p class="muted">Имитация платёжного провайдера для теста сценария.</p>
    <p>Заказ: <strong>{{ $order->public_id }}</strong></p>
    <p>Steam логин: <strong>{{ $order->steam_login }}</strong></p>
    <p class="amount">{{ number_format($order->total, 0, ',', ' ') }} ₽</p>

    <form method="post" action="{{ route('mock.payments.complete', ['orderPublicId' => $order->public_id, 'paymentId' => $payment->provider_payment_id]) }}">
        @csrf
        <button type="submit">Оплатить (симуляция)</button>
    </form>
</div>
</body>
</html>

