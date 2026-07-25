<?php

declare(strict_types=1);

use App\Domain\Ai\AiProvider;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var list<AiProvider> $providers
 * @var array<string, string|null> $secretHints
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var UrlGeneratorInterface $urlGenerator
 * @var string|null $csrf
 */

$this->setTitle('AI-провайдеры — Админка');
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1>AI-провайдеры</h1>
            </div>
            <p class="cabinet__lead">Управление поставщиками и API-ключами для ответов бота.</p>

            <?php if ($successMessage !== null): ?>
                <div class="form-success"><?= Html::encode($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="form-error"><?= Html::encode($errorMessage) ?></div>
            <?php endif; ?>

            <p>
                <a class="btn btn-primary" href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/providers/create')) ?>">
                    Добавить провайдера
                </a>
            </p>

            <?php if ($providers === []): ?>
                <p class="cabinet__lead">Провайдеры ещё не добавлены.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Код</th>
                                <th>Название</th>
                                <th>Статус</th>
                                <th>Ключ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): ?>
                                <?php
                                $hint = $secretHints[$provider->getId()] ?? null;
                                $statusParts = [];
                                if ($provider->isDefault()) {
                                    $statusParts[] = 'default';
                                }
                                $statusParts[] = $provider->isActive() ? 'active' : 'inactive';
                                ?>
                                <tr>
                                    <td><code><?= Html::encode($provider->getCode()) ?></code></td>
                                    <td><?= Html::encode($provider->getName()) ?></td>
                                    <td><?= Html::encode(implode(', ', $statusParts)) ?></td>
                                    <td><?= $hint !== null ? '••••' . Html::encode($hint) : '—' ?></td>
                                    <td class="admin-table__actions">
                                        <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/providers/edit', ['id' => $provider->getId()])) ?>">Изменить</a>
                                        <a href="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/models', ['id' => $provider->getId()])) ?>">Модели</a>
                                        <form method="post" action="<?= Html::encode($urlGenerator->generate('cabinet/admin/ai/providers/delete', ['id' => $provider->getId()])) ?>" class="admin-inline-form" onsubmit="return confirm('Удалить провайдера?');">
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
