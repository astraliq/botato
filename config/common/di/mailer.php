<?php

declare(strict_types=1);

use App\Infrastructure\Mail\VerificationCodeMailer;
use App\Web\Landing\Action as LandingAction;
use App\Web\Legal\PersonalDataAction;

/** @var array $params */

return [
    VerificationCodeMailer::class => [
        '__construct()' => [
            'mailerDsn' => $params['mailer']['dsn'],
            'fromEmail' => $params['mailer']['from'],
        ],
    ],

    LandingAction::class => [
        '__construct()' => [
            'mailerFrom' => $params['mailer']['from'],
        ],
    ],

    PersonalDataAction::class => [
        '__construct()' => [
            'mailerFrom' => $params['mailer']['from'],
        ],
    ],
];
