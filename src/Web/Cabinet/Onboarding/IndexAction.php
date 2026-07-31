<?php

declare(strict_types=1);

namespace App\Web\Cabinet\Onboarding;

use App\Application\Auth\GetCurrentUser\GetCurrentUserHandler;
use App\Application\Profile\ProfileFormValues;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelRepositoryInterface;
use App\Domain\Channel\ChannelStatus;
use App\Domain\KnowledgeBase\KnowledgeBaseRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Domain\User\UserConstraints;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class IndexAction
{
    public function __construct(
        private readonly WebViewRenderer $viewRenderer,
        private readonly GetCurrentUserHandler $getCurrentUserHandler,
        private readonly KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->getCurrentUserHandler->handle();

        if ($user->isOnboardingCompleted()) {
            return $this->responseFactory
                ->createResponse(302)
                ->withHeader('Location', $this->urlGenerator->generate('cabinet/settings'));
        }

        $knowledgeBases = $this->knowledgeBaseRepository->listByUserId($user->getId());
        $telegramChannel = $this->channelRepository->findConnectedByUserAndProvider(
            $user->getId(),
            ChannelProvider::Telegram,
        );
        $isTelegramConnected = $telegramChannel !== null
            && $telegramChannel->getStatus() === ChannelStatus::Connected;
        $subscription = $this->subscriptionRepository->findActiveByUserId($user->getId());

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'user' => $user,
            'formValues' => ProfileFormValues::fromUser($user)->toArray(),
            'descriptionMaxLength' => UserConstraints::DESCRIPTION_MAX_LENGTH,
            'hasKnowledgeBase' => $knowledgeBases !== [],
            'knowledgeBases' => $knowledgeBases,
            'assignedKnowledgeBaseIds' => $telegramChannel?->getKnowledgeBaseIds() ?? [],
            'maxKnowledgeBases' => $subscription?->getMaxKnowledgeBases() ?? 1,
            'isTelegramConnected' => $isTelegramConnected,
            'telegramDisplayName' => $telegramChannel?->getDisplayName(),
            'saveProfileUrl' => $this->urlGenerator->generate('api/cabinet/onboarding/profile'),
            'createKnowledgeUrl' => $this->urlGenerator->generate('api/cabinet/settings/knowledge'),
            'connectTelegramUrl' => $this->urlGenerator->generate('api/cabinet/settings/telegram'),
            'assignKnowledgeUrl' => $this->urlGenerator->generate('api/cabinet/settings/knowledge'),
            'completeUrl' => $this->urlGenerator->generate('api/cabinet/onboarding/complete'),
            'settingsUrl' => $this->urlGenerator->generate('cabinet/settings'),
            'personalDataUrl' => $this->urlGenerator->generate('legal/personal-data'),
        ]);
    }
}
