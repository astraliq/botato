<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var bool $isAuthenticated
 * @var string $cabinetUrl
 * @var string $mailerFrom
 */

$this->setTitle('Botato — ИИ-ассистент для Telegram, VK и Instagram');
?>

<section class="hero">
    <div class="container hero__grid">
        <div>
            <div class="hero__badge">ИИ для частных специалистов и малого бизнеса</div>
            <h1>ИИ-ассистент отвечает клиентам в Telegram, VK и Instagram</h1>
            <p class="hero__lead">
                Автоматизируйте ответы о прайсе, услугах и записи. Загрузите базу знаний —
                ассистент будет общаться с клиентами 24/7 от вашего имени.
            </p>
            <div class="hero__actions">
                <?php if ($isAuthenticated): ?>
                    <a href="<?= Html::encode($cabinetUrl) ?>" class="btn btn-primary">Перейти в кабинет</a>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" data-auth-open>Попробовать бесплатно</button>
                <?php endif; ?>
                <a href="#pricing" class="btn btn-secondary">Смотреть тарифы</a>
            </div>
            <div class="hero__stats">
                <span>От 0 ₽ на старте</span>
                <span>Настройка за 15 минут</span>
                <span>Telegram, VK, Instagram</span>
            </div>
        </div>
        <div class="hero-card">
            <div class="hero-card__title">Пример диалога</div>
            <div class="chat-preview">
                <div class="chat-bubble chat-bubble--client">Здравствуйте! Сколько стоит чистка лица и есть ли запись на пятницу?</div>
                <div class="chat-bubble chat-bubble--bot">Здравствуйте! Чистка лица — 3 500 ₽. На пятницу есть окна в 14:00 и 17:30. Записать вас?</div>
                <div class="chat-bubble chat-bubble--client">Да, на 14:00, спасибо!</div>
                <div class="chat-bubble chat-bubble--bot">Отлично, записала вас на пятницу в 14:00. Если появятся вопросы — напишите.</div>
            </div>
        </div>
    </div>
</section>

<section class="section section--muted">
    <div class="container">
        <div class="section__head">
            <h2>Знакомо?</h2>
            <p>Клиенты пишут в нерабочее время, а вы теряете заявки и тратите часы на одни и те же ответы.</p>
        </div>
        <div class="cards-grid">
            <article class="card">
                <h3>Клиенты уходят</h3>
                <p>Не ответили за 5 минут — клиент уже написал конкуренту. В выходные заявки копятся без ответа.</p>
            </article>
            <article class="card">
                <h3>Рутина съедает время</h3>
                <p>Один и тот же прайс, адрес, график и запись — каждый день снова и снова.</p>
            </article>
            <article class="card">
                <h3>Автоматизация дорогая</h3>
                <p>Классические CRM-боты сложно настраивать и дорого внедрять для частного специалиста.</p>
            </article>
        </div>
    </div>
</section>

