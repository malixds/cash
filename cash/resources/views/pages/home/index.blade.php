@extends('default.layout')

@section('title', 'Пополнение Steam кошелька РФ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('topupForm');
            const amountInput = document.getElementById('amount');
            const totalAmount = document.getElementById('totalAmount');
            const submitButton = document.querySelector('.payment-submit');
            const statusBox = document.getElementById('orderStatus');
            const statusText = document.getElementById('orderStatusText');

            const getSelectedPaymentMethod = () => {
                const selected = document.querySelector('input[name="payment_method"]:checked');
                return selected ? selected.value : 'sbp';
            };

            const setButtonState = (isLoading) => {
                if (!submitButton) return;
                submitButton.disabled = isLoading;
                submitButton.textContent = isLoading ? 'Создаём платёж...' : 'Перейти к оплате';
            };

            const showStatus = (message, tone = 'info') => {
                if (!statusBox || !statusText) return;
                statusBox.hidden = false;
                statusBox.dataset.tone = tone;
                statusText.textContent = message;
            };

            const validationMessage = (json) => {
                if (json?.message) return json.message;
                const errors = json?.errors;
                if (errors && typeof errors === 'object') {
                    const first = Object.values(errors)[0];
                    if (Array.isArray(first) && first[0]) return first[0];
                }
                return 'Не удалось создать платёж.';
            };

            if (!amountInput || !totalAmount) return;

            const updateTotal = () => {
                const amount = Number(amountInput.value) || 0;
                const total = amount > 0 ? amount * 1.05 : 0;
                totalAmount.textContent = `${Math.ceil(total).toLocaleString('ru-RU')} ₽`;
            };

            amountInput.addEventListener('input', updateTotal);
            updateTotal();

            if (form) {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const formData = new FormData(form);
                    const payload = {
                        login: String(formData.get('login') || ''),
                        amount: Number(formData.get('amount') || 0),
                        promo: String(formData.get('promo') || ''),
                        payment_method: getSelectedPaymentMethod(),
                    };

                    try {
                        setButtonState(true);
                        const response = await fetch('/api/orders/create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        let json = null;
                        try {
                            json = await response.json();
                        } catch (_) {
                            throw new Error('Сервер вернул неожиданный ответ.');
                        }

                        if (!response.ok || !json?.ok) {
                            throw new Error(validationMessage(json));
                        }

                        window.location.href = json.data.payment_url;
                    } catch (error) {
                        showStatus(error.message || 'Ошибка при создании платежа', 'error');
                    } finally {
                        setButtonState(false);
                    }
                });
            }

            const orderFromQuery = new URLSearchParams(window.location.search).get('order');
            if (!orderFromQuery || !statusBox) return;

            const terminal = new Set(['completed', 'error', 'canceled']);
            let attempts = 0;
            const maxAttempts = 20;

            const pollStatus = async () => {
                attempts += 1;
                try {
                    const response = await fetch(`/api/orders/${orderFromQuery}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await response.json();
                    if (!response.ok || !json?.ok) {
                        showStatus('Не удалось получить статус заказа.', 'error');
                        return;
                    }

                    const status = json?.data?.status || 'unknown';
                    if (status === 'completed') {
                        showStatus('Заказ успешно выполнен. Средства скоро появятся в Steam.', 'success');
                        return;
                    }
                    if (status === 'error') {
                        showStatus(`Ошибка выполнения заказа: ${json?.data?.error_message || 'неизвестно'}`, 'error');
                        return;
                    }
                    if (status === 'canceled') {
                        showStatus('Оплата отменена.', 'error');
                        return;
                    }

                    showStatus(`Заказ обрабатывается. Статус: ${status}`, 'info');
                    if (attempts < maxAttempts && !terminal.has(status)) {
                        setTimeout(pollStatus, 2500);
                    }
                } catch (_) {
                    showStatus('Не удалось получить статус заказа.', 'error');
                }
            };

            showStatus('Проверяем статус оплаты...', 'info');
            pollStatus();
        })();
    </script>
@endpush

@section('content')
    <main class="container">
        <div id="orderStatus" class="order-status" hidden role="status" aria-live="polite">
            <p id="orderStatusText"></p>
        </div>
        @include('includes.home.hero')
        @include('includes.home.plans')
        @include('includes.home.advantages')
        @include('includes.home.faq')
    </main>
@endsection
