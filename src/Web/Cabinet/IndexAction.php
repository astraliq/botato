<?php

declare(strict_types=1);

namespace App\Web\Cabinet;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Profile\ProfileFormValues;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly ViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'user' => $user,
            'formValues' => ProfileFormValues::fromUser($user)->toArray(),
            'successMessage' => null,
            'errorMessage' => null,
        ]);
    }
}