<section class="section" id="features">
    <div class="container">
        <div class="section__head">
            <h2>Что умеет ассистент</h2>
            <p>Фокус на общении с клиентами в мессенджерах — без лишней сложности.</p>
        </div>
        <div class="cards-grid">
            <article class="card">
                <h3>Отвечает 24/7</h3>
                <p>Мгновенно отвечает на сообщения в подключённых каналах, даже когда вы заняты.</p>
            </article>
            <article class="card">
                <h3>Знает ваш прайс</h3>
                <p>Ориентируется по базе знаний: услуги, цены, FAQ, правила записи и описание бизнеса.</p>
            </article>
            <article class="card">
                <h3>Записывает клиентов</h3>
                <p>Уточняет время, предлагает свободные окна и помогает довести клиента до записи.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section--muted" id="channels">
    <div class="container">
        <div class="section__head">
            <h2>Каналы связи</h2>
            <p>Начинаем с Telegram и VK. Instagram — в ближайших обновлениях.</p>
        </div>
        <div class="channels">
            <div class="channel">
                <div class="channel__icon channel__icon--telegram">TG</div>
                <div>
                    <strong>Telegram</strong>
                    <div style="color:#64748b;font-size:14px;">Готово к подключению</div>
                </div>
            </div>
            <div class="channel">
                <div class="channel__icon channel__icon--vk">VK</div>
                <div>
                    <strong>ВКонтакте</strong>
                    <div style="color:#64748b;font-size:14px;">Готово к подключению</div>
                </div>
            </div>
            <div class="channel">
                <div class="channel__icon channel__icon--instagram">IG</div>
                <div>
                    <strong>Instagram</strong>
                    <div style="color:#64748b;font-size:14px;"><span class="badge">скоро</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <h2>Запуск за 3 шага</h2>
            <p>Настройте ассистента без программиста — прямо из личного кабинета.</p>
        </div>
        <div class="steps">
            <article class="step">
                <div class="step__num">1</div>
                <h3>Зарегистрируйтесь</h3>
                <p>Войдите по email и заполните профиль бизнеса для контекста ассистента.</p>
            </article>
            <article class="step">
                <div class="step__num">2</div>
                <h3>Загрузите знания</h3>
                <p>Добавьте прайс, услуги, FAQ и правила общения с клиентами.</p>
            </article>
            <article class="step">
                <div class="step__num">3</div>
                <h3>Подключите канал</h3>
                <p>Telegram или VK — ассистент начнёт отвечать клиентам автоматически.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section--muted" id="pricing">
    <div class="container">
        <div class="section__head">
            <h2>Тарифы</h2>
            <p>Выберите план по количеству диалогов и подключённых каналов.</p>
        </div>

        <div style="text-align:center;">
            <div class="pricing-toggle" data-pricing-toggle>
                <button type="button" class="is-active" data-period="1">1 мес</button>
                <button type="button" data-period="6">6 мес -15%</button>
                <button type="button" data-period="12">12 мес -25%</button>
            </div>
        </div>

        <div class="pricing-grid">
            <article class="pricing-card">
                <h3>Free</h3>
                <div class="pricing-card__price" data-price data-base-price="0">0 ₽<small>/мес</small></div>
                <p style="color:#64748b;margin:0;">Для знакомства с платформой</p>
                <ul>
                    <li>10 диалогов в месяц</li>
                    <li>1 канал</li>
                    <li>1 база знаний</li>
                </ul>
                <button type="button" class="btn btn-secondary" data-auth-open>Начать бесплатно</button>
            </article>

            <article class="pricing-card">
                <h3>Старт</h3>
                <div class="pricing-card__price" data-price data-base-price="990">990 ₽<small>/мес</small></div>
                <p style="color:#64748b;margin:0;">Для фрилансеров и малого бизнеса</p>
                <ul>
                    <li>100 диалогов в месяц</li>
                    <li>2 канала</li>
                    <li>3 базы знаний</li>
                </ul>
                <button type="button" class="btn btn-secondary" data-auth-open>Попробовать</button>
            </article>

            <article class="pricing-card pricing-card--popular">
                <span class="pricing-card__label">Популярный</span>
                <h3>Бизнес</h3>
                <div class="pricing-card__price" data-price data-base-price="2490">2 490 ₽<small>/мес</small></div>
                <p style="color:#64748b;margin:0;">Для растущих команд</p>
                <ul>
                    <li>300 диалогов в месяц</li>
                    <li>3 канала</li>
                    <li>10 баз знаний</li>
                </ul>
                <button type="button" class="btn btn-primary" data-auth-open>Попробовать</button>
            </article>

            <article class="pricing-card">
                <h3>Про</h3>
                <div class="pricing-card__price" data-price data-base-price="4990">4 990 ₽<small>/мес</small></div>
                <p style="color:#64748b;margin:0;">Для активного потока клиентов</p>
                <ul>
                    <li>1 000 диалогов в месяц</li>
                    <li>Без лимита каналов</li>
                    <li>30 баз знаний</li>
                </ul>
                <button type="button" class="btn btn-secondary" data-auth-open>Попробовать</button>
            </article>
        </div>
    </div>
</section>

<section class="section" id="faq">
    <div class="container">
        <div class="section__head">
            <h2>Частые вопросы</h2>
        </div>
        <div class="faq">
            <details>
                <summary>Это сложно настроить?</summary>
                <p>Нет. Регистрация по email занимает пару минут. Дальше вы заполняете профиль бизнеса и базу знаний — без кода и программиста.</p>
            </details>
            <details>
                <summary>Чем это отличается от ChatGPT?</summary>
                <p>ChatGPT не знает ваш бизнес. Botato использует ваши данные — прайс, услуги, правила записи — и отвечает клиентам в мессенджерах.</p>
            </details>
            <details>
                <summary>Как считается диалог?</summary>
                <p>Диалог — вся переписка с одним клиентом в течение 24 часов, независимо от количества сообщений.</p>
            </details>
            <details>
                <summary>Что если ассистент ответит неправильно?</summary>
                <p>Ассистент опирается на вашу базу знаний. Вы можете обновить описание бизнеса, прайс и FAQ в любой момент.</p>
            </details>
            <details>
                <summary>Когда будет Instagram?</summary>
                <p>Интеграция Instagram уже в roadmap. Сейчас доступны Telegram и VK.</p>
            </details>
        </div>
    </div>
</section>

<section class="cta">
    <div class="container cta__inner">
        <h2>Готовы начать?</h2>
        <p>Бесплатный тариф — без карты. Настройка профиля займёт 15 минут.</p>
        <?php if ($isAuthenticated): ?>
            <a href="<?= Html::encode($cabinetUrl) ?>" class="btn btn-secondary">Открыть личный кабинет</a>
        <?php else: ?>
            <button type="button" class="btn btn-secondary" data-auth-open>Начать бесплатно</button>
        <?php endif; ?>
    </div>
</section>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>© <?= date('Y') ?> Botato</div>
        <div><?= Html::encode($mailerFrom) ?></div>
    </div>
</footer>
