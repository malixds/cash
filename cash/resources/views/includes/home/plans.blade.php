<section id="plans" class="section">
    <h3>Рассчитайте пополнение</h3>
    <div class="topup-wrap">
        <form class="topup-form" id="topupForm">
            <!-- <label class="field">
                <span>Регион аккаунта</span>
                <select id="region" name="region">
                    <option value="ru" selected>Россия</option>
                    <option value="kz">Казахстан</option>
                    <option value="tr">Турция</option>
                    <option value="other">Другой регион</option>
                </select>
            </label> -->

            <div class="field-row">
                <label class="field">
                    <span>Никнейм</span>
                    <input id="nickname" name="nickname" type="text" placeholder="Введите логин Steam" required>
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
            <label class="pay-option">
                <input type="radio" name="payment_method" value="card">
                <span>Банковская карта</span>
            </label>

            <button class="button button-primary payment-submit" type="submit" form="topupForm">
                Перейти к оплате
            </button>
            <p class="payment-note">Финальная сумма будет зафиксирована перед оплатой.</p>
        </aside>
    </div>
</section>
