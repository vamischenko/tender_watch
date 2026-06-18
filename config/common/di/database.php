<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Definitions\DynamicReference;

return [
    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => DynamicReference::to(static function (\Psr\Container\ContainerInterface $c): Driver {
                $params = $c->get('Yiisoft\Config\Config')->get('params');
                return new Driver(
                    $params['db']['dsn'],
                    $params['db']['username'],
                    $params['db']['password'],
                );
            }),
        ],
    ],
];
