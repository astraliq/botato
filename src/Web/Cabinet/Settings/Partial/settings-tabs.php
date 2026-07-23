<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * @var UrlGeneratorInterface $urlGenerator
 * @var string $activeTab knowledge|telegram
 * @var bool $isTelegramConnected
 */

$knowledgeUrl = $urlGenerator->generate('cabinet/settings/knowledge');
$telegramUrl = $urlGenerator->generate('cabinet/settings');
?>
<nav class="cabinet__tabs" aria-label="Разделы настроек">
    <a
        href="<?= Html::encode($knowledgeUrl) ?>"
        class="cabinet__tab<?= $activeTab === 'knowledge' ? ' is-active' : '' ?>"
        <?= $activeTab === 'knowledge' ? 'aria-current="page"' : '' ?>
    >
        <span>Базы знаний</span>
    </a>
    <a
        href="<?= Html::encode($telegramUrl) ?>"
        class="cabinet__tab<?= $activeTab === 'telegram' ? ' is-active' : '' ?>"
        <?= $activeTab === 'telegram' ? 'aria-current="page"' : '' ?>
        data-telegram-tab
    >
        <span>Подключить Telegram</span>
        <?php if ($isTelegramConnected): ?>
            <span
                class="cabinet__tab-check"
                title="Telegram подключён"
                aria-label="Telegram подключён"
                data-telegram-tab-check
            >✓</span>
        <?php endif; ?>
    </a>
</nav>
