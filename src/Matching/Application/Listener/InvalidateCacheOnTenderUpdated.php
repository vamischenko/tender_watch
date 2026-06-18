<?php

declare(strict_types=1);

namespace App\Matching\Application\Listener;

use App\Tenders\Domain\Event\TenderUpdated;
use Psr\SimpleCache\CacheInterface;

final class InvalidateCacheOnTenderUpdated
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function __invoke(TenderUpdated $event): void
    {
        $this->cache->delete('tender:' . $event->tenderId);
    }
}
