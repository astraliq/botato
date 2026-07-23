<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Support;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Support\ListTickets\ListSupportTicketsHandler;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly ListSupportTicketsHandler $listSupportTicketsHandler,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'tickets' => $this->listSupportTicketsHandler->handle($user->getId()),
            'question' => '',
            'successMessage' => null,
            'errorMessage' => null,
        ]);
    }
}
