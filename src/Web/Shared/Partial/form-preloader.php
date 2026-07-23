<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string|null $text
 */

$preloaderText = $text ?? 'Загрузка…';
?>
<div class="form-preloader" data-form-preloader hidden aria-hidden="true">
    <span class="form-preloader__spinner" aria-hidden="true"></span>
    <span class="form-preloader__text"><?= Html::encode($preloaderText) ?></span>
</div>
