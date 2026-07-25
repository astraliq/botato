<?php

declare(strict_types=1);

use App\Domain\Ai\AiModel;
use App\Domain\Ai\AiModelPurpose;
use App\Domain\Ai\AiProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var AiProvider $provider
 * @var AiModel|null $model
 * @var array{
 *     code: string,
 *     name: string,
 *     purpose: string,
 *     sort_order: string,
 *     is_active: bool,
 *     is_default: bool
 * } $formValues
 * @var list<AiModelPurpose> $purposes
 * @var string|null $errorMessage
 * @var string|null $successMessage
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Модель AI — Админка');

$isCreate = $model === null;

if ($model === null) {
    $actionUrl = $urlGenerator->generate('cabinet/admin/ai/models/create', ['id' => $provider->getId()]);
    $heading = 'Новая модель';
} else {
    $actionUrl = $urlGenerator->generate('cabinet/admin/ai/models/edit', [
        'id' => $provider->getId(),
        'modelId' => $model->getId(),
    ]);
    $heading = 'Редактирование модели';
}
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1><?= $heading ?></h1>
            </div>

            <p>
                <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/models', ['id' => $provider->getId()])) ?>">
                    ← К моделям <?= Html::encode($provider->getName()) ?>
                </a>
            </p>

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
                    >
                </div>

                <div class="form-field">
                    <label for="name">Название</label>
                    <input id="name" type="text" name="name" value="<?= Html::encode($formValues['name']) ?>" required>
                </div>

                <div class="form-field">
                    <label for="purpose">Purpose</label>
                    <select id="purpose" name="purpose" required>
                        <?php foreach ($purposes as $purpose): ?>
                            <option
                                value="<?= Html::encode($purpose->value) ?>"
                                <?= $formValues['purpose'] === $purpose->value ? 'selected' : '' ?>
                            >
                                <?= Html::encode($purpose->value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="sort_order">Порядок сортировки</label>
                    <input id="sort_order" type="number" name="sort_order" value="<?= Html::encode($formValues['sort_order']) ?>">
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?= $formValues['is_active'] ? 'checked' : '' ?>>
                        Активна
                    </label>
                </div>

                <div class="form-field">
                    <label>
                        <input type="checkbox" name="is_default" value="1" <?= $formValues['is_default'] ? 'checked' : '' ?>>
                        По умолчанию для purpose
                    </label>
                </div>

                <button type="submit" class="btn btn-primary"><?= $isCreate ? 'Создать' : 'Сохранить' ?></button>
            </form>
        </div>
    </div>
</section>
