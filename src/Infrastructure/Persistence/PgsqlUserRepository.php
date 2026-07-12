<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Yiisoft\Db\Connection\ConnectionInterface;

final class PgsqlUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function findById(string $id): ?User
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM users WHERE id = :id')
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? User::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM users WHERE email = :email')
            ->bindValue(':email', $email)
            ->queryOne();

        return is_array($row) ? User::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function save(User $user): void
    {
        $this->connection
            ->createCommand(
                'UPDATE users SET
                    full_name = :full_name,
                    company_name = :company_name,
                    description = :description,
                    phone = :phone,
                    gender = :gender,
                    email_verified_at = :email_verified_at,
                    updated_at = :updated_at
                 WHERE id = :id',
            )
            ->bindValues([
                ':id' => $user->getId(),
                ':full_name' => $user->getFullName(),
                ':company_name' => $user->getCompanyName(),
                ':description' => $user->getDescription(),
                ':phone' => $user->getPhone(),
                ':gender' => $user->getGender()?->value,
                ':email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:sP'),
                ':updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:sP'),
            ])
            ->execute();
    }

    public function create(string $email): User
    {
        $now = new DateTimeImmutable();
        $id = Uuid::uuid4()->toString();

        $this->connection
            ->createCommand(
                'INSERT INTO users (
                    id, email, full_name, company_name, description, phone, gender,
                    email_verified_at, created_at, updated_at
                 ) VALUES (
                    :id, :email, :full_name, :company_name, :description, :phone, :gender,
                    :email_verified_at, :created_at, :updated_at
                 )',
            )
            ->bindValues([
                ':id' => $id,
                ':email' => $email,
                ':full_name' => '',
                ':company_name' => '',
                ':description' => '',
                ':phone' => '',
                ':gender' => null,
                ':email_verified_at' => null,
                ':created_at' => $now->format('Y-m-d H:i:sP'),
                ':updated_at' => $now->format('Y-m-d H:i:sP'),
            ])
            ->execute();

        $user = $this->findById($id);

        if ($user === null) {
            throw new \RuntimeException('Не удалось создать пользователя.');
        }

        return $user;
    }
}
