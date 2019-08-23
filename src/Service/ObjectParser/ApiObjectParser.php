<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function assert;
use function count;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use function is_countable;
use function is_iterable;
use function method_exists;
use ReflectionClass;
use ReflectionException;
use Rikudou\JsonApiBundle\Annotation\ApiResource;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use Rikudou\JsonApiBundle\Service\ApiNormalizerLocator;

final class ApiObjectParser
{
    /**
     * @var ApiPropertyParser
     */
    private $propertyParser;

    /**
     * @var ApiObjectValidator
     */
    private $objectValidator;

    /**
     * @var ApiParserCache
     */
    private $parserCache;

    /**
     * @var ApiNameResolutionInterface
     */
    private $nameResolution;

    /**
     * @var ApiNormalizerLocator
     */
    private $normalizerLocator;

    public function __construct(
        ApiPropertyParser $propertyParser,
        ApiObjectValidator $objectValidator,
        ApiParserCache $parserCache,
        ApiNameResolutionInterface $nameResolution,
        ApiNormalizerLocator $normalizerLocator
    ) {
        $this->propertyParser = $propertyParser;
        $this->objectValidator = $objectValidator;
        $this->parserCache = $parserCache;
        $this->nameResolution = $nameResolution;
        $this->normalizerLocator = $normalizerLocator;
    }

    /**
     * @param object $object
     * @param bool   $metadataOnly
     *
     * @throws AnnotationException
     * @throws ReflectionException
     *
     * @return array
     */
    public function getJsonApiArray($object, bool $metadataOnly = false)
    {
        $this->objectValidator->throwOnInvalidObject($object);

        assert(method_exists($object, 'getId'));

        $result = [
            'id' => $object->getId(),
            'type' => $this->getResourceName($object),
            'attributes' => [],
            'relationships' => [],
        ];

        $properties = $this->propertyParser->getApiProperties($object);

        foreach ($properties as $property => $accessor) {
            $value = $accessor->getValue($object);
            if ($property === 'id') {
                $result[$property] = $value;
            } elseif (!$metadataOnly) {
                if ($normalizer = $this->normalizerLocator->getNormalizerForClass($value)) {
                    $result['attributes'][$property] = $normalizer->getNormalizedValue($value);
                } elseif (($accessor->isRelation() === true) || ($accessor->isRelation() === null && $this->isRelation($value))) {
                    if (is_iterable($value)) {
                        $result['relationships'][$property]['data'] = [];
                        foreach ($value as $item) {
                            $relationData = $this->getJsonApiArray($item, true);
                            $result['relationships'][$property]['data'][] = $relationData;
                        }
                    } elseif ($value !== null) {
                        $relationData = $this->getJsonApiArray($value, true);
                        $result['relationships'][$property]['data'] = $relationData;
                    } else {
                        $result['relationships'][$property]['data'] = null;
                    }
                } else {
                    if (is_iterable($value)) {
                        $tmp = $value;
                        $value = [];

                        foreach ($tmp as $key => $tmpValue) {
                            if ($normalizer = $this->normalizerLocator->getNormalizerForClass($tmpValue)) {
                                $value[$key] = $normalizer->getNormalizedValue($tmpValue);
                            } else {
                                $value[$key] = $tmpValue;
                            }
                        }
                    }
                    $result['attributes'][$property] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param object $object
     *
     * @throws AnnotationException
     * @throws ReflectionException
     *
     * @return string
     */
    public function getResourceName($object): string
    {
        $this->objectValidator->throwOnInvalidObject($object);

        $cacheItem = $this->parserCache->getResourceCacheItem($object);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $reflection = new ReflectionClass($object);
        $annotationReader = new AnnotationReader();
        $annotation = $annotationReader->getClassAnnotation($reflection, ApiResource::class);
        if ($annotation === null) {
            $annotation = new ApiResource();
        }
        assert($annotation instanceof ApiResource);

        if (!$annotation->name) {
            $annotation->name = $this->nameResolution->getResourceName($reflection->getName());
        }

        $cacheItem->set($annotation->name);
        $this->parserCache->save($cacheItem);

        return $annotation->name;
    }

    private function isRelation($value)
    {
        if (is_iterable($value)) {
            if (is_countable($value) && !count($value)) {
                return false;
            }
            foreach ($value as $item) {
                if (!$this->isRelation($item)) {
                    return false;
                }
            }

            return true;
        } else {
            return $this->objectValidator->isValid($value)
                   && $this->normalizerLocator->getNormalizerForClass($value) === null;
        }
    }
}
