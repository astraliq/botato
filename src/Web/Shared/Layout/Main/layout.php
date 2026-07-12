<?php

declare(strict_types=1);

use App\Application\Auth\AuthSessionService;
use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;

/**
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool|null $showLandingHeader
 * @var bool|null $includeAuthModal
 * @var bool|null $includePricingScript
 * @var bool|null $includePhoneMask
 */

$assetManager->register(MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$showLandingHeader = $showLandingHeader ?? true;
$includeAuthModal = $includeAuthModal ?? true;
$includePricingScript = $includePricingScript ?? false;
$includePhoneMask = $includePhoneMask ?? false;
$csrfToken = isset($csrf) && is_string($csrf) ? $csrf : '';

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Html::encode($csrfToken) ?>">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
    <script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js" defer></script>
    <script src="<?= $aliases->get('@assetsUrl/js/auth-modal.js') ?>" defer></script>
    <?php if ($includePricingScript): ?>
        <script src="<?= $aliases->get('@assetsUrl/js/pricing-toggle.js') ?>" defer></script>
    <?php endif; ?>
    <?php if ($includePhoneMask): ?>
        <script src="<?= $aliases->get('@assetsUrl/js/phone-mask.js') ?>" defer></script>
    <?php endif; ?>
</head>
<body>
<?php $this->beginBody() ?>

<?php if ($showLandingHeader): ?>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="<?= $urlGenerator->generate('home') ?>" class="logo">
                <span class="logo__mark">Bt</span>
                <span><?= Html::encode($applicationParams->name) ?></span>
            </a>
            <nav class="site-nav">
                <a href="#features">Возможности</a>
                <a href="#channels">Каналы</a>
                <a href="#pricing">Тарифы</a>
                <a href="#faq">FAQ</a>
            </nav>
            <div class="site-actions">
                <?php if (($isAuthenticated ?? false) === true): ?>
                    <a href="<?= $urlGenerator->generate('cabinet') ?>" class="btn btn-secondary">Личный кабинет</a>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" data-auth-open>Войти</button>
                <?php endif; ?>
            </div>
        </div>
    </header>
<?php endif; ?>

<?= $content ?>

<?php if ($includeAuthModal && ($isAuthenticated ?? false) !== true): ?>
    <div id="auth-modal" class="modal" aria-hidden="true">
        <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
            <div class="modal__head">
                <h3 id="auth-modal-title">Вход в Botato</h3>
                <button type="button" class="modal__close" data-auth-close aria-label="Закрыть">&times;</button>
            </div>

            <div data-auth-error class="form-error" hidden></div>
            <div data-auth-success class="form-success" hidden></div>

            <div data-auth-step="email">
                <p style="color:#64748b;margin-top:0;">Введите email — мы отправим код для входа или регистрации.</p>
                <form data-auth-email-form>
                    <div class="form-field">
                        <label for="auth-email">Email</label>
                        <input id="auth-email" type="email" name="email" required data-auth-email-input placeholder="you@example.com">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Получить код</button>
                </form>
            </div>

            <div data-auth-step="code" hidden>
                <p style="color:#64748b;margin-top:0;">Введите 6-значный код из письма.</p>
                <form data-auth-code-form>
                    <div class="form-field">
                        <label for="auth-code">Код подтверждения</label>
                        <input id="auth-code" type="text" name="code" required maxlength="6" pattern="\d{6}" data-auth-code-input placeholder="123456">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Войти</button>
                    <button type="button" class="btn btn-ghost" data-auth-back style="width:100%;margin-top:8px;">Изменить email</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
