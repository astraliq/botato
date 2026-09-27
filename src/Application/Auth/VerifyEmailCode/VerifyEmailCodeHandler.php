<?php

declare(strict_types=1);

namespace App\Application\Auth\VerifyEmailCode;

use App\Application\Auth\AuthSessionInterface;
use App\Application\Auth\EmailNormalizer;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class VerifyEmailCodeHandler
{
    public function __construct(
        private EmailVerificationCodeRepositoryInterface $verificationCodeRepository,
        private UserRepositoryInterface $userRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private AuthSessionInterface $authSession,
    ) {}

    public function handle(string $email, string $code): User
    {
        $normalizedEmail = EmailNormalizer::normalize($email);
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
        $isNewUser = $user === null;

        if ($user === null) {
            $user = $this->userRepository->create($normalizedEmail);
        }

        if ($user->getEmailVerifiedAt() === null) {
            $user->markEmailVerified($now, $now);
            $this->userRepository->save($user);
        }

        if ($isNewUser) {
            $this->subscriptionRepository->ensureFreeSubscription($user->getId());
        }

        $this->authSession->login($user->getId());

        return $user;
    }
}
