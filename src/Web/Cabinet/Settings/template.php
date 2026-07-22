<?php

declare(strict_types=1);

use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelStatus;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Channel|null $telegramChannel
 * @var string|null $telegramSecretHint
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var string|null $csrf
 */

$this->setTitle('Настройки — Botato');

$settingsUrl = $urlGenerator->generate('cabinet/settings');
$connectTelegramUrl = $urlGenerator->generate('cabinet/settings/telegram');
$botFatherUrl = 'https://t.me/BotFather';
$tooltipId = 'botfather-tooltip';
$isTelegramConnected = $telegramChannel !== null
    && $telegramChannel->getStatus() === ChannelStatus::Connected;
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__layout">
            <nav class="cabinet__tabs" aria-label="Разделы настроек">
                <a href="<?= Html::encode($settingsUrl) ?>" class="cabinet__tab is-active" aria-current="page">
                    <span>Подключить Telegram</span>
                    <?php if ($isTelegramConnected): ?>
                        <span class="cabinet__tab-check" title="Telegram подключён" aria-label="Telegram подключён">✓</span>
                    <?php endif; ?>
                </a>
            </nav>

            <div class="cabinet__card">
                <div class="cabinet__head">
                    <h1>Подключить Telegram</h1>
                </div>

                <?php if ($successMessage !== null): ?>
                    <div class="form-success"><?= Html::encode($successMessage) ?></div>
                <?php endif; ?>

                <?php if ($errorMessage !== null): ?>
                    <div class="form-error"><?= Html::encode($errorMessage) ?></div>
                <?php endif; ?>

                <?php if ($telegramChannel !== null && $isTelegramConnected): ?>
                    <div class="channel-status">
                        <div class="channel-status__badge">Подключено</div>
                        <div class="channel-status__meta">
                            <strong><?= Html::encode((string) $telegramChannel->getDisplayName()) ?></strong>
                            <?php if ($telegramSecretHint !== null): ?>
                                <span class="channel-status__hint">токен ···<?= Html::encode($telegramSecretHint) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <p class="cabinet__lead">
                    Ассистент будет отвечать клиентам в вашей личной переписке Telegram от вашего имени.
                    Для этого создайте бота и подключите его в разделе «Автоматизация чатов».
                </p>

                <ol class="instruction-steps">
                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">1</div>
                        <div class="instruction-step__body">
                            <h2>
                                Откройте
                                <a href="<?= Html::encode($botFatherUrl) ?>" target="_blank" rel="noopener noreferrer">@BotFather</a>
                                <span class="info-tooltip">
                                    <button
                                        type="button"
                                        class="info-tooltip__trigger"
                                        aria-describedby="<?= Html::encode($tooltipId) ?>"
                                        aria-label="Что такое BotFather"
                                    >
                                        <span aria-hidden="true">ⓘ</span>
                                    </button>
                                    <span class="info-tooltip__content" id="<?= Html::encode($tooltipId) ?>" role="tooltip">
                                        BotFather — официальный бот Telegram для создания и управления ботами
                                    </span>
                                </span>
                            </h2>
                            <p>
                                В Telegram найдите
                                <a href="<?= Html::encode($botFatherUrl) ?>" target="_blank" rel="noopener noreferrer">@BotFather</a>
                                или откройте ссылку. Это официальный аккаунт Telegram, не сторонний сервис.
                            </p>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">2</div>
                        <div class="instruction-step__body">
                            <h2>Создайте нового бота</h2>
                            <p>В чате с BotFather выполните по порядку:</p>
                            <ol class="instruction-substeps">
                                <li>Отправьте команду <code>/newbot</code>.</li>
                                <li>Укажите <strong>отображаемое имя</strong> бота — например, название вашего бизнеса. Его будут видеть клиенты.</li>
                                <li>
                                    Укажите <strong>username</strong> бота. Он должен быть уникальным и
                                    <strong>заканчиваться на <code>bot</code></strong>
                                    (например, <code>my_salon_bot</code> или <code>MySalonBot</code>).
                                </li>
                            </ol>
                            <p>
                                Если username занят, BotFather попросит выбрать другой — просто предложите новый вариант.
                            </p>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">3</div>
                        <div class="instruction-step__body">
                            <h2>Сохраните токен бота в Botato</h2>
                            <p>
                                После создания BotFather пришлёт сообщение с <strong>HTTP API token</strong> —
                                длинной строкой вида <code>123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw</code>.
                            </p>
                            <p>
                                Вставьте токен ниже. Мы проверим его через Telegram и сохраним в зашифрованном виде.
                                Никому не передавайте токен: по нему можно управлять вашим ботом.
                            </p>

                            <form method="post" action="<?= Html::encode($connectTelegramUrl) ?>" class="telegram-token-form">
                                <?php if ($csrf !== null): ?>
                                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                                <?php endif; ?>
                                <div class="form-field">
                                    <label for="bot_token">Токен бота</label>
                                    <input
                                        id="bot_token"
                                        type="password"
                                        name="bot_token"
                                        required
                                        autocomplete="off"
                                        placeholder="123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw"
                                    >
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <?= $isTelegramConnected ? 'Обновить токен' : 'Подключить бота' ?>
                                </button>
                            </form>

                            <p class="instruction-step__note">
                                Если токен потеряли: откройте BotFather → <code>/mybots</code> → выберите бота →
                                <strong>API Token</strong> → при необходимости перевыпустите токен.
                            </p>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">4</div>
                        <div class="instruction-step__body">
                            <h2>Подключите бота в «Автоматизация чатов»</h2>
                            <p>Чтобы ассистент отвечал в вашей личке (а не только в отдельном чате с ботом):</p>
                            <ol class="instruction-substeps">
                                <li>Откройте Telegram → <strong>Настройки</strong>.</li>
                                <li>Перейдите в <strong>Мой аккаунт</strong> → <strong>Автоматизация чатов</strong>.</li>
                                <li>
                                    В поле «Ссылка или @имя бота» укажите username вашего бота
                                    <?php if ($telegramChannel !== null && $telegramChannel->getDisplayName() !== null): ?>
                                        (<code><?= Html::encode($telegramChannel->getDisplayName()) ?></code>)
                                    <?php else: ?>
                                        (например, <code>@my_salon_bot</code>)
                                    <?php endif; ?>
                                    .
                                </li>
                                <li>
                                    Выберите, какие чаты доступны боту:
                                    <ul>
                                        <li><strong>Все личные чаты, кроме…</strong> — для большинства клиентов;</li>
                                        <li><strong>Только выбранные чаты</strong> — для точечного теста.</li>
                                    </ul>
                                </li>
                                <li>
                                    В <strong>Исключения</strong> добавьте личные чаты (семья, друзья, коллеги),
                                    куда бот не должен заходить.
                                </li>
                                <li>Сохраните настройки.</li>
                            </ol>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">5</div>
                        <div class="instruction-step__body">
                            <h2>Проверьте работу</h2>
                            <p>
                                С другого аккаунта Telegram напишите <strong>вам</strong> в личку вопрос
                                вроде: «Здравствуйте! Сколько стоит услуга и есть ли запись?»
                            </p>
                            <p>
                                Убедитесь, что ответ приходит от вашего имени и опирается на данные профиля
                                и базы знаний в Botato. Если ответа нет — проверьте, что бот указан в
                                «Автоматизация чатов» и чат не в исключениях.
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
