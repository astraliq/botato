<?php

declare(strict_types=1);

use App\Application\Auth\AuthSessionInterface;
use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Auth\Logout\LogoutHandler;
use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Application\Auth\VerificationCodeMailerInterface;
use App\Application\Auth\VerifyEmailCode\VerifyEmailCodeHandler;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Auth\SessionAuthSession;
use App\Infrastructure\Mail\VerificationCodeMailer;
use App\Infrastructure\Persistence\PgsqlEmailVerificationCodeRepository;
use App\Infrastructure\Persistence\PgsqlUserRepository;
use App\Web\Shared\JsonResponseFactory;

/** @var array $params */

return [
    UserRepositoryInterface::class => PgsqlUserRepository::class,
    EmailVerificationCodeRepositoryInterface::class => PgsqlEmailVerificationCodeRepository::class,
    AuthSessionInterface::class => SessionAuthSession::class,
    VerificationCodeMailerInterface::class => VerificationCodeMailer::class,

    GetCurrentUserHandler::class => GetCurrentUserHandler::class,
    LogoutHandler::class => LogoutHandler::class,
    RequestVerificationCodeHandler::class => RequestVerificationCodeHandler::class,
    VerifyEmailCodeHandler::class => VerifyEmailCodeHandler::class,
    UpdateProfileHandler::class => UpdateProfileHandler::class,
    JsonResponseFactory::class => JsonResponseFactory::class,
];
