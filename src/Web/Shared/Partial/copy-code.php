<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $text Текст для копирования и отображения в <code>
 * @var string|null $label Подпись кнопки
 */

$label ??= 'Скопировать';
?>
<span class="copy-code">
    <code><?= Html::encode($text) ?></code>
    <button
        type="button"
        class="copy-code__button"
        data-copy-button
        data-copy-text="<?= Html::encode($text) ?>"
        aria-label="<?= Html::encode($label . ': ' . $text) ?>"
    >
        <?= Html::encode($label) ?>
    </button>
</span>
