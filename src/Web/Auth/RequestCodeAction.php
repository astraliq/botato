<?php

declare(strict_types=1);

namespace App\Web\Auth;

use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Shared\ArrayHelper;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class RequestCodeAction
{
    public function __construct(
        private RequestVerificationCodeHandler $handler,
        private JsonResponseFactory $jsonResponseFactory,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $payload = JsonBodyParser::parse($request);
            $email = ArrayHelper::toString($payload['email'] ?? null);
            $this->handler->handle($email);

            return $this->jsonResponseFactory->success([
                'message' => 'Код отправлен на email.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to send verification code.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось отправить код.', 500);
        }
    }
}
