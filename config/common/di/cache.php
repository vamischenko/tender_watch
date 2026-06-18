<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\Redis\RedisCache;

return [
    CacheInterface::class => static function (\Psr\Container\ContainerInterface $c): RedisCache {
        $params = $c->get('Yiisoft\Config\Config')->get('params');
        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => $params['redis']['host'],
            'port' => $params['redis']['port'],
            'database' => $params['redis']['database'],
        ]);
        return new RedisCache($redis);
    },
];
