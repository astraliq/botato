<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Support;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Support\CreateTicket\CreateSupportTicketDto;
use App\Application\Support\CreateTicket\CreateSupportTicketHandler;
use App\Application\Support\ListTickets\ListSupportTicketsHandler;
use App\Shared\ArrayHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CreateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private GetCurrentUserHandler $getCurrentUserHandler,
        private CreateSupportTicketHandler $createSupportTicketHandler,
        private ListSupportTicketsHandler $listSupportTicketsHandler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $parsedBody = $request->getParsedBody();
        $formData = is_array($parsedBody) ? ArrayHelper::toStringKeyArray($parsedBody) : [];
        $dto = CreateSupportTicketDto::fromArray($formData);

        $successMessage = null;
        $errorMessage = null;
        $question = $dto->question;

        try {
            $this->createSupportTicketHandler->handle($user->getId(), $dto);
            $successMessage = 'Вопрос отправлен. Мы ответим как можно скорее.';
            $question = '';
        } catch (InvalidArgumentException $exception) {
            $errorMessage = $exception->getMessage();
        }

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'tickets' => $this->listSupportTicketsHandler->handle($user->getId()),
            'question' => $question,
            'successMessage' => $successMessage,
            'errorMessage' => $errorMessage,
        ]);
    }
}
