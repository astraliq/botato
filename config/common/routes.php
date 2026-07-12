<?php

declare(strict_types=1);

use App\Web;
use App\Web\Middleware\AuthMiddleware;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(Web\Landing\Action::class)
                ->name('home'),

            Route::post('/api/auth/request-code')
                ->action(Web\Auth\RequestCodeAction::class)
                ->name('auth/request-code'),

            Route::post('/api/auth/verify-code')
                ->action(Web\Auth\VerifyCodeAction::class)
                ->name('auth/verify-code'),

            Route::post('/api/auth/logout')
                ->action(Web\Auth\LogoutAction::class)
                ->name('auth/logout'),

            Group::create('/cabinet')
                ->middleware(AuthMiddleware::class)
                ->routes(
                    Route::get('')
                        ->action(Web\Cabinet\IndexAction::class)
                        ->name('cabinet'),
                    Route::post('')
                        ->action(Web\Cabinet\UpdateAction::class)
                        ->name('cabinet/update'),
                ),
        ),
];
