<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Onboarding;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Onboarding\CompleteOnboarding\CompleteOnboardingHandler;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Router\UrlGeneratorInterface;

final class CompleteAction
{
    public function __construct(
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly CompleteOnboardingHandler $completeOnboardingHandler,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getCurrentUserHandler->handle();
            $payload = JsonBodyParser::parse($request);
            $knowledgeBaseIds = [];

            if (isset($payload['knowledge_base_ids']) && is_array($payload['knowledge_base_ids'])) {
                foreach ($payload['knowledge_base_ids'] as $knowledgeBaseId) {
                    if (is_string($knowledgeBaseId) && $knowledgeBaseId !== '') {
                        $knowledgeBaseIds[] = $knowledgeBaseId;
                    }
                }
            }

            $this->completeOnboardingHandler->handle($user->getId(), $knowledgeBaseIds);

            return $this->jsonResponseFactory->success([
                'message' => 'Настройка завершена.',
                'redirect' => $this->urlGenerator->generate('cabinet/settings'),
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to complete onboarding.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось завершить настройку.', 500);
        }
    }
}
