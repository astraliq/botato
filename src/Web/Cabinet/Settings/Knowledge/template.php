<?php

declare(strict_types=1);

use App\Domain\KnowledgeBase\KnowledgeArticle;
use App\Domain\KnowledgeBase\KnowledgeBase;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var list<KnowledgeBase> $knowledgeBases
 * @var KnowledgeBase|null $selectedKnowledgeBase
 * @var list<KnowledgeArticle> $articles
 * @var bool $isTelegramConnected
 * @var string|null $telegramKnowledgeBaseId
 * @var int $maxKnowledgeBases
 * @var string|null $csrf
 */

$this->setTitle('Базы знаний — Botato');

$manageUrl = $urlGenerator->generate('api/cabinet/settings/knowledge');
$knowledgePageUrl = $urlGenerator->generate('cabinet/settings/knowledge');
$canCreateMore = count($knowledgeBases) < $maxKnowledgeBases;
?>

<section class="cabinet" data-kb-page data-kb-page-url="<?= Html::encode($knowledgePageUrl) ?>">
    <div class="container">
        <div class="cabinet__layout">
            <?= $this->render(dirname(__DIR__) . '/Partial/settings-tabs', [
                'urlGenerator' => $urlGenerator,
                'activeTab' => 'knowledge',
                'isTelegramConnected' => $isTelegramConnected,
            ]) ?>

            <div class="cabinet__card">
                <div class="cabinet__head">
                    <h1>Базы знаний</h1>
                </div>

                <p class="cabinet__lead">
                    Добавьте материалы о бизнесе — ассистент будет опираться на них в ответах клиентам.
                    <?php if ($maxKnowledgeBases > 0): ?>
                        Доступно баз: <?= Html::encode((string) count($knowledgeBases)) ?> из <?= Html::encode((string) $maxKnowledgeBases) ?>.
                    <?php endif; ?>
                </p>

                <div class="form-success" data-kb-success hidden></div>

                <div class="kb-pills" role="tablist" aria-label="Базы знаний">
                    <?php foreach ($knowledgeBases as $knowledgeBase): ?>
                        <?php
                        $isSelected = $selectedKnowledgeBase !== null
                            && $selectedKnowledgeBase->getId() === $knowledgeBase->getId();
                        $isAssignedToTelegram = $telegramKnowledgeBaseId === $knowledgeBase->getId();
                        $pillUrl = $urlGenerator->generate('cabinet/settings/knowledge', [
                            'kb' => $knowledgeBase->getId(),
                        ]);
                        ?>
                        <a
                            href="<?= Html::encode($pillUrl) ?>"
                            class="kb-pill<?= $isSelected ? ' is-active' : '' ?>"
                            role="tab"
                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                            data-kb-pill
                            data-kb-id="<?= Html::encode($knowledgeBase->getId()) ?>"
                        >
                            <span data-kb-pill-title><?= Html::encode($knowledgeBase->getTitle()) ?></span>
                            <?php if ($isAssignedToTelegram): ?>
                                <span class="kb-pill__badge" title="Используется в Telegram" data-kb-tg-badge>TG</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($canCreateMore): ?>
                    <form
                        method="post"
                        action="<?= Html::encode($manageUrl) ?>"
                        class="kb-create-base has-form-preloader"
                        data-kb-form
                    >
                        <input type="hidden" name="form_action" value="create_base">
                        <div class="form-field-row">
                            <div class="form-field">
                                <label for="new_kb_title">Новая база знаний</label>
                                <input
                                    id="new_kb_title"
                                    type="text"
                                    name="title"
                                    required
                                    maxlength="255"
                                    placeholder="Например: Услуги и прайс"
                                >
                            </div>
                            <div class="form-field kb-create-base__actions">
                                <label class="kb-create-base__spacer" for="new_kb_submit">&nbsp;</label>
                                <button id="new_kb_submit" type="submit" class="btn btn-primary">Создать</button>
                            </div>
                        </div>
                        <div class="form-error" data-kb-form-error hidden></div>
                        <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                            'text' => 'Создаём…',
                        ]) ?>
                    </form>
                <?php elseif ($knowledgeBases === []): ?>
                    <p class="cabinet__lead">На вашем тарифе нет доступных слотов для баз знаний.</p>
                <?php endif; ?>

                <?php if ($selectedKnowledgeBase !== null): ?>
                    <div class="kb-panel" data-kb-panel data-kb-id="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">
                        <div class="kb-panel__head">
                            <h2 data-kb-panel-title><?= Html::encode($selectedKnowledgeBase->getTitle()) ?></h2>
                            <div data-kb-telegram-actions>
                                <?php if ($isTelegramConnected && $telegramKnowledgeBaseId !== $selectedKnowledgeBase->getId()): ?>
                                    <form
                                        method="post"
                                        action="<?= Html::encode($manageUrl) ?>"
                                        class="has-form-preloader"
                                        data-kb-form
                                    >
                                        <input type="hidden" name="form_action" value="assign_telegram">
                                        <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">
                                        <button type="submit" class="btn btn-secondary">Использовать в Telegram</button>
                                        <div class="form-error" data-kb-form-error hidden></div>
                                        <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                            'text' => 'Привязываем…',
                                        ]) ?>
                                    </form>
                                <?php elseif ($isTelegramConnected && $telegramKnowledgeBaseId === $selectedKnowledgeBase->getId()): ?>
                                    <span class="kb-panel__status">Используется в Telegram</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form
                            method="post"
                            action="<?= Html::encode($manageUrl) ?>"
                            class="kb-base-form has-form-preloader"
                            data-kb-form
                            data-kb-no-reload
                        >
                            <input type="hidden" name="form_action" value="update_base">
                            <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">

                            <div class="form-field">
                                <label for="kb_title">Название</label>
                                <input
                                    id="kb_title"
                                    type="text"
                                    name="title"
                                    required
                                    maxlength="255"
                                    value="<?= Html::encode($selectedKnowledgeBase->getTitle()) ?>"
                                    data-kb-title-input
                                >
                            </div>

                            <div class="form-field">
                                <label for="kb_summary">Краткое описание</label>
                                <textarea
                                    id="kb_summary"
                                    name="summary"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Необязательно — для вас, не уходит в промпт как статья"
                                ><?= Html::encode($selectedKnowledgeBase->getSummary()) ?></textarea>
                            </div>

                            <label class="kb-checkbox">
                                <input type="checkbox" name="is_active" value="1"<?= $selectedKnowledgeBase->isActive() ? ' checked' : '' ?>>
                                <span>Активна</span>
                            </label>

                            <div class="kb-actions">
                                <button type="submit" class="btn btn-primary">Сохранить базу</button>
                            </div>
                            <div class="form-error" data-kb-form-error hidden></div>
                            <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                'text' => 'Сохраняем…',
                            ]) ?>
                        </form>

                        <form
                            method="post"
                            action="<?= Html::encode($manageUrl) ?>"
                            class="kb-delete-form has-form-preloader"
                            data-kb-form
                            data-kb-confirm="Удалить базу знаний и все её статьи?"
                        >
                            <input type="hidden" name="form_action" value="delete_base">
                            <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">
                            <button type="submit" class="btn btn-ghost">Удалить базу</button>
                            <div class="form-error" data-kb-form-error hidden></div>
                            <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                'text' => 'Удаляем…',
                            ]) ?>
                        </form>

                        <div class="kb-articles">
                            <h3>Статьи</h3>
                            <p class="kb-articles__hint">
                                Каждая статья — отдельный блок знаний (услуги, FAQ, условия). Ассистент читает активные статьи.
                            </p>

                            <?php if ($articles === []): ?>
                                <p class="kb-articles__empty">Пока нет статей — добавьте первую ниже.</p>
                            <?php endif; ?>

                            <?php foreach ($articles as $article): ?>
                                <article class="kb-article" data-kb-article data-article-id="<?= Html::encode($article->getId()) ?>">
                                    <form
                                        method="post"
                                        action="<?= Html::encode($manageUrl) ?>"
                                        class="has-form-preloader"
                                        data-kb-form
                                        data-kb-no-reload
                                    >
                                        <input type="hidden" name="form_action" value="update_article">
                                        <input type="hidden" name="article_id" value="<?= Html::encode($article->getId()) ?>">
                                        <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">

                                        <div class="form-field">
                                            <label for="article_title_<?= Html::encode($article->getId()) ?>">Заголовок</label>
                                            <input
                                                id="article_title_<?= Html::encode($article->getId()) ?>"
                                                type="text"
                                                name="title"
                                                required
                                                maxlength="255"
                                                value="<?= Html::encode($article->getTitle()) ?>"
                                            >
                                        </div>

                                        <div class="form-field">
                                            <label for="article_content_<?= Html::encode($article->getId()) ?>">Текст</label>
                                            <textarea
                                                id="article_content_<?= Html::encode($article->getId()) ?>"
                                                name="content"
                                                rows="6"
                                                required
                                                maxlength="50000"
                                            ><?= Html::encode($article->getContent()) ?></textarea>
                                        </div>

                                        <label class="kb-checkbox">
                                            <input type="checkbox" name="is_active" value="1"<?= $article->isActive() ? ' checked' : '' ?>>
                                            <span>Активна (учитывается в ответах)</span>
                                        </label>

                                        <div class="kb-actions">
                                            <button type="submit" class="btn btn-primary">Сохранить статью</button>
                                        </div>
                                        <div class="form-error" data-kb-form-error hidden></div>
                                        <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                            'text' => 'Сохраняем…',
                                        ]) ?>
                                    </form>

                                    <form
                                        method="post"
                                        action="<?= Html::encode($manageUrl) ?>"
                                        class="has-form-preloader"
                                        data-kb-form
                                        data-kb-confirm="Удалить эту статью?"
                                    >
                                        <input type="hidden" name="form_action" value="delete_article">
                                        <input type="hidden" name="article_id" value="<?= Html::encode($article->getId()) ?>">
                                        <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">
                                        <button type="submit" class="btn btn-ghost">Удалить статью</button>
                                        <div class="form-error" data-kb-form-error hidden></div>
                                        <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                            'text' => 'Удаляем…',
                                        ]) ?>
                                    </form>
                                </article>
                            <?php endforeach; ?>

                            <div class="kb-article kb-article--new">
                                <h4>Новая статья</h4>
                                <form
                                    method="post"
                                    action="<?= Html::encode($manageUrl) ?>"
                                    class="has-form-preloader"
                                    data-kb-form
                                >
                                    <input type="hidden" name="form_action" value="create_article">
                                    <input type="hidden" name="knowledge_base_id" value="<?= Html::encode($selectedKnowledgeBase->getId()) ?>">

                                    <div class="form-field">
                                        <label for="new_article_title">Заголовок</label>
                                        <input
                                            id="new_article_title"
                                            type="text"
                                            name="title"
                                            required
                                            maxlength="255"
                                            placeholder="Например: Часы работы"
                                        >
                                    </div>

                                    <div class="form-field">
                                        <label for="new_article_content">Текст</label>
                                        <textarea
                                            id="new_article_content"
                                            name="content"
                                            rows="6"
                                            required
                                            maxlength="50000"
                                            placeholder="Напишите факты, которые ассистент должен знать…"
                                        ></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Добавить статью</button>
                                    <div class="form-error" data-kb-form-error hidden></div>
                                    <?= $this->render(dirname(__DIR__, 3) . '/Shared/Partial/form-preloader', [
                                        'text' => 'Добавляем…',
                                    ]) ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif ($knowledgeBases === []): ?>
                    <p class="kb-articles__empty">Создайте первую базу знаний, чтобы добавлять статьи.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
