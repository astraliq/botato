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
        private bool $deleted = false,
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

    public function isDeleted(): bool
    {
        return $this->deleted;
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

    public function markDeleted(DateTimeImmutable $updatedAt): void
    {
        $this->deleted = true;
        $this->updatedAt = $updatedAt;
    }
}
