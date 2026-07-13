<?php

declare(strict_types=1);

namespace App\Domain\User;

use DateTimeImmutable;

final class User
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private string $surname,
        private string $name,
        private string $patronymic,
        private string $companyName,
        private string $description,
        private string $phone,
        private ?UserGender $gender,
        private ?DateTimeImmutable $emailVerifiedAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPatronymic(): string
    {
        return $this->patronymic;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getGender(): ?UserGender
    {
        return $this->gender;
    }

    public function getEmailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateProfile(
        string $surname,
        string $name,
        string $patronymic,
        string $companyName,
        string $description,
        string $phone,
        ?UserGender $gender,
        DateTimeImmutable $updatedAt,
    ): void {
        $this->surname = $surname;
        $this->name = $name;
        $this->patronymic = $patronymic;
        $this->companyName = $companyName;
        $this->description = $description;
        $this->phone = $phone;
        $this->gender = $gender;
        $this->updatedAt = $updatedAt;
    }

    public function markEmailVerified(DateTimeImmutable $verifiedAt, DateTimeImmutable $updatedAt): void
    {
        $this->emailVerifiedAt = $verifiedAt;
        $this->updatedAt = $updatedAt;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            email: (string) $row['email'],
            surname: (string) ($row['surname'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            patronymic: (string) ($row['patronymic'] ?? ''),
            companyName: (string) ($row['company_name'] ?? ''),
            description: (string) ($row['description'] ?? ''),
            phone: (string) ($row['phone'] ?? ''),
            gender: isset($row['gender']) && $row['gender'] !== null && $row['gender'] !== ''
                ? UserGender::tryFrom((string) $row['gender'])
                : null,
            emailVerifiedAt: isset($row['email_verified_at']) && $row['email_verified_at'] !== null
                ? new DateTimeImmutable((string) $row['email_verified_at'])
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
