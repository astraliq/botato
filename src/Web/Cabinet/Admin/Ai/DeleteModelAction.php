<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Application\Admin\Ai\DeleteModel\DeleteAiModelHandler;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;

final readonly class DeleteModelAction
{
    public function __construct(
        private DeleteAiModelHandler $deleteAiModelHandler,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(
        #[RouteArgument('id')]
        string $id,
        #[RouteArgument('modelId')]
        string $modelId,
    ): ResponseInterface {
        try {
            $this->deleteAiModelHandler->handle($id, $modelId);
        } catch (InvalidArgumentException) {
            // redirect anyway
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader(
                'Location',
                $this->urlGenerator->generate('cabinet/admin/ai/models', ['id' => $id]),
            );
    }
}
