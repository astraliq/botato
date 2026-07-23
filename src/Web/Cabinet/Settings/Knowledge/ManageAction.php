<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings\Knowledge;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\KnowledgeBase\AssignKnowledgeBaseToTelegram\AssignKnowledgeBaseToTelegramHandler;
use App\Application\KnowledgeBase\CreateKnowledgeArticle\CreateKnowledgeArticleHandler;
use App\Application\KnowledgeBase\CreateKnowledgeBase\CreateKnowledgeBaseHandler;
use App\Application\KnowledgeBase\DeleteKnowledgeArticle\DeleteKnowledgeArticleHandler;
use App\Application\KnowledgeBase\DeleteKnowledgeBase\DeleteKnowledgeBaseHandler;
use App\Application\KnowledgeBase\UpdateKnowledgeArticle\UpdateKnowledgeArticleHandler;
use App\Application\KnowledgeBase\UpdateKnowledgeBase\UpdateKnowledgeBaseHandler;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ManageAction
{
    public function __construct(
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly CreateKnowledgeBaseHandler $createKnowledgeBaseHandler,
        private readonly UpdateKnowledgeBaseHandler $updateKnowledgeBaseHandler,
        private readonly DeleteKnowledgeBaseHandler $deleteKnowledgeBaseHandler,
        private readonly AssignKnowledgeBaseToTelegramHandler $assignKnowledgeBaseToTelegramHandler,
        private readonly CreateKnowledgeArticleHandler $createKnowledgeArticleHandler,
        private readonly UpdateKnowledgeArticleHandler $updateKnowledgeArticleHandler,
        private readonly DeleteKnowledgeArticleHandler $deleteKnowledgeArticleHandler,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getCurrentUserHandler->handle();
            $formData = JsonBodyParser::parse($request);
            $formAction = isset($formData['form_action']) && is_string($formData['form_action'])
                ? $formData['form_action']
                : '';

            $knowledgeBaseId = $this->dispatch($user->getId(), $formAction, $formData);

            return $this->jsonResponseFactory->success([
                'message' => $this->successMessageFor($formAction),
                'formAction' => $formAction,
                'knowledgeBaseId' => $knowledgeBaseId,
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonResponseFactory->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Failed to manage knowledge base.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return $this->jsonResponseFactory->error('Не удалось выполнить действие.', 500);
        }
    }

    /**
     * @param array<string, mixed> $formData
     */
    private function dispatch(
        string $userId,
        string $formAction,
        array $formData,
    ): ?string {
        return match ($formAction) {
            'create_base' => $this->createKnowledgeBaseHandler->handle(
                userId: $userId,
                title: $this->stringValue($formData, 'title'),
                summary: $this->stringValue($formData, 'summary'),
            )->getId(),
            'update_base' => $this->updateKnowledgeBaseHandler->handle(
                userId: $userId,
                knowledgeBaseId: $this->requiredString($formData, 'knowledge_base_id'),
                title: $this->stringValue($formData, 'title'),
                summary: $this->stringValue($formData, 'summary'),
                isActive: $this->booleanValue($formData, 'is_active'),
            )->getId(),
            'delete_base' => $this->deleteBase($userId, $formData),
            'assign_telegram' => $this->assignTelegram($userId, $formData),
            'create_article' => $this->createArticle($userId, $formData),
            'update_article' => $this->updateArticle($userId, $formData),
            'delete_article' => $this->deleteArticle($userId, $formData),
            default => throw new InvalidArgumentException('Неизвестное действие.'),
        };
    }

    /** @param array<string, mixed> $formData */
    private function deleteBase(string $userId, array $formData): ?string
    {
        $knowledgeBaseId = $this->requiredString($formData, 'knowledge_base_id');
        $this->deleteKnowledgeBaseHandler->handle($userId, $knowledgeBaseId);

        return null;
    }

    /** @param array<string, mixed> $formData */
    private function assignTelegram(string $userId, array $formData): string
    {
        $knowledgeBaseId = $this->requiredString($formData, 'knowledge_base_id');
        $this->assignKnowledgeBaseToTelegramHandler->handle($userId, $knowledgeBaseId);

        return $knowledgeBaseId;
    }

    /** @param array<string, mixed> $formData */
    private function createArticle(string $userId, array $formData): string
    {
        $knowledgeBaseId = $this->requiredString($formData, 'knowledge_base_id');
        $this->createKnowledgeArticleHandler->handle(
            userId: $userId,
            knowledgeBaseId: $knowledgeBaseId,
            title: $this->stringValue($formData, 'title'),
            content: $this->stringValue($formData, 'content'),
        );

        return $knowledgeBaseId;
    }

    /** @param array<string, mixed> $formData */
    private function updateArticle(string $userId, array $formData): string
    {
        $article = $this->updateKnowledgeArticleHandler->handle(
            userId: $userId,
            articleId: $this->requiredString($formData, 'article_id'),
            title: $this->stringValue($formData, 'title'),
            content: $this->stringValue($formData, 'content'),
            isActive: $this->booleanValue($formData, 'is_active'),
        );

        return $article->getKnowledgeBaseId();
    }

    /** @param array<string, mixed> $formData */
    private function deleteArticle(string $userId, array $formData): string
    {
        return $this->deleteKnowledgeArticleHandler->handle(
            $userId,
            $this->requiredString($formData, 'article_id'),
        );
    }

    private function successMessageFor(string $formAction): string
    {
        return match ($formAction) {
            'create_base' => 'База знаний создана.',
            'update_base' => 'База знаний сохранена.',
            'delete_base' => 'База знаний удалена.',
            'assign_telegram' => 'База знаний привязана к Telegram.',
            'create_article' => 'Статья добавлена.',
            'update_article' => 'Статья сохранена.',
            'delete_article' => 'Статья удалена.',
            default => 'Сохранено.',
        };
    }

    /** @param array<string, mixed> $formData */
    private function stringValue(array $formData, string $key): string
    {
        $value = $formData[$key] ?? '';

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) ? $value : '';
    }

    /** @param array<string, mixed> $formData */
    private function booleanValue(array $formData, string $key): bool
    {
        $value = $formData[$key] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    /** @param array<string, mixed> $formData */
    private function requiredString(array $formData, string $key): string
    {
        $value = $this->stringValue($formData, $key);

        if ($value === '') {
            throw new InvalidArgumentException('Не указан обязательный параметр.');
        }

        return $value;
    }
}
