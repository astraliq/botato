<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Application\Admin\Ai\SaveModel\SaveAiModelHandler;
use App\Domain\Ai\AiModel;
use App\Domain\Ai\AiModelPurpose;
use App\Domain\Ai\AiProviderRepositoryInterface;
use App\Shared\ArrayHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ModelFormAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private AiProviderRepositoryInterface $aiProviderRepository,
        private SaveAiModelHandler $saveAiModelHandler,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        string $id,
        #[RouteArgument('modelId')]
        ?string $modelId = null,
    ): ResponseInterface {
        $modelId = is_string($modelId) && $modelId !== '' ? $modelId : null;
        $provider = $this->aiProviderRepository->findById($id);

        if ($provider === null || $provider->isDeleted()) {
            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', $this->urlGenerator->generate('cabinet/admin/ai'));
        }

        $model = null;

        if ($modelId !== null) {
            $model = $this->aiProviderRepository->findModelById($modelId);

            if ($model === null || $model->getProviderId() !== $id) {
                return $this->responseFactory
                    ->createResponse(302)
                    ->withHeader(
                        'Location',
                        $this->urlGenerator->generate('cabinet/admin/ai/models', ['id' => $id]),
                    );
            }
        }

        $errorMessage = null;
        $formValues = $this->defaultFormValues($model);

        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];
            $formValues = array_merge($formValues, $this->extractFormValues($formData));

            try {
                $saved = $this->saveAiModelHandler->handle($id, $modelId, $formData);

                return $this->responseFactory
                    ->createResponse(302)
                    ->withHeader(
                        'Location',
                        $this->urlGenerator->generate('cabinet/admin/ai/models/edit', [
                            'id' => $id,
                            'modelId' => $saved->getId(),
                        ]),
                    );
            } catch (InvalidArgumentException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/template-model-form', [
            'provider' => $provider,
            'model' => $model,
            'formValues' => $formValues,
            'purposes' => AiModelPurpose::cases(),
            'errorMessage' => $errorMessage,
            'successMessage' => null,
        ]);
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     purpose: string,
     *     sort_order: string,
     *     is_active: bool,
     *     is_default: bool
     * }
     */
    private function defaultFormValues(?AiModel $model): array
    {
        if ($model === null) {
            return [
                'code' => '',
                'name' => '',
                'purpose' => AiModelPurpose::Chat->value,
                'sort_order' => '0',
                'is_active' => true,
                'is_default' => false,
            ];
        }

        return [
            'code' => $model->getCode(),
            'name' => $model->getName(),
            'purpose' => $model->getPurpose()->value,
            'sort_order' => (string) $model->getSortOrder(),
            'is_active' => $model->isActive(),
            'is_default' => $model->isDefault(),
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
            'purpose' => (string) ($formData['purpose'] ?? ''),
            'sort_order' => (string) ($formData['sort_order'] ?? '0'),
            'is_active' => isset($formData['is_active']),
            'is_default' => isset($formData['is_default']),
        ];
    }
}
