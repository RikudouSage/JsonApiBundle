<?php

namespace Rikudou\JsonApiBundle\Service\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

final class CacheClearHook implements CacheClearerInterface
{
    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var AdapterInterface
     */
    private $cache;

    public function __construct(bool $enabled, AdapterInterface $cache)
    {
        $this->enabled = $enabled;
        $this->cache = $cache;
    }

    /**
     * Clears any caches necessary.
     *
     * @param string $cacheDir The cache directory
     */
    public function clear($cacheDir)
    {
        if (!$this->enabled) {
            return;
        }

        $this->cache->clear();
    }
}
