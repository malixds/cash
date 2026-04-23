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

            const getSelectedPaymentMethod = () => {
                const selected = document.querySelector('input[name="payment_method"]:checked');
                return selected ? selected.value : 'sbp';
            };

            const setButtonState = (isLoading) => {
                if (!submitButton) return;
                submitButton.disabled = isLoading;
                submitButton.textContent = isLoading ? 'Создаём платёж...' : 'Перейти к оплате';
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
                        nickname: String(formData.get('nickname') || ''),
                        amount: Number(formData.get('amount') || 0),
                        promo: String(formData.get('promo') || ''),
                        payment_method: getSelectedPaymentMethod(),
                    };

                    try {
                        setButtonState(true);
                        const response = await fetch('/api/checkout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const json = await response.json();
                        if (!response.ok || !json?.ok) {
                            const message = json?.message || 'Не удалось создать платёж.';
                            throw new Error(message);
                        }

                        window.location.href = json.data.payment_url;
                    } catch (error) {
                        alert(error.message || 'Ошибка при создании платежа');
                    } finally {
                        setButtonState(false);
                    }
                });
            }

            const orderFromQuery = new URLSearchParams(window.location.search).get('order');
            if (orderFromQuery) {
                fetch(`/api/orders/${orderFromQuery}`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.json())
                    .then((json) => {
                        if (!json?.ok) return;
                        const status = json?.data?.status || 'unknown';
                        if (status === 'completed') {
                            alert('Заказ успешно выполнен.');
                        } else if (status === 'error') {
                            alert(`Ошибка выполнения заказа: ${json?.data?.error_message || 'неизвестно'}`);
                        } else {
                            alert(`Текущий статус заказа: ${status}`);
                        }
                    })
                    .catch(() => {});
            }
        })();
    </script>
@endpush

@section('content')
    <main class="container">
        @include('includes.home.hero')
        @include('includes.home.plans')
        @include('includes.home.advantages')
        @include('includes.home.faq')
    </main>
@endsection
