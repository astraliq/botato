<?php

declare(strict_types=1);

use App\Infrastructure\Mail\VerificationCodeMailer;

/** @var array $params */

return [
    VerificationCodeMailer::class => [
        '__construct()' => [
            'mailerDsn' => $params['mailer']['dsn'],
            'fromEmail' => $params['mailer']['from'],
        ],
    ],
];
