<?php

declare(strict_types=1);

namespace App\Web\Landing;

use App\Domain\Plan\PlanRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private PlanRepositoryInterface $planRepository,
        private string $mailerFrom,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/template', [
            'cabinetUrl' => $this->urlGenerator->generate('cabinet'),
            'mailerFrom' => $this->mailerFrom,
            'plans' => $this->planRepository->listVisible(),
            'personalDataUrl' => $this->urlGenerator->generate('legal/personal-data'),
        ]);
    }
}
