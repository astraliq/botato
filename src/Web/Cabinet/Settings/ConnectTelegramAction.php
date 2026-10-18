<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Channel\ConnectTelegram\ConnectTelegramChannelCommand;
use App\Application\Channel\ConnectTelegram\ConnectTelegramChannelHandler;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ConnectTelegramAction
{
    public function __construct(
        private GetCurrentUserHandler $getCurrentUserHandler,
        private ConnectTelegramChannelHandler $connectTelegramChannelHandler,
        private ChannelRepositoryInterface $channelRepository,
        private JsonResponseFactory $jsonResponseFactory,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getCurrentUserHandler->handle();
            $command = ConnectTelegramChannelCommand::fromArray(JsonBodyParser::parse($request));
            $telegramChannel = $this->connectTelegramChannelHandler->handle($user->getId(), $command);

            return $this->jsonResponseFactory->success([
                'message' => 'Telegram-бот подключён.',
                'displayName' => $telegramChannel->getDisplayName(),
                'secretHint' => $this->channelRepository->findSecretHint($telegramChannel->getId()),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to connect Telegram channel.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось подключить Telegram-бота.', 500);
        }
    }
}
