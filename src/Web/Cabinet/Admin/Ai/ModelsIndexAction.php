<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Domain\Ai\AiProviderRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ModelsIndexAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly AiProviderRepositoryInterface $aiProviderRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(
        #[RouteArgument('id')]
        string $id,
    ): ResponseInterface {
        $provider = $this->aiProviderRepository->findById($id);

        if ($provider === null || $provider->isDeleted()) {
            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', $this->urlGenerator->generate('cabinet/admin/ai'));
        }

        return $this->viewRenderer->render(__DIR__ . '/template-models', [
            'provider' => $provider,
            'models' => $this->aiProviderRepository->listModelsByProvider($id),
            'successMessage' => null,
            'errorMessage' => null,
        ]);
    }
}
