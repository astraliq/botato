<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Redis;
use RedisException;
use SessionHandlerInterface;

/**
 * Shared session storage for multi-replica deployments.
 */
final class RedisSessionHandler implements SessionHandlerInterface
{
    private const KEY_PREFIX = 'botato:session:';

    private readonly Redis $redis;

    private readonly int $lifetime;

    public function __construct(string $redisHost, int $redisPort = 6379)
    {
        $lifetime = (int) ini_get('session.gc_maxlifetime');
        $this->lifetime = $lifetime > 0 ? $lifetime : 1440;

        $this->redis = new Redis();
        $this->redis->connect($redisHost, $redisPort);
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
            $data = $this->redis->get($this->key($id));
        } catch (RedisException) {
            return '';
        }

        return is_string($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        try {
            return (bool) $this->redis->setex($this->key($id), $this->lifetime, $data);
        } catch (RedisException) {
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $this->redis->del($this->key($id));
        } catch (RedisException) {
            return false;
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function key(string $id): string
    {
        return self::KEY_PREFIX . $id;
    }
}
