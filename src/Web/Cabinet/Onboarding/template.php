<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Domain\User\UserGender;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var User $user
 * @var array{surname: string, name: string, patronymic: string, company_name: string, description: string, phone: string, gender: string} $formValues
 * @var int $descriptionMaxLength
 * @var bool $hasKnowledgeBase
 * @var bool $isTelegramConnected
 * @var string|null $telegramDisplayName
 * @var list<\App\Domain\KnowledgeBase\KnowledgeBase> $knowledgeBases
 * @var list<string> $assignedKnowledgeBaseIds
 * @var int $maxKnowledgeBases
 * @var string $saveProfileUrl
 * @var string $createKnowledgeUrl
 * @var string $connectTelegramUrl
 * @var string $assignKnowledgeUrl
 * @var string $completeUrl
 * @var string $settingsUrl
 */

$this->setTitle('Настройка Botato');

$initialStep = 1;

if ($formValues['name'] !== '' && $formValues['surname'] !== '' && $formValues['company_name'] !== '' && $formValues['gender'] !== '') {
    $initialStep = 2;
}

if ($initialStep >= 2 && $hasKnowledgeBase) {
    $initialStep = 3;
}

$botFatherUrl = 'https://t.me/BotFather';
?>

<section
    class="cabinet onboarding"
    data-onboarding
    data-initial-step="<?= Html::encode((string) $initialStep) ?>"
    data-has-knowledge-base="<?= $hasKnowledgeBase ? '1' : '0' ?>"
    data-telegram-connected="<?= $isTelegramConnected ? '1' : '0' ?>"
    data-save-profile-url="<?= Html::encode($saveProfileUrl) ?>"
    data-create-knowledge-url="<?= Html::encode($createKnowledgeUrl) ?>"
    data-connect-telegram-url="<?= Html::encode($connectTelegramUrl) ?>"
    data-assign-knowledge-url="<?= Html::encode($assignKnowledgeUrl) ?>"
    data-complete-url="<?= Html::encode($completeUrl) ?>"
    data-settings-url="<?= Html::encode($settingsUrl) ?>"
    data-max-knowledge-bases="<?= Html::encode((string) $maxKnowledgeBases) ?>"
