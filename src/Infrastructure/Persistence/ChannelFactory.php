<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Channel\Channel;
use App\Domain\Channel\ChannelProvider;
use App\Domain\Channel\ChannelStatus;
use DateTimeImmutable;

final class ChannelFactory
{
    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): Channel
    {
        $provider = ChannelProvider::from((string) $row['provider']);
        $status = ChannelStatus::from((string) $row['status']);
        $settings = self::decodeSettings($row['settings'] ?? []);

        return new Channel(
            id: (string) $row['id'],
            userId: (string) $row['user_id'],
            provider: $provider,
            status: $status,
            externalId: isset($row['external_id']) && $row['external_id'] !== null && $row['external_id'] !== ''
                ? (string) $row['external_id']
                : null,
            displayName: isset($row['display_name']) && $row['display_name'] !== null && $row['display_name'] !== ''
                ? (string) $row['display_name']
                : null,
            settings: $settings,
            connectedAt: isset($row['connected_at']) && $row['connected_at'] !== null
                ? new DateTimeImmutable((string) $row['connected_at'])
                : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
            updatedAt: new DateTimeImmutable((string) $row['updated_at']),
            knowledgeBaseId: isset($row['knowledge_base_id']) && $row['knowledge_base_id'] !== null && $row['knowledge_base_id'] !== ''
                ? (string) $row['knowledge_base_id']
                : null,
        );
    }

    /**
     * @param mixed $settings
     * @return array<string, mixed>
     */
    private static function decodeSettings(mixed $settings): array
    {
        if (is_array($settings)) {
            $result = [];

            foreach ($settings as $key => $value) {
                if (is_string($key)) {
                    $result[$key] = $value;
                }
            }

            return $result;
        }

        if (is_string($settings) && $settings !== '') {
            $decoded = json_decode($settings, true);

            if (is_array($decoded)) {
                $result = [];

                foreach ($decoded as $key => $value) {
                    if (is_string($key)) {
                        $result[$key] = $value;
                    }
                }

                return $result;
            }
        }

        return [];
    }
}
