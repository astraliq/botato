<?php

declare(strict_types=1);

namespace App\Application\Auth\VerifyEmailCode;

use App\Application\Auth\AuthSessionService;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class VerifyEmailCodeHandler
{
    public function __construct(
        private readonly EmailVerificationCodeRepositoryInterface $verificationCodeRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthSessionService $authSessionService,
    ) {}

    public function handle(string $email, string $code): User
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $normalizedCode = trim($code);
        $now = new DateTimeImmutable();

        if (!preg_match('/^\d{6}$/', $normalizedCode)) {
            throw new InvalidArgumentException('Код должен состоять из 6 цифр.');
        }

        $verificationCode = $this->verificationCodeRepository->findLatestValid($normalizedEmail, $now);

        if ($verificationCode === null) {
            throw new InvalidArgumentException('Код не найден или истёк. Запросите новый.');
        }

        if (!password_verify($normalizedCode, $verificationCode->getCodeHash())) {
            throw new InvalidArgumentException('Неверный код подтверждения.');
        }

        $this->verificationCodeRepository->markUsed($verificationCode->getId(), $now);

        $user = $this->userRepository->findByEmail($normalizedEmail);

        if ($user === null) {
            $user = $this->userRepository->create($normalizedEmail);
        }

        if ($user->getEmailVerifiedAt() === null) {
            $user->markEmailVerified($now, $now);
            $this->userRepository->save($user);
        }

        $this->authSessionService->login($user->getId());

        return $user;
    }

    private function normalizeEmail(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '' || filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Укажите корректный email.');
        }

        return $normalizedEmail;
    }
}
