<?php

declare(strict_types=1);

use App\Application\Auth\AuthSessionInterface;
use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Auth\Logout\LogoutHandler;
use App\Application\Auth\RequestVerificationCode\RequestVerificationCodeHandler;
use App\Application\Auth\VerificationCodeMailerInterface;
use App\Application\Auth\VerifyEmailCode\VerifyEmailCodeHandler;
use App\Application\Channel\ConnectTelegram\ConnectTelegramChannelHandler;
use App\Application\Channel\Telegram\HandleUpdate\HandleTelegramUpdateHandler;
use App\Application\Channel\Telegram\TelegramBotApiInterface;
use App\Application\Profile\UpdateProfile\UpdateProfileHandler;
use App\Domain\Ai\AiProviderRepositoryInterface;
use App\Domain\Auth\EmailVerificationCodeRepositoryInterface;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Plan\PlanRepositoryInterface;
use App\Domain\Security\SecretEncryptorInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\Usage\UsageRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Auth\SessionAuthSession;
use App\Infrastructure\Mail\VerificationCodeMailer;
use App\Infrastructure\Persistence\AiProviderRepository;
use App\Infrastructure\Persistence\ChannelRepository;
use App\Infrastructure\Persistence\EmailVerificationCodeRepository;
use App\Infrastructure\Persistence\KnowledgeArticleRepository;
use App\Infrastructure\Persistence\KnowledgeBaseRepository;
use App\Infrastructure\Persistence\PlanRepository;
use App\Infrastructure\Persistence\SubscriptionRepository;
use App\Infrastructure\Persistence\UsageRepository;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Security\SodiumSecretEncryptor;
use App\Infrastructure\Telegram\TelegramBotApiHttpClient;
use App\Web\Shared\JsonResponseFactory;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    UserRepositoryInterface::class => UserRepository::class,
    EmailVerificationCodeRepositoryInterface::class => EmailVerificationCodeRepository::class,
    ChannelRepositoryInterface::class => ChannelRepository::class,
    SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
    UsageRepositoryInterface::class => UsageRepository::class,
    PlanRepositoryInterface::class => PlanRepository::class,
    AiProviderRepositoryInterface::class => AiProviderRepository::class,
    KnowledgeBaseRepositoryInterface::class => KnowledgeBaseRepository::class,
    KnowledgeArticleRepositoryInterface::class => KnowledgeArticleRepository::class,
    AuthSessionInterface::class => SessionAuthSession::class,
    VerificationCodeMailerInterface::class => VerificationCodeMailer::class,
    TelegramBotApiHttpClient::class => [
        '__construct()' => [
            'proxyUrl' => $params['telegram_proxy'] ?? null,
        ],
    ],
    TelegramBotApiInterface::class => Reference::to(TelegramBotApiHttpClient::class),
    SodiumSecretEncryptor::class => [
        '__construct()' => [
            'appSecret' => $params['app_secret'],
        ],
    ],
    SecretEncryptorInterface::class => Reference::to(SodiumSecretEncryptor::class),

    GetCurrentUserHandler::class => GetCurrentUserHandler::class,
    LogoutHandler::class => LogoutHandler::class,
    RequestVerificationCodeHandler::class => RequestVerificationCodeHandler::class,
    VerifyEmailCodeHandler::class => VerifyEmailCodeHandler::class,
    UpdateProfileHandler::class => UpdateProfileHandler::class,
    ConnectTelegramChannelHandler::class => [
        '__construct()' => [
            'webhookBaseUrl' => ($params['telegram_webhook_base_url'] ?? '') !== ''
                ? $params['telegram_webhook_base_url']
                : ($params['public_base_url'] ?? ''),
        ],
    ],
    HandleTelegramUpdateHandler::class => HandleTelegramUpdateHandler::class,
    JsonResponseFactory::class => JsonResponseFactory::class,
];
