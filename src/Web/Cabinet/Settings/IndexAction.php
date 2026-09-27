<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private GetCurrentUserHandler $getCurrentUserHandler,
        private ChannelRepositoryInterface $channelRepository,
        private KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $user->getId(),
            ChannelProvider::Telegram,
        );
        $knowledgeBases = $this->knowledgeBaseRepository->listByUserId($user->getId());
        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId());

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'telegramChannel' => $telegramChannel,
            'telegramSecretHint' => $telegramChannel !== null
                ? $this->channelRepository->findSecretHint($telegramChannel->getId())
                : null,
            'knowledgeBases' => $knowledgeBases,
            'assignedKnowledgeBaseIds' => $telegramChannel?->getKnowledgeBaseIds() ?? [],
            'maxKnowledgeBases' => $subscription?->getMaxKnowledgeBases() ?? 0,
        ]);
    }
}
