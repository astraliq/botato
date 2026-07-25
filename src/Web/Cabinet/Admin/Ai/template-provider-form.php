<?php

declare(strict_types=1);

use App\Domain\Ai\AiApiStyle;
use App\Domain\Ai\AiProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var AiProvider|null $provider
 * @var array{
 *     code: string,
 *     name: string,
 *     base_url: string,
 *     api_host: string,
 *     api_style: string,
 *     sort_order: string,
 *     is_active: bool,
 *     is_default: bool,
 *     chat_completions_path: string,
 *     default_max_tokens: string,
 *     api_key: string
 * } $formValues
 * @var string|null $secretHint
 * @var list<AiApiStyle> $apiStyles
 * @var string|null $errorMessage
 * @var string|null $successMessage
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Провайдер AI — Админка');
if ($provider === null) {
    $isCreate = true;
    $actionUrl = $urlGenerator->generate('cabinet/admin/ai/providers/create');
    $heading = 'Новый AI-провайдер';
    $modelsUrl = null;
} else {
    $isCreate = false;
    $actionUrl = $urlGenerator->generate('cabinet/admin/ai/providers/edit', ['id' => $provider->getId()]);
    $heading = 'Редактирование провайдера';
    $modelsUrl = $urlGenerator->generate('cabinet/admin/ai/models', ['id' => $provider->getId()]);
}
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1><?= $heading ?></h1>
            </div>

            <p>
                <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai')) ?>">← К списку</a>
                <?php if ($modelsUrl !== null): ?>
                    ·
                    <a href="<?= Html::encode($modelsUrl) ?>">Модели</a>
                <?php endif; ?>
            </p>

            <?php if ($successMessage !== null): ?>
                <div class="form-success"><?= Html::encode($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="form-error"><?= Html::encode($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= Html::encode($actionUrl) ?>">
                <?php if ($csrf !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                <?php endif; ?>

                <div class="form-field">
                    <label for="code">Код</label>
                    <input
                        id="code"
                        type="text"
                        name="code"
                        value="<?= Html::encode($formValues['code']) ?>"
                        <?= $isCreate ? 'required' : 'readonly' ?>
                        pattern="[a-z0-9_-]+"
                        placeholder="aitunnel"
                    >
                </div>

                <div class="form-field">
                    <label for="name">Название</label>
                    <input id="name" type="text" name="name" value="<?= Html::encode($formValues['name']) ?>" required>
                </div>

                <div class="form-field">
                    <label for="base_url">Base URL</label>
                    <input id="base_url" type="url" name="base_url" value="<?= Html::encode($formValues['base_url']) ?>" required>
                </div>

                <div class="form-field">
                    <label for="api_host">API host</label>
                    <input id="api_host" type="text" name="api_host" value="<?= Html::encode($formValues['api_host']) ?>" required>
                </div>

                <div class="form-field">
                    <label for="api_style">API style</label>
                    <select id="api_style" name="api_style" required>
                        <?php foreach ($apiStyles as $apiStyle): ?>
                            <option
                                value="<?= Html::encode($apiStyle->value) ?>"
                                <?= $formValues['api_style'] === $apiStyle->value ? 'selected' : '' ?>
                            >
                                <?= Html::encode($apiStyle->value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="chat_completions_path">chat_completions_path</label>
                    <input id="chat_completions_path" type="text" name="chat_completions_path" value="<?= Html::encode($formValues['chat_completions_path']) ?>">
                </div>

                <div class="form-field">
                    <label for="default_max_tokens">default_max_tokens</label>
                    <input id="default_max_tokens" type="number" name="default_max_tokens" min="1" value="<?= Html::encode($formValues['default_max_tokens']) ?>">
                </div>

                <div class="form-field">
                    <label for="sort_order">Порядок сортировки</label>
                    <input id="sort_order" type="number" name="sort_order" value="<?= Html::encode($formValues['sort_order']) ?>">
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?= $formValues['is_active'] ? 'checked' : '' ?>>
                        Активен
                    </label>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="is_default" value="1" <?= $formValues['is_default'] ? 'checked' : '' ?>>
                        Провайдер по умолчанию
                    </label>
                </div>

                <div class="form-field">
                    <label for="api_key">API-ключ<?= $secretHint !== null ? ' (сейчас ••••' . Html::encode($secretHint) . ')' : '' ?></label>
                    <input id="api_key" type="password" name="api_key" value="" autocomplete="new-password" placeholder="<?= $secretHint !== null ? 'Оставьте пустым, чтобы не менять' : 'Вставьте ключ' ?>">
                </div>

                <button type="submit" class="btn btn-primary"><?= $isCreate ? 'Создать' : 'Сохранить' ?></button>
            </form>
        </div>
    </div>
</section>
