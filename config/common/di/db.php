<?php

declare(strict_types=1);

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;

/** @var array $params */

return [
    ConnectionInterface::class => static function () use ($params): ConnectionInterface {
        $databaseConfig = $params['database'];

        return new Connection(
            new Driver(
                $databaseConfig['dsn'],
                $databaseConfig['username'],
                $databaseConfig['password'],
            ),
            new SchemaCache(new ArrayCache()),
        );
    },
];
