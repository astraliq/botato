<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\KnowledgeBase\KnowledgeArticle;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Shared\ArrayHelper;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class KnowledgeArticleRepository implements KnowledgeArticleRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function findById(string $id): ?KnowledgeArticle
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM knowledge_articles WHERE id = :id AND deleted = FALSE',
            )
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? KnowledgeArticleFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function listByKnowledgeBaseId(string $knowledgeBaseId): array
    {
        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM knowledge_articles
                 WHERE knowledge_base_id = :knowledge_base_id AND deleted = FALSE
                 ORDER BY sort_order ASC, created_at ASC',
            )
            ->bindValue(':knowledge_base_id', $knowledgeBaseId)
            ->queryAll();

        return $this->mapRows($rows);
    }

    public function listActiveForPrompt(string $knowledgeBaseId): array
    {
        return $this->listActiveForPromptByKnowledgeBaseIds([$knowledgeBaseId]);
    }

    public function listActiveForPromptByKnowledgeBaseIds(array $knowledgeBaseIds): array
    {
        $normalizedIds = [];

        foreach ($knowledgeBaseIds as $knowledgeBaseId) {
            if ($knowledgeBaseId !== '' && !in_array($knowledgeBaseId, $normalizedIds, true)) {
                $normalizedIds[] = $knowledgeBaseId;
            }
        }

        if ($normalizedIds === []) {
            return [];
        }

        $placeholders = [];
        $bindValues = [];

        foreach ($normalizedIds as $index => $knowledgeBaseId) {
            $placeholder = ':knowledge_base_id_' . $index;
            $placeholders[] = $placeholder;
            $bindValues[$placeholder] = $knowledgeBaseId;
        }

        $rows = $this->connection
            ->createCommand(
                'SELECT * FROM knowledge_articles
                 WHERE knowledge_base_id IN (' . implode(', ', $placeholders) . ')
                   AND deleted = FALSE
                   AND is_active = TRUE
                 ORDER BY knowledge_base_id ASC, sort_order ASC, created_at ASC',
            )
            ->bindValues($bindValues)
            ->queryAll();

        return $this->mapRows($rows);
    }

    public function save(KnowledgeArticle $article): void
    {
        $existing = $this->connection
            ->createCommand('SELECT 1 FROM knowledge_articles WHERE id = :id')
            ->bindValue(':id', $article->getId())
            ->queryScalar();

        if ($existing === false || $existing === null) {
            $this->connection
                ->createCommand(
                    'INSERT INTO knowledge_articles (
                        id, knowledge_base_id, title, content, sort_order,
                        is_active, deleted, created_at, updated_at
                     ) VALUES (
                        :id, :knowledge_base_id, :title, :content, :sort_order,
                        :is_active, :deleted, :created_at, :updated_at
                     )',
                )
                ->bindValues([
                    ':id' => $article->getId(),
                    ':knowledge_base_id' => $article->getKnowledgeBaseId(),
                    ':title' => $article->getTitle(),
                    ':content' => $article->getContent(),
                    ':sort_order' => $article->getSortOrder(),
                    ':is_active' => $article->isActive(),
                    ':deleted' => $article->isDeleted(),
                    ':created_at' => $article->getCreatedAt()->format('Y-m-d H:i:sP'),
                    ':updated_at' => $article->getUpdatedAt()->format('Y-m-d H:i:sP'),
                ])
                ->execute();

            return;
        }

        $this->connection
            ->createCommand(
                'UPDATE knowledge_articles SET
                    title = :title,
                    content = :content,
                    sort_order = :sort_order,
                    is_active = :is_active,
                    deleted = :deleted,
                    updated_at = :updated_at
                 WHERE id = :id',
            )
            ->bindValues([
                ':id' => $article->getId(),
                ':title' => $article->getTitle(),
                ':content' => $article->getContent(),
                ':sort_order' => $article->getSortOrder(),
                ':is_active' => $article->isActive(),
                ':deleted' => $article->isDeleted(),
                ':updated_at' => $article->getUpdatedAt()->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }

    /**
     * @param array<array-key, array<array-key, mixed>> $rows
     * @return list<KnowledgeArticle>
     */
    private function mapRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = KnowledgeArticleFactory::fromRow(ArrayHelper::toStringKeyArray($row));
        }

        return $result;
    }
}
