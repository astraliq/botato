<?php

declare(strict_types=1);

use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelStatus;
use App\Domain\KnowledgeBase\KnowledgeBase;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Channel|null $telegramChannel
 * @var string|null $telegramSecretHint
 * @var list<KnowledgeBase> $knowledgeBases
 * @var list<string> $assignedKnowledgeBaseIds
 * @var int $maxKnowledgeBases
 */

$this->setTitle('Настройки — Botato');

$connectTelegramUrl = $urlGenerator->generate('api/cabinet/settings/telegram');
$assignKnowledgeUrl = $urlGenerator->generate('api/cabinet/settings/knowledge');
$knowledgeSettingsUrl = $urlGenerator->generate('cabinet/settings/knowledge');
$botFatherUrl = 'https://t.me/BotFather';
$tooltipId = 'botfather-tooltip';
$isTelegramConnected = $telegramChannel !== null
    && $telegramChannel->getStatus() === ChannelStatus::Connected;
$telegramDisplayName = $telegramChannel?->getDisplayName();
$hasKnowledgeBases = $knowledgeBases !== [];
$assignedCount = count($assignedKnowledgeBaseIds);
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__layout">
            <?= $this->render(__DIR__ . '/Partial/settings-tabs', [
                'urlGenerator' => $urlGenerator,
                'activeTab' => 'telegram',
                'isTelegramConnected' => $isTelegramConnected,
            ]) ?>

            <div class="cabinet__card">
                <div class="cabinet__head">
                    <h1>Подключить Telegram</h1>
                </div>

                <div class="form-success" data-telegram-success hidden></div>

                <div data-telegram-status-mount>
                    <?php if ($isTelegramConnected): ?>
                        <div class="channel-status" data-telegram-status>
                            <div class="channel-status__badge">Подключено</div>
                            <div class="channel-status__meta">
                                <?php if ($telegramDisplayName !== null): ?>
                                    <strong><?= Html::encode($telegramDisplayName) ?></strong>
                                <?php endif; ?>
                                <?php if ($telegramSecretHint !== null): ?>
                                    <span class="channel-status__hint">токен ···<?= Html::encode($telegramSecretHint) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="cabinet__lead">
                    Ассистент будет отвечать клиентам в вашей личной переписке Telegram от вашего имени.
                    Для этого создайте бота, включите у него режим секретаря и подключите его в разделе
                    «Автоматизация чатов».
                </p>

                <ol class="instruction-steps">
                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">1</div>
                        <div class="instruction-step__body">
                            <h2>Создайте нового бота</h2>
                            <p>
                                В Telegram найдите
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
                                или откройте ссылку. Это официальный аккаунт Telegram, не сторонний сервис.
                            </p>
                            <p>В чате с BotFather выполните по порядку:</p>
                            <ol class="instruction-substeps">
                                <li>
                                    Отправьте команду
                                    <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/copy-code', [
                                        'text' => '/newbot',
                                    ]) ?>.
                                </li>
                                <li>Отправьте в чат <strong>отображаемое имя</strong> бота — например, название вашего бизнеса. Его будут видеть клиенты.</li>
                                <li>
                                    Отправьте в чат <strong>username</strong> бота. Он должен быть уникальным и
                                    <strong>заканчиваться на <code>bot</code></strong>
                                    (например, <code>my_salon_bot</code> или <code>MySalonBot</code>).
                                </li>
                            </ol>
                            <p>
                                Если username занят, BotFather попросит выбрать другой — просто предложите новый вариант.
                            </p>
                            <p>
                                После создания BotFather пришлёт сообщение с токеном.
                            </p>
                            <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                'src' => 'telegram/token-message.png',
                                'alt' => 'Сообщение BotFather с HTTP API token после создания бота',
                                'caption' => 'Сообщение после создания бота',
                            ]) ?>
                            <p>
                                Скопируйте token или целиком сообщение (лишнее мы обрежем при вставке).
                            </p>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">2</div>
                        <div class="instruction-step__body">
                            <h2>Сохраните токен бота в Botato</h2>
                            <p>
                                Вставьте токен или целиком сообщение ниже и нажмите «Подключить».
                                Мы проверим его через Telegram и сохраним в зашифрованном виде.
                                Никому не передавайте токен: по нему можно управлять вашим ботом.
                            </p>

                            <form
                                method="post"
                                action="<?= Html::encode($connectTelegramUrl) ?>"
                                class="telegram-token-form has-form-preloader"
                                data-connect-telegram-form
                            >
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
                                    <div class="form-error" data-telegram-error hidden></div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <?= $isTelegramConnected ? 'Обновить токен' : 'Подключить бота' ?>
                                </button>
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/form-preloader', [
                                    'text' => 'Проверяем токен…',
                                ]) ?>
                            </form>

                            <p class="instruction-step__note">
                                Если токен потеряли: откройте BotFather →
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/copy-code', [
                                    'text' => '/mybots',
                                ]) ?>
                                → выберите бота →
                                <strong>API Token</strong> → при необходимости перевыпустите токен.
                            </p>
                            <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                'src' => 'telegram/botfather-bot-menu.png',
                                'alt' => 'Меню бота в BotFather с кнопкой API Token',
                                'caption' => 'Где найти токен снова: API Token',
                            ]) ?>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">3</div>
                        <div class="instruction-step__body">
                            <h2>Включите режим секретаря у бота</h2>
                            <p>
                                Без режима секретаря бот не сможет работать в личной переписке через
                                «Автоматизация чатов».
                            </p>
                            <ol class="instruction-substeps">
                                <li>
                                    Откройте чат со своим ботом.
                                    Кнопку <strong>Старт</strong> нажимать не нужно.
                                    <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                        'src' => 'telegram/bot-link.png',
                                        'alt' => 'Сообщение BotFather о создании бота со ссылкой на чат',
                                        'caption' => 'Нажмите на ссылку чата с созданным ботом',
                                    ]) ?>
                                </li>
                                <li>Справа сверху нажмите на иконку бота.</li>
                                <li>Перейдите в <strong>Настройки бота</strong>.</li>
                                <li>Нажмите <strong>Изменить настройки бота</strong>.</li>
                                <li>
                                    Откройте <strong>Secretary Mode</strong> и нажмите <strong>Turn on</strong>.
                                    Должно появиться подтверждение, что режим включён.
                                </li>
                            </ol>
                            <div class="instruction-figures">
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                    'src' => 'telegram/bot-start.png',
                                    'alt' => 'Чат с ботом: иконка бота справа сверху',
                                    'caption' => 'Справа сверху нажмите на иконку бота',
                                ]) ?>
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                    'src' => 'telegram/bot-profile.png',
                                    'alt' => 'Профиль бота с пунктом Настройки бота',
                                    'caption' => 'Перейдите в «Настройки бота»',
                                ]) ?>
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                    'src' => 'telegram/bot-edit.png',
                                    'alt' => 'Экран редактирования бота с пунктом Изменить настройки бота',
                                    'caption' => 'Нажмите «Изменить настройки бота»',
                                ]) ?>
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                    'src' => 'telegram/botfather-settings.png',
                                    'alt' => 'Настройки бота с пунктом Secretary Mode',
                                    'caption' => 'Откройте Secretary Mode',
                                ]) ?>
                                <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/instruction-figure', [
                                    'src' => 'telegram/secretary-turn-on.png',
                                    'alt' => 'Включение Secretary Mode кнопкой Turn on',
                                    'caption' => 'Нажмите Turn on',
                                ]) ?>
                            </div>
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
                                    (<code data-telegram-bot-username><?= Html::encode($telegramDisplayName ?? '@my_salon_bot') ?></code>).
                                    Username можно посмотреть в профиле бота в Telegram.
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
                                <li>
                                    Откройте <strong>Разрешения для бота</strong> и в блоке
                                    <strong>Управление сообщениями</strong> включите:
                                    <ul>
                                        <li><strong>Чтение сообщений</strong> — обязательно;</li>
                                        <li><strong>Ответы на сообщения</strong> — обязательно;</li>
                                        <li><strong>Отметки о прочтении</strong> — по желанию.</li>
                                    </ul>
                                    Остальные разрешения (удаление сообщений, профиль, подарки, истории) для Botato не нужны.
                                </li>
                                <li>Сохраните настройки.</li>
                            </ol>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">5</div>
                        <div class="instruction-step__body">
                            <h2>Укажите базы знаний</h2>
                            <p>
                                Выберите одну или несколько баз, на которые ассистент будет опираться в ответах.
                                По тарифу доступно до <?= Html::encode((string) $maxKnowledgeBases) ?>
                                <?= $maxKnowledgeBases === 1 ? 'базы' : 'баз' ?>.
                                Статьи редактируются в разделе
                                <a href="<?= Html::encode($knowledgeSettingsUrl) ?>">Базы знаний</a>.
                            </p>

                            <div data-assign-kb-section>
                                <?php if (!$isTelegramConnected): ?>
                                    <p class="instruction-step__note" data-assign-kb-need-bot>
                                        Сначала подключите бота в пункте 2 — после этого здесь появится выбор баз.
                                    </p>
                                <?php endif; ?>

                                <?php if (!$hasKnowledgeBases): ?>
                                    <p class="instruction-step__note" data-assign-kb-empty<?= $isTelegramConnected ? '' : ' hidden' ?>>
                                        Пока нет баз знаний.
                                        <a href="<?= Html::encode($knowledgeSettingsUrl) ?>">Создайте первую</a>,
                                        затем вернитесь и привяжите её к Telegram.
                                    </p>
                                <?php else: ?>
                                    <form
                                        method="post"
                                        action="<?= Html::encode($assignKnowledgeUrl) ?>"
                                        class="telegram-kb-form has-form-preloader"
                                        data-assign-kb-form
                                        data-assign-kb-max="<?= Html::encode((string) $maxKnowledgeBases) ?>"
                                        <?= $isTelegramConnected ? '' : 'hidden' ?>
                                    >
                                        <input type="hidden" name="form_action" value="assign_telegram">
                                        <fieldset class="telegram-kb-form__fieldset">
                                            <legend>Базы знаний для Telegram</legend>
                                            <p class="form-field__hint" data-assign-kb-counter>
                                                Выбрано: <?= Html::encode((string) $assignedCount) ?> из <?= Html::encode((string) $maxKnowledgeBases) ?>
                                            </p>
                                            <div class="telegram-kb-checkboxes">
                                                <?php foreach ($knowledgeBases as $knowledgeBase): ?>
                                                    <?php
                                                    $isAssigned = in_array($knowledgeBase->getId(), $assignedKnowledgeBaseIds, true);
                                                    $isDisabled = !$knowledgeBase->isActive();
                                                    $checkboxId = 'telegram_kb_' . $knowledgeBase->getId();
                                                    ?>
                                                    <label class="kb-checkbox telegram-kb-checkbox<?= $isDisabled ? ' is-disabled' : '' ?>" for="<?= Html::encode($checkboxId) ?>">
                                                        <input
                                                            id="<?= Html::encode($checkboxId) ?>"
                                                            type="checkbox"
                                                            name="knowledge_base_ids[]"
                                                            value="<?= Html::encode($knowledgeBase->getId()) ?>"
                                                            data-assign-kb-checkbox
                                                            <?= $isAssigned ? ' checked' : '' ?>
                                                            <?= $isDisabled ? ' disabled' : '' ?>
                                                        >
                                                        <span><?= Html::encode($knowledgeBase->getTitle()) ?></span>
                                                        <?php if ($isDisabled): ?>
                                                            <span class="telegram-kb-checkbox__meta">(неактивна)</span>
                                                        <?php endif; ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="form-error" data-assign-kb-error hidden></div>
                                        </fieldset>
                                        <button type="submit" class="btn btn-primary">Сохранить базы</button>
                                        <?= $this->render(dirname(__DIR__, 2) . '/Shared/Partial/form-preloader', [
                                            'text' => 'Сохраняем…',
                                        ]) ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>

                    <li class="instruction-step">
                        <div class="instruction-step__num" aria-hidden="true">6</div>
                        <div class="instruction-step__body">
                            <h2>Проверьте работу</h2>
                            <p>
                                С другого аккаунта Telegram напишите <strong>вам</strong> в личку вопрос
                                вроде: «Здравствуйте! Сколько стоит услуга и есть ли запись?»
                            </p>
                            <p>
                                Убедитесь, что ответ приходит от вашего имени и опирается на данные профиля
                                и базы знаний в Botato. Если ответа нет — проверьте, что у бота включён
                                режим секретаря, бот указан в «Автоматизация чатов», выданы разрешения
                                на чтение и ответы, и чат не в исключениях.
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
