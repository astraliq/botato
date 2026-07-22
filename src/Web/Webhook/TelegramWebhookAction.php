<?php

declare(strict_types=1);

namespace App\Web\Webhook;

use App\Application\Channel\Telegram\HandleUpdate\HandleTelegramUpdateHandler;
use App\Web\Shared\JsonBodyParser;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class TelegramWebhookAction
{
    public function __construct(
        private readonly HandleTelegramUpdateHandler $handleTelegramUpdateHandler,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request, string $channelId): ResponseInterface
    {
        $secretTokenHeader = $request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');

        try {
            $update = JsonBodyParser::parse($request);
            $this->handleTelegramUpdateHandler->handle(
                $channelId,
                $secretTokenHeader,
                $update,
            );
        } catch (InvalidArgumentException) {
            return $this->emptyResponse(403);
        } catch (Throwable) {
            return $this->emptyResponse(500);
        }

        return $this->emptyResponse(200);
    }

    private function emptyResponse(int $status): ResponseInterface
    {
        return $this->responseFactory->createResponse($status);
    }
}
