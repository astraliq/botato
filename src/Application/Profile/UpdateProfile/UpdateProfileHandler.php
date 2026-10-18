<?php

declare(strict_types=1);

namespace App\Application\Profile\UpdateProfile;

use App\Domain\User\User;
use App\Domain\User\UserConstraints;
use App\Domain\User\UserGender;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UpdateProfileHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(string $userId, UpdateProfileCommand $command): User
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new InvalidArgumentException('Пользователь не найден.');
        }

        $surname = trim($command->surname);
        $name = trim($command->name);
        $patronymic = trim($command->patronymic);
        $companyName = trim($command->companyName);
        $description = trim($command->description);
        $phone = trim($command->phone);
        $gender = UserGender::tryFrom(trim($command->gender));

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

        if (mb_strlen($description) > UserConstraints::DESCRIPTION_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Описание не должно превышать ' . UserConstraints::DESCRIPTION_MAX_LENGTH . ' символов.',
            );
        }

        if ($phone !== '' && !preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
            throw new InvalidArgumentException('Телефон должен быть в формате +7 (XXX) XXX-XX-XX.');
        }

        if ($command->isConsentPersonalData === false) {
            throw new InvalidArgumentException(
                'Чтобы продолжить, примите оферту и согласие на обработку персональных данных.',
            );
        }

        $updatedAt = new DateTimeImmutable();

        $user->updateProfile(
            $surname,
            $name,
            $patronymic,
            $companyName,
            $description,
            $phone,
            $gender,
            $updatedAt,
        );

        if ($command->isConsentPersonalData === true) {
            $user->givePersonalDataConsent($updatedAt);
        }

        $this->userRepository->save($user);

        return $user;
    }
}
