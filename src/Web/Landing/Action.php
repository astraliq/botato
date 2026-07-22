<?php

declare(strict_types=1);

namespace App\Web\Landing;

use App\Domain\Plan\PlanRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class Action
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly string $mailerFrom,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/template', [
            'cabinetUrl' => $this->urlGenerator->generate('cabinet'),
            'mailerFrom' => $this->mailerFrom,
            'plans' => $this->planRepository->listVisible(),
        ]);
    }
}
