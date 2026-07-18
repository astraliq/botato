<?php

declare(strict_types=1);

use App\Infrastructure\Auth\RedisSessionHandler;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\Session;
use Yiisoft\Session\SessionInterface;

/** @var array $params */

return [
    SessionInterface::class => [
        'class' => Session::class,
        '__construct()' => [
            $params['yiisoft/session']['session']['options'] ?? ['cookie_secure' => 0],
            Reference::to(RedisSessionHandler::class),
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
        ],
    ],
];
