<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Application\Admin\Ai\SaveProvider\SaveAiProviderHandler;
use App\Domain\Ai\AiApiStyle;
use App\Domain\Ai\AiProvider;
use App\Domain\Ai\AiProviderRepositoryInterface;
use App\Shared\ArrayHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ProviderFormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private AiProviderRepositoryInterface $aiProviderRepository,
        private SaveAiProviderHandler $saveAiProviderHandler,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        ?string $id = null,
    ): ResponseInterface {
        $providerId = is_string($id) && $id !== '' ? $id : null;
        $provider = null;
        $secretHint = null;

        if ($providerId !== null) {
            $provider = $this->aiProviderRepository->findById($providerId);

            if ($provider === null || $provider->isDeleted()) {
                return $this->responseFactory
                    ->createResponse(302)
                    ->withHeader('Location', $this->urlGenerator->generate('cabinet/admin/ai'));
            }

            $secretHint = $this->aiProviderRepository->findSecretHint($providerId);
        }

        $errorMessage = null;
        $formValues = $this->defaultFormValues($provider);

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];
            $formValues = array_merge($formValues, $this->extractFormValues($formData));

            try {
                $saved = $this->saveAiProviderHandler->handle($providerId, $formData);

                return $this->responseFactory
                    ->createResponse(302)
                    ->withHeader(
                        'Location',
                        $this->urlGenerator->generate('cabinet/admin/ai/providers/edit', [
                            'id' => $saved->getId(),
                        ]),
                    );
            } catch (InvalidArgumentException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/template-provider-form', [
            'provider' => $provider,
            'formValues' => $formValues,
            'secretHint' => $secretHint,
            'apiStyles' => AiApiStyle::cases(),
            'errorMessage' => $errorMessage,
            'successMessage' => null,
        ]);
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     base_url: string,
     *     api_host: string,
     *     api_style: string,
     *     sort_order: string,
     *     is_active: bool,
     *     is_default: bool,
     *     chat_completions_path: string,
     *     default_max_tokens: string,
     *     api_key: string
     * }
     */
    private function defaultFormValues(?AiProvider $provider): array
    {
        if ($provider === null) {
            return [
                'code' => '',
                'name' => '',
                'base_url' => 'https://api.aitunnel.ru/v1/',
                'api_host' => 'api.aitunnel.ru',
                'api_style' => AiApiStyle::OpenAiCompatible->value,
                'sort_order' => '0',
                'is_active' => true,
                'is_default' => false,
                'chat_completions_path' => '/chat/completions',
                'default_max_tokens' => '4096',
                'api_key' => '',
            ];
        }

        $settings = $provider->getSettings();

        return [
            'code' => $provider->getCode(),
            'name' => $provider->getName(),
            'base_url' => $provider->getBaseUrl(),
            'api_host' => $provider->getApiHost(),
            'api_style' => $provider->getApiStyle()->value,
            'sort_order' => (string) $provider->getSortOrder(),
            'is_active' => $provider->isActive(),
            'is_default' => $provider->isDefault(),
            'chat_completions_path' => (string) ($settings['chat_completions_path'] ?? '/chat/completions'),
            'default_max_tokens' => (string) ($settings['default_max_tokens'] ?? 4096),
            'api_key' => '',
        ];
    }

    /**
     * @param array<string, mixed> $formData
     * @return array<string, mixed>
     */
    private function extractFormValues(array $formData): array
    {
        return [
            'code' => (string) ($formData['code'] ?? ''),
            'name' => (string) ($formData['name'] ?? ''),
            'base_url' => (string) ($formData['base_url'] ?? ''),
            'api_host' => (string) ($formData['api_host'] ?? ''),
            'api_style' => (string) ($formData['api_style'] ?? ''),
            'sort_order' => (string) ($formData['sort_order'] ?? '0'),
            'is_active' => isset($formData['is_active']),
            'is_default' => isset($formData['is_default']),
            'chat_completions_path' => (string) ($formData['chat_completions_path'] ?? ''),
            'default_max_tokens' => (string) ($formData['default_max_tokens'] ?? ''),
            'api_key' => '',
        ];
    }
}
