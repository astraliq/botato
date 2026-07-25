<?php

declare(strict_types=1);

use App\Domain\Ai\AiModel;
use App\Domain\Ai\AiProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var AiProvider $provider
 * @var list<AiModel> $models
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('Модели — ' . $provider->getName());
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1>Модели: <?= Html::encode($provider->getName()) ?></h1>
            </div>

            <p>
                <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai')) ?>">← К провайдерам</a>
                ·
                <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/providers/edit', ['id' => $provider->getId()])) ?>">Настройки провайдера</a>
            </p>

            <p>
                <a
                    class="btn btn-primary"
                    href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/models/create', ['id' => $provider->getId()])) ?>"
                >
                    Добавить модель
                </a>
            </p>

            <?php if ($models === []): ?>
                <p class="cabinet__lead">Моделей пока нет.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Код</th>
                                <th>Название</th>
                                <th>Purpose</th>
                                <th>Статус</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models as $model): ?>
                                <?php
                                $statusParts = [];
                                if ($model->isDefault()) {
                                    $statusParts[] = 'default';
                                }
                                $statusParts[] = $model->isActive() ? 'active' : 'inactive';
                                ?>
                                <tr>
                                    <td><code><?= Html::encode($model->getCode()) ?></code></td>
                                    <td><?= Html::encode($model->getName()) ?></td>
                                    <td><?= Html::encode($model->getPurpose()->value) ?></td>
                                    <td><?= Html::encode(implode(', ', $statusParts)) ?></td>
                                    <td class="admin-table__actions">
                                        <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/models/edit', [
                                            'id' => $provider->getId(),
                                            'modelId' => $model->getId(),
                                        ])) ?>">Изменить</a>
                                        <form
                                            method="post"
                                            action="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/models/delete', [
                                                'id' => $provider->getId(),
                                                'modelId' => $model->getId(),
                                            ])) ?>"
                                            class="admin-inline-form"
                                            onsubmit="return confirm('Удалить модель?');"
                                        >
                                            <?php if ($csrf !== null): ?>
                                                <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                                            <?php endif; ?>
                                            <button type="submit" class="user-menu__item--button">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
