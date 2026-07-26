<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Domain\Ai\AiProviderRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private AiProviderRepositoryInterface $aiProviderRepository,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $providers = $this->aiProviderRepository->listAll();
        $secretHints = [];

        foreach ($providers as $provider) {
            $secretHints[$provider->getId()] = $this->aiProviderRepository->findSecretHint($provider->getId());
        }

        return $this->viewRenderer->render(__DIR__ . '/template-index', [
            'providers' => $providers,
            'secretHints' => $secretHints,
            'successMessage' => null,
            'errorMessage' => null,
        ]);
    }
}
