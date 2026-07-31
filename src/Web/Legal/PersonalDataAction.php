<?php

declare(strict_types=1);

namespace App\Web\Legal;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class PersonalDataAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly string $mailerFrom,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->render(__DIR__ . '/personal-data', [
            'mailerFrom' => $this->mailerFrom,
            'documentDate' => '31.07.2026',
        ]);
    }
}
