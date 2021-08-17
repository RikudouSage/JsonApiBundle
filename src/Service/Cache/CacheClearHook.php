<?php

namespace Rikudou\JsonApiBundle\Service\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

final class CacheClearHook implements CacheClearerInterface
{
    public function __construct(private bool $enabled, private AdapterInterface $cache)
    {
    }

    public function clear(string $cacheDir): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->clear();
    }
}
