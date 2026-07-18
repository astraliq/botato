<?php

declare(strict_types=1);

namespace App\Application\Profile\UpdateProfile;

use App\Domain\User\User;
use App\Domain\User\UserGender;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class UpdateProfileHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(
        string $userId,
        string $surname,
        string $name,
        string $patronymic,
        string $companyName,
        string $description,
        string $phone,
        ?UserGender $gender,
    ): User {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException('Пользователь не найден.');
        }

        $surname = trim($surname);
        $name = trim($name);
        $patronymic = trim($patronymic);
        $companyName = trim($companyName);
        $description = trim($description);
        $phone = trim($phone);

        if ($surname === '') {
            throw new InvalidArgumentException('Укажите фамилию.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Укажите имя.');
        }

        if ($companyName === '') {
            throw new InvalidArgumentException('Укажите название компании.');
        }

        if ($gender === null) {
            throw new InvalidArgumentException('Укажите пол.');
        }

        if (mb_strlen($surname) > 255) {
            throw new InvalidArgumentException('Фамилия не должна превышать 255 символов.');
        }

        if (mb_strlen($name) > 255) {
            throw new InvalidArgumentException('Имя не должно превышать 255 символов.');
        }

        if (mb_strlen($patronymic) > 255) {
            throw new InvalidArgumentException('Отчество не должно превышать 255 символов.');
        }

        if (mb_strlen($companyName) > 255) {
            throw new InvalidArgumentException('Название компании не должно превышать 255 символов.');
        }

        if (mb_strlen($description) > 2000) {
            throw new InvalidArgumentException('Описание не должно превышать 2000 символов.');
        }

        if ($phone !== '' && !preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
            throw new InvalidArgumentException('Телефон должен быть в формате +7 (XXX) XXX-XX-XX.');
        }

        $user->updateProfile(
            $surname,
            $name,
            $patronymic,
            $companyName,
            $description,
            $phone,
            $gender,
            new DateTimeImmutable(),
        );
        $this->userRepository->save($user);

        return $user;
    }
}
