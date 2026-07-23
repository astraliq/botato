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

            Route::post('/api/cabinet/settings/telegram')
                ->middleware(AuthMiddleware::class)
                ->action(Web\Cabinet\Settings\ConnectTelegramAction::class)
                ->name('api/cabinet/settings/telegram'),

            Route::post('/api/cabinet/settings/knowledge')
                ->middleware(AuthMiddleware::class)
                ->action(Web\Cabinet\Settings\Knowledge\ManageAction::class)
                ->name('api/cabinet/settings/knowledge'),

            Route::post('/webhooks/telegram/{channelId}')
                ->action(Web\Webhook\TelegramWebhookAction::class)
                ->name('webhooks/telegram'),

            Group::create('/cabinet')
                ->middleware(AuthMiddleware::class)
                ->routes(
                    Route::get('')
                        ->action(Web\Cabinet\IndexAction::class)
                        ->name('cabinet'),
                    Route::post('')
                        ->action(Web\Cabinet\UpdateAction::class)
                        ->name('cabinet/update'),
                    Route::get('/settings')
                        ->action(Web\Cabinet\Settings\IndexAction::class)
                        ->name('cabinet/settings'),
                    Route::get('/settings/knowledge')
                        ->action(Web\Cabinet\Settings\Knowledge\IndexAction::class)
                        ->name('cabinet/settings/knowledge'),
                    Route::get('/support')
                        ->action(Web\Cabinet\Support\IndexAction::class)
                        ->name('cabinet/support'),
                    Route::post('/support')
                        ->action(Web\Cabinet\Support\CreateAction::class)
                        ->name('cabinet/support/create'),
                ),
        ),
];
