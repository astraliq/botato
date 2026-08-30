<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Admin\Ai;

use App\Application\Admin\Ai\DeleteProvider\DeleteAiProviderHandler;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;

final readonly class DeleteProviderAction
{
    public function __construct(
        private DeleteAiProviderHandler $deleteAiProviderHandler,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(
        #[RouteArgument('id')]
        string $id,
    ): ResponseInterface {
        try {
            $this->deleteAiProviderHandler->handle($id);
        } catch (InvalidArgumentException) {
            // redirect anyway
        }

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $this->urlGenerator->generate('cabinet/admin/ai'));
    }
}
