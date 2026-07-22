<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\KnowledgeBase\KnowledgeBase;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Shared\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;

final class KnowledgeBaseRepository implements KnowledgeBaseRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function findById(string $id): ?KnowledgeBase
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM knowledge_bases WHERE id = :id AND deleted = FALSE',
            )
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? KnowledgeBaseFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function listByUserId(string $userId): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM knowledge_bases
                 WHERE user_id = :user_id AND deleted = FALSE
                 ORDER BY created_at ASC',
            )
            ->bindValue(':user_id', $userId)
            ->queryAll();

        $result = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $result[] = KnowledgeBaseFactory::fromRow(ArrayHelper::toStringKeyArray($row));
            }
        }

        return $result;
    }

    public function countByUserId(string $userId): int
    {
        $count = $this->connection
            ->createCommand(
                'SELECT COUNT(*) FROM knowledge_bases
                 WHERE user_id = :user_id AND deleted = FALSE',
            )
            ->bindValue(':user_id', $userId)
            ->queryScalar();

        return (int) $count;
    }

    public function save(KnowledgeBase $knowledgeBase): void
    {
        $existing = $this->connection
            ->createCommand('SELECT 1 FROM knowledge_bases WHERE id = :id')
            ->bindValue(':id', $knowledgeBase->getId())
            ->queryScalar();

        if ($existing === false || $existing === null) {
            $this->connection
                ->createCommand(
                    'INSERT INTO knowledge_bases (
                        id, user_id, title, summary, is_active, deleted, created_at, updated_at
                     ) VALUES (
                        :id, :user_id, :title, :summary, :is_active, :deleted, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => $knowledgeBase->getId(),
                    ':user_id' => $knowledgeBase->getUserId(),
                    ':title' => $knowledgeBase->getTitle(),
                    ':summary' => $knowledgeBase->getSummary(),
                    ':is_active' => $knowledgeBase->isActive(),
                    ':deleted' => $knowledgeBase->isDeleted(),
                    ':created_at' => $knowledgeBase->getCreatedAt()->format('Y-m-d H:i:sP'),
                    ':updated_at' => $knowledgeBase->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();

            return;
        }

        $this->connection
            ->createCommand(
                'UPDATE knowledge_bases SET
                    title = :title,
                    summary = :summary,
                    is_active = :is_active,
                    deleted = :deleted,
                    updated_at = :updated_at
                 WHERE id = :id',
            )
            ->bindValues([
                ':id' => $knowledgeBase->getId(),
                ':title' => $knowledgeBase->getTitle(),
                ':summary' => $knowledgeBase->getSummary(),
                ':is_active' => $knowledgeBase->isActive(),
                ':deleted' => $knowledgeBase->isDeleted(),
                ':updated_at' => $knowledgeBase->getUpdatedAt()->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }
}
