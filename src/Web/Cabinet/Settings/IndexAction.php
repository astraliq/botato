<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $user->getId(),
            ChannelProvider::Telegram,
        );

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'telegramChannel' => $telegramChannel,
            'telegramSecretHint' => $telegramChannel !== null
                ? $this->channelRepository->findSecretHint($telegramChannel->getId())
                : null,
            'successMessage' => null,
            'errorMessage' => null,
        ]);
    }
}
