<?php

declare(strict_types=1);

use App\Environment;
use App\Infrastructure\Auth\RedisSessionHandler;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\Session;
use Yiisoft\Session\SessionInterface;

/** @var array $params */

$sessionOptions = $params['yiisoft/session']['session']['options'] ?? ['cookie_secure' => 0];
$sessionHandler = Environment::isTest()
    ? null
    : Reference::to(RedisSessionHandler::class);

return [
    SessionInterface::class => [
        'class' => Session::class,
        '__construct()' => [
            $sessionOptions,
            $sessionHandler,
        ],
        'reset' => function (): void {
            $this->sessionId = null;
            $this->close();
        },
    ],

    RedisSessionHandler::class => [
        'class' => RedisSessionHandler::class,
        '__construct()' => [
            'redisHost' => $params['redis']['host'],
            'redisPort' => $params['redis']['port'],
            'lifetimeSeconds' => $sessionOptions['gc_maxlifetime'] ?? 604_800,
        ],
    ],
];
