<?php

declare(strict_types=1);

use App\Web\Middleware\ConditionalCsrfMiddleware;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\Definitions\Reference;

return [
    ConditionalCsrfMiddleware::class => [
        '__construct()' => [
            'csrfTokenMiddleware' => Reference::to(CsrfTokenMiddleware::class),
        ],
    ],
];
