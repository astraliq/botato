<?php

declare(strict_types=1);

namespace App\Web\Landing;

use App\Application\Auth\AuthSessionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class Action
{
    public function __construct(
        private readonly ViewRenderer $viewRenderer,
        private readonly AuthSessionService $authSessionService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mailerFrom,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/template', [
            'isAuthenticated' => $this->authSessionService->isAuthenticated(),
            'cabinetUrl' => $this->urlGenerator->generate('cabinet'),
            'mailerFrom' => $this->mailerFrom,
        ]);
    }
}
