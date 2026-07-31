<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var bool $isAuthenticated
 * @var string $cabinetUrl
 * @var string $mailerFrom
 * @var list<\App\Domain\Plan\Plan> $plans
 * @var string $personalDataUrl
 */

$this->setTitle('Botato — ИИ-ассистент для Telegram, VK и Instagram');
$this->registerMeta([
    'name' => 'yandex-verification',
    'content' => 'c991cdff6022ef83',
]);
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
                <span>Telegram сейчас · VK и Instagram скоро</span>
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
            <p>Сейчас доступен Telegram. ВКонтакте и Instagram — в ближайших обновлениях.</p>
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
                    <div style="color:#64748b;font-size:14px;"><span class="badge">скоро</span></div>
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
                <p>Подключите Telegram — ассистент начнёт отвечать клиентам автоматически.</p>
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

        <div class="pricing-grid" style="--pricing-columns: <?= count($plans) ?>">
            <?php foreach ($plans as $plan): ?>
                <?php
                $priceRubles = $plan->getPriceMonthlyRubles();
                $priceLabel = number_format($priceRubles, 0, '', ' ') . ' ₽';
                $cardClass = $plan->isPopular() ? 'pricing-card pricing-card--popular' : 'pricing-card';
                $buttonClass = $plan->isPopular() && $plan->isActive() ? 'btn btn-primary' : 'btn btn-secondary';
                ?>
                <article class="<?= Html::encode($cardClass) ?>">
                    <?php if ($plan->isPopular()): ?>
                        <span class="pricing-card__label">Популярный</span>
                    <?php endif; ?>
                    <h3><?= Html::encode($plan->getName()) ?></h3>
                    <div
                        class="pricing-card__price"
                        data-price
                        data-base-price="<?= Html::encode((string) $priceRubles) ?>"
                    ><?= Html::encode($priceLabel) ?><small>/мес</small></div>
                    <p style="color:#64748b;margin:0;"><?= Html::encode($plan->getDescription()) ?></p>
                    <ul>
                        <li><?= Html::encode($plan->getDialogsFeatureLabel()) ?></li>
                        <li><?= Html::encode($plan->getMaxMessagesPerDialogFeatureLabel()) ?></li>
                        <li><?= Html::encode($plan->getChannelsFeatureLabel()) ?></li>
                        <li><?= Html::encode($plan->getKnowledgeBasesFeatureLabel()) ?></li>
                    </ul>
                    <?php if ($plan->isActive()): ?>
                        <button type="button" class="<?= Html::encode($buttonClass) ?>" data-auth-open><?= Html::encode($plan->getButtonLabel()) ?></button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled><?= Html::encode($plan->getButtonLabel()) ?></button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
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
                <p>
                    Диалог — вся переписка с одним клиентом в течение 24 часов.
                    Первое сообщение в этом окне списывает 1 диалог из лимита тарифа.
                    В одном диалоге — до лимита сообщений тарифа; после истечения 24 часов
                    переписка с тем же клиентом считается новым диалогом.
                </p>
            </details>
            <details>
                <summary>Что если ассистент ответит неправильно?</summary>
                <p>Ассистент опирается на вашу базу знаний. Вы можете обновить описание бизнеса, прайс и FAQ в любой момент.</p>
            </details>
            <details>
                <summary>Когда будут ВКонтакте и Instagram?</summary>
                <p>Сейчас доступен Telegram. Интеграции ВКонтакте и Instagram уже в ближайших обновлениях.</p>
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
        <div class="site-footer__links">
            <a href="<?= Html::encode($personalDataUrl) ?>">Оферта и персональные данные</a>
            <span><?= Html::encode($mailerFrom) ?></span>
        </div>
    </div>
</footer>
