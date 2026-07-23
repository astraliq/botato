<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Settings\Knowledge;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeArticleRepositoryInterface;
use App\Domain\KnowledgeBase\KnowledgeBase;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly KnowledgeArticleRepositoryInterface $knowledgeArticleRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();
        $knowledgeBases = $this->knowledgeBaseRepository->listByUserId($user->getId());
        $queryParams = $request->getQueryParams();
        $queryKnowledgeBaseId = $queryParams['kb'] ?? null;
        $selectedKnowledgeBaseId = is_string($queryKnowledgeBaseId) && $queryKnowledgeBaseId !== ''
            ? $queryKnowledgeBaseId
            : null;

        $selectedKnowledgeBase = $this->resolveSelectedKnowledgeBase($knowledgeBases, $selectedKnowledgeBaseId);
        $articles = $selectedKnowledgeBase !== null
            ? $this->knowledgeArticleRepository->listByKnowledgeBaseId($selectedKnowledgeBase->getId())
            : [];

        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $user->getId(),
            ChannelProvider::Telegram,
        );
        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId());

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'knowledgeBases' => $knowledgeBases,
            'selectedKnowledgeBase' => $selectedKnowledgeBase,
            'articles' => $articles,
            'isTelegramConnected' => $telegramChannel !== null,
            'telegramKnowledgeBaseId' => $telegramChannel?->getKnowledgeBaseId(),
            'maxKnowledgeBases' => $subscription?->getMaxKnowledgeBases() ?? 0,
        ]);
    }

    /**
     * @param list<KnowledgeBase> $knowledgeBases
     */
    private function resolveSelectedKnowledgeBase(array $knowledgeBases, ?string $selectedKnowledgeBaseId): ?KnowledgeBase
    {
        if ($knowledgeBases === []) {
            return null;
        }

        if ($selectedKnowledgeBaseId !== null) {
            foreach ($knowledgeBases as $knowledgeBase) {
                if ($knowledgeBase->getId() === $selectedKnowledgeBaseId) {
                    return $knowledgeBase;
                }
            }
        }

        return $knowledgeBases[0];
    }
}
