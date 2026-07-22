<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Auth\EmailVerificationCode;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

final class EmailVerificationCodeRepository implements EmailVerificationCodeRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function save(string $email, string $codeHash, DateTimeImmutable $expiresAt): void
    {
        $now = new DateTimeImmutable();

        $this->connection
            ->createCommand(
                'INSERT INTO email_verification_codes (email, code_hash, expires_at, created_at)
                 VALUES (:email, :code_hash, :expires_at, :created_at)',
            )
            ->bindValues([
                ':email' => $email,
                ':code_hash' => $codeHash,
                ':expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
                ':created_at' => $now->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }

    public function findLatestValid(string $email, DateTimeImmutable $now): ?EmailVerificationCode
    {
        $row = $this->connection
            ->createCommand(
                'SELECT * FROM email_verification_codes
                 WHERE email = :email
                   AND used_at IS NULL
                   AND expires_at > :now
                 ORDER BY id DESC
                 LIMIT 1',
            )
            ->bindValues([
                ':email' => $email,
                ':now' => $now->format('Y-m-d H:i:sP'),
            ])
            ->queryOne();

        return is_array($row) ? EmailVerificationCodeFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function markUsed(int $id, DateTimeImmutable $usedAt): void
    {
        $this->connection
            ->createCommand(
                'UPDATE email_verification_codes SET used_at = :used_at WHERE id = :id',
            )
            ->bindValues([
                ':id' => $id,
                ':used_at' => $usedAt->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }

    public function hasRecentRequest(string $email, DateTimeImmutable $since): bool
    {
        $count = $this->connection
            ->createCommand(
                'SELECT COUNT(*) FROM email_verification_codes
                 WHERE email = :email AND created_at >= :since',
            )
            ->bindValues([
                ':email' => $email,
                ':since' => $since->format('Y-m-d H:i:sP'),
            ])
            ->queryScalar();

        return (int) $count > 0;
    }
}
