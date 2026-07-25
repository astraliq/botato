<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Usage\DialogBillingRules;
use App\Domain\Usage\PeerMessageQuotaResult;
use App\Domain\Usage\UsageRepositoryInterface;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;

final class UsageRepository implements UsageRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function registerPeerMessage(
        string $userId,
        string $peerKey,
        int $maxDialogsPerMonth,
        int $maxMessagesPerDialog,
        DateTimeImmutable $now,
    ): PeerMessageQuotaResult {
        $transaction = $this->connection->beginTransaction();

        try {
            $periodMonthDate = $now->modify('first day of this month')->setTime(0, 0)->format('Y-m-d');
            $nowFormatted = $now->format('Y-m-d H:i:sP');
            $maxMessagesPerDialog = max(1, $maxMessagesPerDialog);

            $dialogsUsed = $this->ensureUsageMonthLocked($userId, $periodMonthDate, $nowFormatted);
            $window = $this->ensurePeerWindowLocked($userId, $peerKey, $nowFormatted);

            $windowStartedAt = new DateTimeImmutable((string) $window['window_started_at']);
            $messagesCount = (int) $window['messages_count'];

            $startsNewDialog = $messagesCount === 0
                || !DialogBillingRules::isWithinDialogWindow($windowStartedAt, $now);

            if ($startsNewDialog) {
                $messagesCount = 0;
                $windowStartedAt = $now;
            }

            $nextMessagesCount = $messagesCount + 1;

            if ($nextMessagesCount > $maxMessagesPerDialog) {
                $transaction->rollBack();

                return new PeerMessageQuotaResult(
                    allowed: false,
                    startedNewDialog: false,
                    messagesInWindow: $messagesCount,
                    dialogsUsedThisMonth: $dialogsUsed,
                    dialogsLimit: $maxDialogsPerMonth,
                    windowStartedAt: $windowStartedAt,
                    denyReason: PeerMessageQuotaResult::DENY_MESSAGES_LIMIT,
                );
            }

            if ($startsNewDialog) {
                $updatedRows = $this->connection
                    ->createCommand(
                        'UPDATE usage_months
                         SET dialogs_count = dialogs_count + 1,
                             updated_at = :updated_at
                         WHERE user_id = :user_id
                           AND period_month = :period_month
                           AND dialogs_count < :max_dialogs',
                    )
                    ->bindValues([
                        ':updated_at' => $nowFormatted,
                        ':user_id' => $userId,
                        ':period_month' => $periodMonthDate,
                        ':max_dialogs' => $maxDialogsPerMonth,
                    ])
                    ->execute();

                if ($updatedRows === 0) {
                    $transaction->rollBack();

                    return new PeerMessageQuotaResult(
                        allowed: false,
                        startedNewDialog: false,
                        messagesInWindow: $messagesCount,
                        dialogsUsedThisMonth: $dialogsUsed,
                        dialogsLimit: $maxDialogsPerMonth,
                        windowStartedAt: $windowStartedAt,
                        denyReason: PeerMessageQuotaResult::DENY_DIALOGS_LIMIT,
                    );
                }

                $dialogsUsed++;
            }

            $this->connection
                ->createCommand(
                    'UPDATE usage_peer_windows
                     SET window_started_at = :window_started_at,
                         messages_count = :messages_count,
                         updated_at = :updated_at
                     WHERE user_id = :user_id AND peer_key = :peer_key',
                )
                ->bindValues([
                    ':window_started_at' => $windowStartedAt->format('Y-m-d H:i:sP'),
                    ':messages_count' => $nextMessagesCount,
                    ':updated_at' => $nowFormatted,
                    ':user_id' => $userId,
                    ':peer_key' => $peerKey,
                ])
                ->execute();

            $transaction->commit();

            return new PeerMessageQuotaResult(
                allowed: true,
                startedNewDialog: $startsNewDialog,
                messagesInWindow: $nextMessagesCount,
                dialogsUsedThisMonth: $dialogsUsed,
                dialogsLimit: $maxDialogsPerMonth,
                windowStartedAt: $windowStartedAt,
            );
        } catch (\Throwable $exception) {
            if ($transaction->isActive()) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array{window_started_at: mixed, messages_count: mixed}
     */
    private function ensurePeerWindowLocked(string $userId, string $peerKey, string $nowFormatted): array
    {
        $row = $this->connection
            ->createCommand(
                'SELECT window_started_at, messages_count
                 FROM usage_peer_windows
                 WHERE user_id = :user_id AND peer_key = :peer_key
                 FOR UPDATE',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':peer_key' => $peerKey,
            ])
            ->queryOne();

        if (is_array($row)) {
            return $this->peerWindowFromRow($row);
        }

        $this->connection
            ->createCommand(
                'INSERT INTO usage_peer_windows (
                    id, user_id, peer_key, window_started_at, messages_count, updated_at
                 ) VALUES (
                    :id, :user_id, :peer_key, :window_started_at, 0, :updated_at
                 )
                 ON CONFLICT (user_id, peer_key) DO NOTHING',
            )
            ->bindValues([
                ':id' => Uuid::uuid4()->toString(),
                ':user_id' => $userId,
                ':peer_key' => $peerKey,
                ':window_started_at' => $nowFormatted,
                ':updated_at' => $nowFormatted,
            ])
            ->execute();

        $row = $this->connection
            ->createCommand(
                'SELECT window_started_at, messages_count
                 FROM usage_peer_windows
                 WHERE user_id = :user_id AND peer_key = :peer_key
                 FOR UPDATE',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':peer_key' => $peerKey,
            ])
            ->queryOne();

        if (!is_array($row)) {
            throw new RuntimeException('Не удалось создать окно учёта сообщений.');
        }

        return $this->peerWindowFromRow($row);
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array{window_started_at: mixed, messages_count: mixed}
     */
    private function peerWindowFromRow(array $row): array
    {
        return [
            'window_started_at' => $row['window_started_at'] ?? null,
            'messages_count' => $row['messages_count'] ?? 0,
        ];
    }

    private function ensureUsageMonthLocked(string $userId, string $periodMonthDate, string $nowFormatted): int
    {
        $row = $this->connection
            ->createCommand(
                'SELECT dialogs_count FROM usage_months
                 WHERE user_id = :user_id AND period_month = :period_month
                 FOR UPDATE',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':period_month' => $periodMonthDate,
            ])
            ->queryOne();

        if (is_array($row)) {
            return (int) $row['dialogs_count'];
        }

        $this->connection
            ->createCommand(
                'INSERT INTO usage_months (id, user_id, period_month, dialogs_count, updated_at)
                 VALUES (:id, :user_id, :period_month, 0, :updated_at)
                 ON CONFLICT (user_id, period_month) DO NOTHING',
            )
            ->bindValues([
                ':id' => Uuid::uuid4()->toString(),
                ':user_id' => $userId,
                ':period_month' => $periodMonthDate,
                ':updated_at' => $nowFormatted,
            ])
            ->execute();

        $row = $this->connection
            ->createCommand(
                'SELECT dialogs_count FROM usage_months
                 WHERE user_id = :user_id AND period_month = :period_month
                 FOR UPDATE',
            )
            ->bindValues([
                ':user_id' => $userId,
                ':period_month' => $periodMonthDate,
            ])
            ->queryOne();

        if (!is_array($row)) {
            throw new RuntimeException('Не удалось создать запись usage_months.');
        }

        return (int) $row['dialogs_count'];
    }
}
