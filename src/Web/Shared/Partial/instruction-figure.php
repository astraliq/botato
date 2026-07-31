<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var string $src Путь к картинке относительно /images/
 * @var string $alt
 * @var string|null $caption
 */

$caption ??= null;
$imageUrl = '/images/' . ltrim($src, '/');
?>
<figure class="instruction-figure">
    <img
        class="instruction-figure__image"
        src="<?= Html::encode($imageUrl) ?>"
        alt="<?= Html::encode($alt) ?>"
        loading="lazy"
        decoding="async"
    >
    <?php if ($caption !== null && $caption !== ''): ?>
        <figcaption class="instruction-figure__caption"><?= Html::encode($caption) ?></figcaption>
    <?php endif; ?>
</figure>
