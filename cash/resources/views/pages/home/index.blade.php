@extends('default.layout')

@section('title', 'Пополнение Steam кошелька РФ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@push('scripts')
    <script>
        (() => {
            const amountInput = document.getElementById('amount');
            const totalAmount = document.getElementById('totalAmount');

            if (!amountInput || !totalAmount) return;

            const updateTotal = () => {
                const amount = Number(amountInput.value) || 0;
                const total = amount > 0 ? amount * 1.05 : 0;
                totalAmount.textContent = `${Math.ceil(total).toLocaleString('ru-RU')} ₽`;
            };

            amountInput.addEventListener('input', updateTotal);
            updateTotal();
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
