<section id="plans" class="section">
    <h3>Рассчитайте пополнение</h3>
    <div class="catalog-region-single">Регион: <strong>Россия</strong></div>
    <div class="topup-wrap">
        <form class="topup-form" id="topupForm">
            <div class="field-row">
                <label class="field">
                    <span>Никнейм</span>
                    <input id="login" name="login" type="text" placeholder="Введите логин Steam" required>
                </label>

                <label class="field">
                    <span>Сумма пополнения, ₽</span>
                    <input id="amount" name="amount" type="number" min="1" step="1" placeholder="Введите сумму" required>
                </label>
            </div>

            <label class="field">
                <span>Промокод</span>
                <input id="promo" name="promo" type="text" placeholder="Введите промокод">
            </label>

            <div class="result-line">
                <span>Итого</span>
                <strong id="totalAmount">0 ₽</strong>
            </div>
        </form>

        <aside class="payment-box">
            <h4>Способ оплаты</h4>
            <label class="pay-option">
                <input type="radio" name="payment_method" value="sbp" checked>
                <span>СБП</span>
            </label>

            <button class="button button-primary payment-submit" type="submit" form="topupForm">
                Перейти к оплате
            </button>
            <p class="payment-note">Финальная сумма будет зафиксирована перед оплатой.</p>
        </aside>
    </div>
</section>