>
    <div class="container">
        <div class="cabinet__card onboarding__card">
            <div class="cabinet__head">
                <h1>Настройка Botato</h1>
            </div>
            <p class="cabinet__lead">
                Три коротких шага — и ассистент готов отвечать клиентам.
            </p>

            <ol class="onboarding__progress" data-onboarding-progress aria-label="Шаги настройки">
                <li class="onboarding__progress-item is-active" data-onboarding-progress-item="1">
                    <span class="onboarding__progress-num">1</span>
                    <span class="onboarding__progress-label">Профиль</span>
                </li>
                <li class="onboarding__progress-item" data-onboarding-progress-item="2">
                    <span class="onboarding__progress-num">2</span>
                    <span class="onboarding__progress-label">База знаний</span>
                </li>
                <li class="onboarding__progress-item" data-onboarding-progress-item="3">
                    <span class="onboarding__progress-num">3</span>
                    <span class="onboarding__progress-label">Подключение канала</span>
                </li>
            </ol>

            <div class="form-error" data-onboarding-error hidden></div>
            <div class="form-success" data-onboarding-success hidden></div>

            <div class="onboarding__step is-active" data-onboarding-step="1">
                <h2 class="onboarding__step-title">Профиль бизнеса</h2>
                <p class="onboarding__step-lead">Эти данные ИИ использует, чтобы отвечать от вашего имени.</p>

                <form data-onboarding-profile-form class="has-form-preloader" novalidate>
                    <div class="form-field">
                        <label>Email</label>
                        <input type="email" value="<?= Html::encode($user->getEmail()) ?>" readonly>
                    </div>

                    <div class="form-field-row">
                        <div class="form-field">
                            <label for="onboarding_surname">Фамилия</label>
                            <input id="onboarding_surname" type="text" name="surname" required maxlength="255" value="<?= Html::encode($formValues['surname']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="onboarding_name">Имя</label>
                            <input id="onboarding_name" type="text" name="name" required maxlength="255" value="<?= Html::encode($formValues['name']) ?>">
                        </div>
                        <div class="form-field">
                            <label for="onboarding_patronymic">Отчество</label>
                            <input id="onboarding_patronymic" type="text" name="patronymic" maxlength="255" value="<?= Html::encode($formValues['patronymic']) ?>">
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="onboarding_gender">Пол</label>
                        <select id="onboarding_gender" name="gender" required>
                            <option value="" disabled <?= $formValues['gender'] === '' ? 'selected' : '' ?>>Выберите пол</option>
                            <option value="<?= UserGender::Male->value ?>" <?= $formValues['gender'] === UserGender::Male->value ? 'selected' : '' ?>>
                                <?= Html::encode(UserGender::Male->label()) ?>
                            </option>
                            <option value="<?= UserGender::Female->value ?>" <?= $formValues['gender'] === UserGender::Female->value ? 'selected' : '' ?>>
                                <?= Html::encode(UserGender::Female->label()) ?>
                            </option>
                        </select>
                        <p class="form-field__hint">ИИ будет отвечать клиентам от вашего имени с учётом рода.</p>
                    </div>

                    <div class="form-field">
                        <label for="onboarding_company_name">Название компании / бизнеса</label>
                        <input id="onboarding_company_name" type="text" name="company_name" required maxlength="255" value="<?= Html::encode($formValues['company_name']) ?>">
                    </div>

                    <div class="form-field" data-character-counter>
                        <label for="onboarding_description">Описание бизнеса для контекста ИИ</label>
                        <textarea
                            id="onboarding_description"
                            name="description"
                            maxlength="<?= $descriptionMaxLength ?>"
                            data-character-counter-input
                            placeholder="Опишите кратко особенности вашего бизнеса, аудиторию, подробнее можно будет расписать в базах знаний."
                        ><?= Html::encode($formValues['description']) ?></textarea>
                        <p class="form-field__counter" data-character-counter-output aria-live="polite"></p>
                    </div>

                    <div class="form-field">
                        <label for="onboarding_phone">Телефон</label>
                        <input id="onboarding_phone" type="tel" name="phone" data-phone-mask value="<?= Html::encode($formValues['phone']) ?>" placeholder="+7 (___) ___-__-__">
                    </div>

                    <div class="onboarding__actions">
                        <button type="submit" class="btn btn-primary" data-onboarding-next>Далее</button>
                    </div>
                </form>
            </div>

            <div class="onboarding__step" data-onboarding-step="2" hidden>
                <h2 class="onboarding__step-title">База знаний</h2>
                <p class="onboarding__step-lead">
                    Создайте базу — статьи можно добавить позже. Без базы ИИ ассистенту не на что опираться.
                </p>

                <form data-onboarding-knowledge-form class="has-form-preloader" novalidate>
                    <div class="form-field">
                        <label for="onboarding_kb_title">Название базы</label>
                        <input
                            id="onboarding_kb_title"
                            type="text"
                            name="title"
                            required
                            maxlength="255"
                            placeholder="Например: Основная база"
                            <?= $hasKnowledgeBase ? 'disabled' : '' ?>
                            value="<?= $hasKnowledgeBase ? 'База уже создана' : '' ?>"
                        >
                    </div>

                    <?php if ($hasKnowledgeBase): ?>
                        <p class="form-success" data-onboarding-kb-ready>База знаний уже есть — можно переходить дальше.</p>
                    <?php endif; ?>

                    <div class="onboarding__actions">
                        <button type="button" class="btn btn-secondary" data-onboarding-back>Назад</button>
                        <button type="submit" class="btn btn-primary" data-onboarding-next>Далее</button>
                    </div>
                </form>
            </div>

            <div class="onboarding__step" data-onboarding-step="3" hidden>
                <h2 class="onboarding__step-title">Подключение канала</h2>
                <p class="onboarding__step-lead">
                    Пока доступен только Telegram. Ассистент будет отвечать клиентам в вашей личной переписке
                    от вашего имени. Создайте бота, включите у него режим секретаря и подключите его в разделе
                    «Автоматизация чатов».
                </p>

                <?php
                $tooltipId = 'onboarding-botfather-tooltip';
                $botUsernamePlaceholder = $telegramDisplayName ?? '@my_salon_bot';
                ?>

                <ol class="instruction-steps onboarding__instructions">
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

                            <form data-onboarding-telegram-form class="has-form-preloader telegram-token-form" novalidate>
                                <?php if ($isTelegramConnected): ?>
                                    <div class="channel-status" data-onboarding-telegram-status>
                                        <div class="channel-status__badge">Подключено</div>
                                        <div class="channel-status__meta">
                                            <?php if ($telegramDisplayName !== null): ?>
                                                <strong><?= Html::encode($telegramDisplayName) ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="form-field" data-onboarding-telegram-token-field>
                                    <label for="onboarding_bot_token">Токен бота</label>
                                    <input
                                        id="onboarding_bot_token"
                                        type="password"
                                        name="bot_token"
                                        autocomplete="off"
                                        spellcheck="false"
                                        placeholder="123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw"
                                        <?= $isTelegramConnected ? '' : 'required' ?>
                                    >
                                </div>

                                <div class="onboarding__actions">
                                    <button type="button" class="btn btn-secondary" data-onboarding-back>Назад</button>
                                    <button type="submit" class="btn btn-primary" data-onboarding-connect>
                                        <?= $isTelegramConnected ? 'Обновить токен' : 'Подключить бота' ?>
                                    </button>
                                </div>

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
                                    (<code data-telegram-bot-username><?= Html::encode($botUsernamePlaceholder) ?></code>).
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

                            <div data-onboarding-assign-kb>
                                <h3 class="onboarding__subheading">Привяжите базу знаний</h3>
                                <p>
                                    Выберите базы, на которые ассистент будет опираться в ответах.
                                    По тарифу доступно до <?= Html::encode((string) $maxKnowledgeBases) ?>
                                    <?= $maxKnowledgeBases === 1 ? 'базы' : 'баз' ?>.
                                </p>

                                <?php if ($knowledgeBases === []): ?>
                                    <p class="instruction-step__note">
                                        База знаний ещё не создана — вернитесь на предыдущий шаг настройки.
                                    </p>
                                <?php else: ?>
                                    <fieldset class="telegram-kb-form__fieldset" data-onboarding-kb-fieldset<?= $isTelegramConnected ? '' : ' disabled' ?>>
                                        <legend>Базы знаний для Telegram</legend>
                                        <div class="telegram-kb-checkboxes">
                                            <?php foreach ($knowledgeBases as $knowledgeBase): ?>
                                                <?php
                                                $isAssigned = in_array($knowledgeBase->getId(), $assignedKnowledgeBaseIds, true)
                                                    || ($assignedKnowledgeBaseIds === [] && $knowledgeBase === $knowledgeBases[0]);
                                                $isDisabled = !$knowledgeBase->isActive();
                                                $checkboxId = 'onboarding_kb_' . $knowledgeBase->getId();
                                                ?>
                                                <label class="kb-checkbox telegram-kb-checkbox<?= $isDisabled ? ' is-disabled' : '' ?>" for="<?= Html::encode($checkboxId) ?>">
                                                    <input
                                                        id="<?= Html::encode($checkboxId) ?>"
                                                        type="checkbox"
                                                        name="knowledge_base_ids[]"
                                                        value="<?= Html::encode($knowledgeBase->getId()) ?>"
                                                        data-onboarding-kb-checkbox
                                                        <?= $isAssigned ? ' checked' : '' ?>
                                                        <?= $isDisabled || !$isTelegramConnected ? ' disabled' : '' ?>
                                                    >
                                                    <span><?= Html::encode($knowledgeBase->getTitle()) ?></span>
                                                    <?php if ($isDisabled): ?>
                                                        <span class="telegram-kb-checkbox__meta">(неактивна)</span>
                                                    <?php endif; ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </fieldset>
                                    <?php if (!$isTelegramConnected) { ?>
                                        <p class="instruction-step__note" data-onboarding-kb-need-bot>
                                            Сначала подключите бота в пункте 2 — после этого можно выбрать базы и завершить настройку.
                                        </p>
                                    <?php } else { ?>
                                        <p class="instruction-step__note" data-onboarding-kb-need-bot>
                                            Изменить настройки можно будет позже в личном кабинете.
                                        </p>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>

                            <div class="onboarding__actions onboarding__actions--finish">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    data-onboarding-finish
                                    <?= $isTelegramConnected ? '' : 'disabled' ?>
                                >
                                    Завершить настройку
                                </button>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
