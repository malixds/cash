@extends('default.layout')

@section('title', 'Реквизиты и контакты — ' . config('company.brand'))

@section('content')
    <main class="container legal">
        <h1>Реквизиты и контакты</h1>

        <p>Услуги на сайте {{ config('company.domain') }} оказывает физическое лицо,
            применяющее специальный налоговый режим «Налог на профессиональный доход».</p>

        <table class="legal-table">
            <tbody>
                <tr><th>Исполнитель</th><td>{{ config('company.fio') }}</td></tr>
                <tr><th>Статус</th><td>{{ config('company.status') }}</td></tr>
                <tr><th>ИНН</th><td>{{ config('company.inn') }}</td></tr>
                <tr><th>Место регистрации</th><td>{{ config('company.city') }}, Российская Федерация</td></tr>
                <tr><th>E-mail</th><td><a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a></td></tr>
                @if(config('company.phone'))
                    <tr><th>Телефон</th><td><a href="tel:{{ config('company.phone') }}">{{ config('company.phone') }}</a></td></tr>
                @else
                    <tr><th>Телефон</th><td>предоставляется по запросу через e-mail поддержки</td></tr>
                @endif
                <tr><th>Сайт</th><td>{{ config('company.domain') }}</td></tr>
            </tbody>
        </table>

        <h2>Служба поддержки</h2>
        <p>По любым вопросам, связанным с заказами и оплатой, пишите на
            <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a>.
            Мы отвечаем в течение рабочего дня.</p>

        <p class="legal-links"><a href="{{ url('/') }}">← На главную</a></p>
    </main>
@endsection
