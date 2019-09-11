<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function assert;
use function call_user_func;
use function count;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use function gettype;
use InvalidArgumentException;
use function is_callable;
use function is_countable;
use function is_iterable;
use LogicException;
use function method_exists;
use ReflectionClass;
use ReflectionException;
use Rikudou\JsonApiBundle\Annotation\ApiResource;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;
use Rikudou\JsonApiBundle\Exception\InvalidJsonApiArrayException;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use Rikudou\JsonApiBundle\Service\ApiNormalizerLocator;
use Rikudou\JsonApiBundle\Service\ApiResourceLocator;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Rikudou\JsonApiBundle\Structure\JsonApiRelationshipData;
use TypeError;
use UnexpectedValueException;

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

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ApiResourceLocator
     */
    private $resourceLocator;

    public function __construct(
        ApiPropertyParser $propertyParser,
        ApiObjectValidator $objectValidator,
        ApiParserCache $parserCache,
        ApiNameResolutionInterface $nameResolution,
        ApiNormalizerLocator $normalizerLocator,
        EntityManagerInterface $entityManager
    ) {
        $this->propertyParser = $propertyParser;
        $this->objectValidator = $objectValidator;
        $this->parserCache = $parserCache;
        $this->nameResolution = $nameResolution;
        $this->normalizerLocator = $normalizerLocator;
        $this->entityManager = $entityManager;
    }

    /**
     * @param object $object
     * @param bool   $metadataOnly
     *
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return array
     *
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

    public function parseJsonApiArray(array $data)
    {
        $this->objectValidator->throwOnInvalidArray($data);
        $jsonApiObject = $this->getJsonObject($data);
        if ($jsonApiObject->getType() === null) {
            throw new InvalidJsonApiArrayException(new InvalidArgumentException('The data must have a type'));
        }
        $className = $this->resourceLocator->getEntityFromResourceType($jsonApiObject->getType());

        if ($jsonApiObject->getId() === null) {
            $object = new $className;
        } else {
            try {
                $repository = $this->entityManager->getRepository($className);
                $object = $repository->find($jsonApiObject->getId());
            } catch (MappingException $e) {
                $object = new $className;
            }
        }

        $this->parseArrayAttributes($object, $jsonApiObject);
        $this->parseRelationships($object, $jsonApiObject, $data);

        return $object;
    }

    /**
     * @param object $object
     *
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return string
     *
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

    public function setApiResourceLocator(ApiResourceLocator $resourceLocator)
    {
        $this->resourceLocator = $resourceLocator;
    }

    private function getJsonObject(array $jsonData): JsonApiObject
    {
        if (isset($jsonData['data'])) {
            $jsonData = $jsonData['data'];
        }

        return new JsonApiObject($jsonData);
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
            return $this->objectValidator->isObjectValid($value)
                && $this->normalizerLocator->getNormalizerForClass($value) === null;
        }
    }

    /**
     * @param object        $object
     * @param JsonApiObject $jsonApiObject
     *
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function parseArrayAttributes($object, JsonApiObject $jsonApiObject): void
    {
        $availableProperties = $this->propertyParser->getApiProperties($object);

        foreach ($jsonApiObject->getAttributes() as $attribute) {
            if ($attribute->getName() === 'id') {
                continue;
            }
            if (!isset($availableProperties[$attribute->getName()])) {
                throw new InvalidJsonApiArrayException(
                    new UnexpectedValueException("The attribute '{$attribute->getName()}' is not valid")
                );
            }
            $config = $availableProperties[$attribute->getName()];
            switch ($config->getType()) {
                case ApiObjectAccessor::TYPE_PROPERTY:
                    $getter = $config->getGetter();
                    $object->{$getter} = $attribute->getValue();
                    break;
                case ApiObjectAccessor::TYPE_METHOD:
                    if (!$config->getSetter() && !$config->getAdder()) {
                        throw new InvalidJsonApiArrayException(
                            new UnexpectedValueException("The property '{$attribute->getName()}' is read-only")
                        );
                    }
                    if ($config->getSetter()) {
                        $setter = [$object, $config->getSetter()];
                        if (!is_callable($setter)) {
                            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_SETTER, $attribute->getName());
                        }

                        try {
                            call_user_func($setter, $attribute->getValue());
                        } catch (TypeError $error) {
                            $type = gettype($attribute->getValue());
                            throw new InvalidJsonApiArrayException(
                                new UnexpectedValueException("The property '{$attribute->getName()}' does not accept values of type '{$type}'")
                            );
                        }
                    } else {
                        if (!is_iterable($attribute->getValue())) {
                            throw new InvalidJsonApiArrayException(
                                new UnexpectedValueException("The property '{$attribute->getName()}' accepts only arrays")
                            );
                        }
                        $adder = [$object, $config->getAdder()];

                        if (!is_callable($adder)) {
                            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_ADDER, $attribute->getName());
                        }

                        foreach ($attribute->getValue() as $item) {
                            try {
                                call_user_func($adder, $item);
                            } catch (TypeError $error) {
                                $type = gettype($attribute->getValue());
                                throw new InvalidJsonApiArrayException(
                                    new UnexpectedValueException("The property '{$attribute->getName()}' does not accept values of type '{$type}'")
                                );
                            }
                        }
                    }
                    break;
                default:
                    throw new LogicException('Unknown type');
            }
        }
    }

    /**
     * @param object        $object
     * @param JsonApiObject $jsonApiObject
     * @param array         $rawData
     *
     * @throws AnnotationException
     * @throws ReflectionException
     */
    private function parseRelationships($object, JsonApiObject $jsonApiObject, array $rawData)
    {
        $availableProperties = $this->propertyParser->getApiProperties($object);

        foreach ($jsonApiObject->getRelationships() as $relationship) {
            if (!isset($availableProperties[$relationship->getName()])) {
                throw new InvalidJsonApiArrayException(
                    new UnexpectedValueException("The relationship '{$relationship->getName()}' is not valid")
                );
            }
            if (!$relationship->hasData()) {
                throw new InvalidJsonApiArrayException(
                    new UnexpectedValueException("The relationship '{$relationship->getName()}' config must contain a data array")
                );
            }

            $data = $relationship->getData();
            $raw = $rawData['data']['relationships'][$relationship->getName()];
            $config = $availableProperties[$relationship->getName()];

            if ($data instanceof JsonApiRelationshipData || $data === null) {
                switch ($config->getType()) {
                    case ApiObjectAccessor::TYPE_PROPERTY:
                        $getter = $config->getGetter();
                        $object->{$getter} = $this->parseJsonApiArray($raw);
                        break;
                    case ApiObjectAccessor::TYPE_METHOD:
                        if (!$config->getSetter()) {
                            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_SETTER, $relationship->getName());
                        }
                        $setter = [$object, $config->getSetter()];
                        if (!is_callable($setter)) {
                            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_SETTER, $relationship->getName());
                        }
                        call_user_func($setter, $raw['data'] === null ? null : $this->parseJsonApiArray($raw));
                        break;
                    default:
                        throw new LogicException("Unknown type '{$config->getType()}'");
                }
            } elseif (is_iterable($data)) {
                switch ($config->getType()) {
                    case ApiObjectAccessor::TYPE_PROPERTY:
                        $getter = $config->getGetter();
                        foreach ($data as $index => $relationshipData) {
                            $object->{$getter}[] = $this->parseJsonApiArray(['data' => $raw['data'][$index]]);
                        }
                        break;
                    case ApiObjectAccessor::TYPE_METHOD:
                        if (!$config->getAdder() && !$config->getSetter()) {
                            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_ADDER, $relationship->getName());
                        }
                        if ($config->getAdder()) {
                            $adder = [$object, $config->getAdder()];
                            if (!is_callable($adder)) {
                                throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_ADDER, $relationship->getName());
                            }

                            foreach ($data as $index => $relationshipData) {
                                call_user_func($adder, $this->parseJsonApiArray(['data' => $raw['data'][$index]]));
                            }
                        } else {
                            $setter = [$object, $config->getSetter()];
                            if (!is_callable($setter)) {
                                throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_SETTER, $relationship->getName());
                            }

                            $tempData = [];
                            foreach ($data as $index => $relationshipData) {
                                $tempData[$index] = $this->parseJsonApiArray($raw['data'][$index]);
                            }

                            call_user_func($setter, $tempData);
                        }
                        break;
                    default:
                        throw new LogicException("Unknown type '{$config->getType()}'");
                }
            } else {
                throw new LogicException('The data is of unknown type');
            }
        }
    }
}
