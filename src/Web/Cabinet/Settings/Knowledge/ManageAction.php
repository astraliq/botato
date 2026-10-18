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
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Web\Shared\JsonBodyParser;
use App\Web\Shared\JsonResponseFactory;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ManageAction
{
    public function __construct(
        private GetCurrentUserHandler $getCurrentUserHandler,
        private CreateKnowledgeBaseHandler $createKnowledgeBaseHandler,
        private UpdateKnowledgeBaseHandler $updateKnowledgeBaseHandler,
        private DeleteKnowledgeBaseHandler $deleteKnowledgeBaseHandler,
        private AssignKnowledgeBaseToTelegramHandler $assignKnowledgeBaseToTelegramHandler,
        private CreateKnowledgeArticleHandler $createKnowledgeArticleHandler,
        private UpdateKnowledgeArticleHandler $updateKnowledgeArticleHandler,
        private DeleteKnowledgeArticleHandler $deleteKnowledgeArticleHandler,
        private ChannelRepositoryInterface $channelRepository,
        private JsonResponseFactory $jsonResponseFactory,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->getCurrentUserHandler->handle();
            $formData = JsonBodyParser::parse($request);
            $formAction = isset($formData['form_action']) && is_string($formData['form_action'])
                ? $formData['form_action']
                : '';

            $result = $this->dispatch($user->getId(), $formAction, $formData);

            return $this->jsonResponseFactory->success([
                'message' => $result['message'],
                'formAction' => $formAction,
                'knowledgeBaseId' => $result['knowledgeBaseId'],
                'knowledgeBaseIds' => $result['knowledgeBaseIds'],
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
     * @return array{message: string, knowledgeBaseId: string|null, knowledgeBaseIds: list<string>|null}
     */
    private function dispatch(string $userId, string $formAction, array $formData): array
    {
        return match ($formAction) {
            'create_base' => [
                'message' => 'База знаний создана.',
                'knowledgeBaseId' => $this->createKnowledgeBaseHandler->handle(
                    userId: $userId,
                    title: $this->stringValue($formData, 'title'),
                    summary: $this->stringValue($formData, 'summary'),
                )->getId(),
                'knowledgeBaseIds' => null,
            ],
            'update_base' => [
                'message' => 'База знаний сохранена.',
                'knowledgeBaseId' => $this->updateKnowledgeBaseHandler->handle(
                    userId: $userId,
                    knowledgeBaseId: $this->requiredString($formData, 'knowledge_base_id'),
                    title: $this->stringValue($formData, 'title'),
                    summary: $this->stringValue($formData, 'summary'),
                    isActive: $this->booleanValue($formData, 'is_active'),
                )->getId(),
                'knowledgeBaseIds' => null,
            ],
            'delete_base' => $this->deleteBase($userId, $formData),
            'assign_telegram' => $this->assignTelegram($userId, $formData),
            'add_telegram_kb' => $this->addTelegramKnowledgeBase($userId, $formData),
            'remove_telegram_kb' => $this->removeTelegramKnowledgeBase($userId, $formData),
            'create_article' => [
                'message' => 'Статья добавлена.',
                'knowledgeBaseId' => $this->createArticle($userId, $formData),
                'knowledgeBaseIds' => null,
            ],
            'update_article' => [
                'message' => 'Статья сохранена.',
                'knowledgeBaseId' => $this->updateArticle($userId, $formData),
                'knowledgeBaseIds' => null,
            ],
            'delete_article' => [
                'message' => 'Статья удалена.',
                'knowledgeBaseId' => $this->deleteArticle($userId, $formData),
                'knowledgeBaseIds' => null,
            ],
            default => throw new InvalidArgumentException('Неизвестное действие.'),
        };
    }

    /**
     * @param array<string, mixed> $formData
     * @return array{message: string, knowledgeBaseId: string|null, knowledgeBaseIds: list<string>|null}
     */
    private function deleteBase(string $userId, array $formData): array
    {
        $this->deleteKnowledgeBaseHandler->handle(
            $userId,
            $this->requiredString($formData, 'knowledge_base_id'),
        );

        return [
            'message' => 'База знаний удалена.',
            'knowledgeBaseId' => null,
            'knowledgeBaseIds' => null,
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @return array{message: string, knowledgeBaseId: string|null, knowledgeBaseIds: list<string>}
     */
    private function assignTelegram(string $userId, array $formData): array
    {
        $assignedIds = $this->assignKnowledgeBaseToTelegramHandler->handle(
            $userId,
            $this->extractKnowledgeBaseIds($formData),
        );

        return [
            'message' => $assignedIds === []
                ? 'Базы знаний отвязаны от Telegram.'
                : 'Базы знаний для Telegram сохранены.',
            'knowledgeBaseId' => $assignedIds[0] ?? null,
            'knowledgeBaseIds' => $assignedIds,
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @return array{message: string, knowledgeBaseId: string|null, knowledgeBaseIds: list<string>}
     */
    private function addTelegramKnowledgeBase(string $userId, array $formData): array
    {
        $knowledgeBaseId = $this->requiredString($formData, 'knowledge_base_id');
        $channel = $this->requireTelegramChannel($userId);
        $ids = $channel->getKnowledgeBaseIds();

        if (!in_array($knowledgeBaseId, $ids, true)) {
            $ids[] = $knowledgeBaseId;
        }

        $assignedIds = $this->assignKnowledgeBaseToTelegramHandler->handle($userId, $ids);

        return [
            'message' => 'База знаний добавлена к Telegram.',
            'knowledgeBaseId' => $knowledgeBaseId,
            'knowledgeBaseIds' => $assignedIds,
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @return array{message: string, knowledgeBaseId: string|null, knowledgeBaseIds: list<string>}
     */
    private function removeTelegramKnowledgeBase(string $userId, array $formData): array
    {
        $knowledgeBaseId = $this->requiredString($formData, 'knowledge_base_id');
        $channel = $this->requireTelegramChannel($userId);
        $ids = [];

        foreach ($channel->getKnowledgeBaseIds() as $assignedKnowledgeBaseId) {
            if ($assignedKnowledgeBaseId !== $knowledgeBaseId) {
                $ids[] = $assignedKnowledgeBaseId;
            }
        }

        $assignedIds = $this->assignKnowledgeBaseToTelegramHandler->handle($userId, $ids);

        return [
            'message' => 'База знаний убрана из Telegram.',
            'knowledgeBaseId' => $knowledgeBaseId,
            'knowledgeBaseIds' => $assignedIds,
        ];
    }

    private function requireTelegramChannel(string $userId): \App\Domain\Channel\Channel
    {
        $channel = $this->channelRepository->findConnectedByUserAndProvider(
            $userId,
            ChannelProvider::Telegram,
        );

        if ($channel === null) {
            throw new InvalidArgumentException('Сначала подключите Telegram-бота.');
        }

        return $channel;
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

    /**
     * @param array<string, mixed> $formData
     * @return list<string>
     */
    private function extractKnowledgeBaseIds(array $formData): array
    {
        $rawIds = $formData['knowledge_base_ids'] ?? null;
        $ids = [];

        if (is_array($rawIds)) {
            foreach ($rawIds as $rawId) {
                if (is_string($rawId) && $rawId !== '') {
                    $ids[] = $rawId;
                }
            }
        }

        $singleId = $this->stringValue($formData, 'knowledge_base_id');

        if ($singleId !== '' && !in_array($singleId, $ids, true)) {
            $ids[] = $singleId;
        }

        return $ids;
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
