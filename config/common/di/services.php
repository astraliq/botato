<?php

declare(strict_types=1);

use App\Application\Auth\AuthSessionService;
use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Application\Auth\VerifyEmailCode\VerifyEmailCodeHandler;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\PgsqlEmailVerificationCodeRepository;
use App\Infrastructure\Persistence\PgsqlUserRepository;
use App\Web\Shared\JsonResponseFactory;

/** @var array $params */

return [
    UserRepositoryInterface::class => PgsqlUserRepository::class,
    EmailVerificationCodeRepositoryInterface::class => PgsqlEmailVerificationCodeRepository::class,

    AuthSessionService::class => AuthSessionService::class,
    RequestVerificationCodeHandler::class => RequestVerificationCodeHandler::class,
    VerifyEmailCodeHandler::class => VerifyEmailCodeHandler::class,
    UpdateProfileHandler::class => UpdateProfileHandler::class,
    JsonResponseFactory::class => JsonResponseFactory::class,
];
