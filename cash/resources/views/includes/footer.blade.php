<footer>
    <div class="container footer-inner">
        <nav class="footer-links" aria-label="Юридическая информация">
            <a href="{{ route('legal.oferta') }}">Публичная оферта</a>
            <a href="{{ route('legal.privacy') }}">Политика обработки ПДн</a>
            <a href="{{ route('legal.refund') }}">Возврат и отмена</a>
            <a href="{{ route('legal.delivery') }}">Доставка</a>
            <a href="{{ route('legal.contacts') }}">Реквизиты и контакты</a>
        </nav>

        <div class="footer-meta">
            <p>{{ config('company.brand') }} — пополнение баланса Steam. Оплата через СБП.</p>
            <p>{{ config('company.fio') }} · ИНН {{ config('company.inn') }} · {{ config('company.city') }}</p>
            <p>Поддержка: <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a></p>
        </div>
    </div>
</footer>
