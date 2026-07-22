<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Redis;
use RedisException;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Shared session storage for multi-replica deployments.
 */
final class RedisSessionHandler implements SessionHandlerInterface
{
    private const KEY_PREFIX = 'botato:session:';

    private ?Redis $redis = null;

    private readonly int $lifetime;

    public function __construct(
        private readonly string $redisHost,
        private readonly int $redisPort = 6379,
        int $lifetimeSeconds = 604_800,
    ) {
        $this->lifetime = $lifetimeSeconds > 0 ? $lifetimeSeconds : 604_800;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $data = $this->connection()->get($this->key($id));
        } catch (RedisException) {
            return '';
        }

        return is_string($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        try {
            return (bool) $this->connection()->setex($this->key($id), $this->lifetime, $data);
        } catch (RedisException) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->connection()->del($this->key($id));
        } catch (RedisException) {
            return false;
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function connection(): Redis
    {
        if ($this->redis instanceof Redis) {
            return $this->redis;
        }

        $redis = new Redis();

        try {
            $redis->connect($this->redisHost, $this->redisPort);
        } catch (RedisException $exception) {
            throw new RuntimeException(
                'Не удалось подключиться к Redis для сессий.',
                previous: $exception,
            );
        }

        $this->redis = $redis;

        return $this->redis;
    }

    private function key(string $id): string
    {
        return self::KEY_PREFIX . $id;
    }
}
