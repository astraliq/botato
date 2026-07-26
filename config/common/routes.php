<?php

declare(strict_types=1);

use App\Web;
use App\Web\Middleware\AdminMiddleware;
use App\Web\Middleware\AuthMiddleware;
use App\Web\Middleware\OnboardingMiddleware;
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
            Route::post('/api/cabinet/onboarding/profile')
                ->middleware(AuthMiddleware::class)
                ->action(Web\Cabinet\Onboarding\SaveProfileAction::class)
                ->name('api/cabinet/onboarding/profile'),
            Route::post('/api/cabinet/onboarding/complete')
                ->middleware(AuthMiddleware::class)
                ->action(Web\Cabinet\Onboarding\CompleteAction::class)
                ->name('api/cabinet/onboarding/complete'),
            Route::post('/webhooks/telegram/{channelId}')
                ->action(Web\Webhook\TelegramWebhookAction::class)
                ->name('webhooks/telegram'),
            Group::create('/cabinet')
                ->middleware(AuthMiddleware::class)
                ->middleware(OnboardingMiddleware::class)
                ->routes(
                    Route::get('')
                        ->action(Web\Cabinet\IndexAction::class)
                        ->name('cabinet'),
                    Route::post('')
                        ->action(Web\Cabinet\UpdateAction::class)
                        ->name('cabinet/update'),
                    Route::get('/onboarding')
                        ->action(Web\Cabinet\Onboarding\IndexAction::class)
                        ->name('cabinet/onboarding'),
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
                    Group::create('/admin')
                        ->middleware(AdminMiddleware::class)
                        ->routes(
                            Route::get('/ai')
                                ->action(Web\Cabinet\Admin\Ai\IndexAction::class)
                                ->name('cabinet/admin/ai'),
                            Route::methods(['GET', 'POST'], '/ai/providers/create')
                                ->action(Web\Cabinet\Admin\Ai\ProviderFormAction::class)
                                ->name('cabinet/admin/ai/providers/create'),
                            Route::methods(['GET', 'POST'], '/ai/providers/{id}')
                                ->action(Web\Cabinet\Admin\Ai\ProviderFormAction::class)
                                ->name('cabinet/admin/ai/providers/edit'),
                            Route::post('/ai/providers/{id}/delete')
                                ->action(Web\Cabinet\Admin\Ai\DeleteProviderAction::class)
                                ->name('cabinet/admin/ai/providers/delete'),
                            Route::get('/ai/providers/{id}/models')
                                ->action(Web\Cabinet\Admin\Ai\ModelsIndexAction::class)
                                ->name('cabinet/admin/ai/models'),
                            Route::methods(['GET', 'POST'], '/ai/providers/{id}/models/create')
                                ->action(Web\Cabinet\Admin\Ai\ModelFormAction::class)
                                ->name('cabinet/admin/ai/models/create'),
                            Route::methods(['GET', 'POST'], '/ai/providers/{id}/models/{modelId}')
                                ->action(Web\Cabinet\Admin\Ai\ModelFormAction::class)
                                ->name('cabinet/admin/ai/models/edit'),
                            Route::post('/ai/providers/{id}/models/{modelId}/delete')
                                ->action(Web\Cabinet\Admin\Ai\DeleteModelAction::class)
                                ->name('cabinet/admin/ai/models/delete'),
                        ),
                ),
        ),
];
