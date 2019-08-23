<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function assert;
use function is_string;
use function method_exists;
use function preg_replace;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Rikudou\JsonApiBundle\Exception\ExceptionThatShouldntHappen;
use function sprintf;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class ApiParserCache
{
    /**
     * @var AdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var ApiObjectValidator
     */
    private $objectValidator;

    /**
     * @var bool
     */
    private $propertyCacheEnabled;

    public function __construct(
        AdapterInterface $cacheAdapter,
        ApiObjectValidator $objectValidator,
        bool $propertyCacheEnabled
    ) {
        $this->cacheAdapter = $cacheAdapter;
        $this->objectValidator = $objectValidator;
        $this->propertyCacheEnabled = $propertyCacheEnabled;
    }

    /**
     * @param object $object
     *
     * @return CacheItemInterface
     */
    public function getApiCacheItem($object): CacheItemInterface
    {
        $this->objectValidator->throwOnInvalidObject($object);

        try {
            return $this->cacheAdapter->getItem($this->getCacheName($object));
        } catch (InvalidArgumentException $e) {
            throw new ExceptionThatShouldntHappen($e);
        }
    }

    public function getResourceCacheItem($object): CacheItemInterface
    {
        $this->objectValidator->throwOnInvalidObject($object);

        try {
            return $this->cacheAdapter->getItem($this->getCacheName($object, 'ResourceObject'));
        } catch (InvalidArgumentException $e) {
            throw new ExceptionThatShouldntHappen($e);
        }
    }

    public function save(CacheItemInterface $cacheItem): void
    {
        if (!$this->propertyCacheEnabled) {
            return;
        }
        $this->cacheAdapter->save($cacheItem);
    }

    private function getCacheName($object, ?string $postfix = null): string
    {
        assert(method_exists($object, 'getId'));
        $name = sprintf(
            'ApiObjectParserCache_%s_%s',
            $this->objectValidator->getRealClass($object),
            $object->getId()
        );
        if ($postfix) {
            $name .= "_{$postfix}";
        }

        $result = preg_replace('@[^a-zA-Z0-9_.]@', '', $name);
        assert(is_string($result));

        return $result;
    }
}
