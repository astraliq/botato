<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Shared\ArrayHelper;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Yiisoft\Db\Connection\ConnectionInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    public function findById(string $id): ?User
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM users WHERE id = :id AND deleted = FALSE')
            ->bindValue(':id', $id)
            ->queryOne();

        return is_array($row) ? UserFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection
            ->createCommand('SELECT * FROM users WHERE email = :email AND deleted = FALSE')
            ->bindValue(':email', $email)
            ->queryOne();

        return is_array($row) ? UserFactory::fromRow(ArrayHelper::toStringKeyArray($row)) : null;
    }

    public function save(User $user): void
    {
        $this->connection
            ->createCommand(
                'UPDATE users SET
                    surname = :surname,
                    name = :name,
                    patronymic = :patronymic,
                    company_name = :company_name,
                    description = :description,
                    phone = :phone,
                    gender = :gender,
                    email_verified_at = :email_verified_at,
                    onboarding_completed_at = :onboarding_completed_at,
                    deleted = :deleted,
                    updated_at = :updated_at
                 WHERE id = :id',
            )
            ->bindValues([
                ':id' => $user->getId(),
                ':surname' => $user->getSurname(),
                ':name' => $user->getName(),
                ':patronymic' => $user->getPatronymic(),
                ':company_name' => $user->getCompanyName(),
                ':description' => $user->getDescription(),
                ':phone' => $user->getPhone(),
                ':gender' => $user->getGender()?->value,
                ':email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:sP'),
                ':onboarding_completed_at' => $user->getOnboardingCompletedAt()?->format('Y-m-d H:i:sP'),
                ':deleted' => $user->isDeleted(),
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
                    id, email, surname, name, patronymic, company_name, description, phone, gender,
                    email_verified_at, deleted, created_at, updated_at
                 ) VALUES (
                    :id, :email, :surname, :name, :patronymic, :company_name, :description, :phone, :gender,
                    :email_verified_at, FALSE, :created_at, :updated_at
                 )',
            )
            ->bindValues([
                ':id' => $id,
                ':email' => $email,
                ':surname' => '',
                ':name' => '',
                ':patronymic' => '',
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
