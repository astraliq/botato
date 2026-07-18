<?php

declare(strict_types=1);

namespace App\Application\Auth\RequestVerificationCode;

use App\Application\Auth\EmailNormalizer;
use App\Application\Auth\VerificationCodeMailerInterface;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class RequestVerificationCodeHandler
{
    private const CODE_TTL_SECONDS = 600;
    private const RATE_LIMIT_SECONDS = 60;

    public function __construct(
        private readonly EmailVerificationCodeRepositoryInterface $verificationCodeRepository,
        private readonly VerificationCodeMailerInterface $verificationCodeMailer,
    ) {}

    public function handle(string $email): void
    {
        $normalizedEmail = EmailNormalizer::normalize($email);
        $now = new DateTimeImmutable();

        if ($this->verificationCodeRepository->hasRecentRequest(
            $normalizedEmail,
            $now->modify('-' . self::RATE_LIMIT_SECONDS . ' seconds'),
        )) {
            throw new InvalidArgumentException('Повторный запрос кода возможен через минуту.');
        }

        $code = $this->generateCode();
        $this->verificationCodeRepository->save(
            $normalizedEmail,
            password_hash($code, PASSWORD_DEFAULT),
            $now->modify('+' . self::CODE_TTL_SECONDS . ' seconds'),
        );

        $this->verificationCodeMailer->send($normalizedEmail, $code);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
