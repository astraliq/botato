<?php

declare(strict_types=1);

namespace App\Domain\Channel;

interface ChannelRepositoryInterface
{
    public function findById(string $id): ?Channel;

    public function findConnectedByUserAndProvider(string $userId, ChannelProvider $provider): ?Channel;

    /** @return list<Channel> */
    public function listByUserId(string $userId): array;

    public function save(Channel $channel, ?string $secretPayloadJson = null): void;

    /** @return string|null decrypted JSON payload */
    public function getSecretPayload(string $channelId): ?string;

    public function findSecretHint(string $channelId): ?string;
}
