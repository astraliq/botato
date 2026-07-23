<?php

declare(strict_types=1);

use App\Domain\Support\SupportTicket;
use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var list<SupportTicket> $tickets
 * @var string $question
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var string|null $csrf
 */

$this->setTitle('Поддержка — Botato');

$supportUrl = $urlGenerator->generate('cabinet/support');
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1>Поддержка</h1>
            </div>

            <p class="cabinet__lead">
                Опишите вопрос или проблему — мы разберёмся и ответим.
            </p>

            <?php if ($successMessage !== null): ?>
                <div class="form-success"><?= Html::encode($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="form-error"><?= Html::encode($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= Html::encode($supportUrl) ?>">
                <?php if ($csrf !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                <?php endif; ?>

                <div class="form-field" data-character-counter>
                    <label for="question">Ваш вопрос</label>
                    <textarea
                        id="question"
                        name="question"
                        required
                        maxlength="65535"
                        data-character-counter-input
                        placeholder="Например: не приходят ответы бота в Telegram, как подключить базу знаний…"
                    ><?= Html::encode($question) ?></textarea>
                    <p class="form-field__counter" data-character-counter-output aria-live="polite"></p>
                </div>

                <button type="submit" class="btn btn-primary">Отправить вопрос</button>
            </form>

            <?php if ($tickets !== []): ?>
                <div class="support-tickets">
                    <h2 class="support-tickets__title">Ваши обращения</h2>
                    <ul class="support-tickets__list">
                        <?php foreach ($tickets as $ticket): ?>
                            <li class="support-ticket">
                                <div class="support-ticket__meta">
                                    <span class="support-ticket__status <?= $ticket->isClosed() ? 'is-closed' : 'is-open' ?>">
                                        <?= $ticket->isClosed() ? 'Закрыт' : 'Открыт' ?>
                                    </span>
                                    <time datetime="<?= Html::encode($ticket->getCreatedAt()->format(DATE_ATOM)) ?>">
                                        <?= Html::encode($ticket->getCreatedAt()->format('d.m.Y H:i')) ?>
                                    </time>
                                </div>
                                <p class="support-ticket__question"><?= Html::encode($ticket->getQuestion()) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
