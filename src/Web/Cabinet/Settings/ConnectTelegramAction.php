<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Channel\ConnectTelegram\ConnectTelegramChannelCommand;
use App\Application\Channel\ConnectTelegram\ConnectTelegramChannelHandler;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Shared\ArrayHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ConnectTelegramAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly ConnectTelegramChannelHandler $connectTelegramChannelHandler,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $parsedBody = $request->getParsedBody();
        $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];
        $command = ConnectTelegramChannelCommand::fromArray($formData);

        $successMessage = null;
        $errorMessage = null;
        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $user->getId(),
            ChannelProvider::Telegram,
        );

        try {
            $telegramChannel = $this->connectTelegramChannelHandler->handle($user->getId(), $command);
            $successMessage = 'Telegram-бот подключён.';
        } catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'telegramChannel' => $telegramChannel,
            'telegramSecretHint' => $telegramChannel !== null
                ? $this->channelRepository->findSecretHint($telegramChannel->getId())
                : null,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }
}
