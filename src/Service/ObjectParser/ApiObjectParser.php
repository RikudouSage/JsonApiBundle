<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function assert;
use function call_user_func;
use function count;
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
use Rikudou\JsonApiBundle\Attribute\ApiResource;
use Rikudou\JsonApiBundle\Exception\AccessViolationException;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;
use Rikudou\JsonApiBundle\Exception\InvalidJsonApiArrayException;
use Rikudou\JsonApiBundle\Exception\ResourceNotFoundException;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use Rikudou\JsonApiBundle\Service\ApiNormalizerLocator;
use Rikudou\JsonApiBundle\Service\ApiResourceLocator;
use Rikudou\JsonApiBundle\Service\Inflector;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Rikudou\JsonApiBundle\Structure\JsonApiRelationshipData;
use TypeError;
use UnexpectedValueException;

final class ApiObjectParser
{
    private ApiResourceLocator $resourceLocator;

    public function __construct(
        private ApiPropertyParser $propertyParser,
        private ApiObjectValidator $objectValidator,
        private ApiParserCache $parserCache,
        private ApiNameResolutionInterface $nameResolution,
        private ApiNormalizerLocator $normalizerLocator,
        private EntityManagerInterface $entityManager,
        private Inflector $inflector,
    ) {
    }

    /**
     * @phpstan-return array<string, mixed>
     *
     * @throws ReflectionException
     */
    public function getJsonApiArray(object $object, bool $metadataOnly = false): array
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
                if (
                    (is_object($value) || is_string($value))
                    && $normalizer = $this->normalizerLocator->getNormalizerForClass($value)
                ) {
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
                            if (
                                (is_object($tmpValue) || is_string($tmpValue))
                                && $normalizer = $this->normalizerLocator->getNormalizerForClass($tmpValue)
                            ) {
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

        if (!count($result['relationships'])) {
            unset($result['relationships']);
        }
        if (!count($result['attributes'])) {
            unset($result['attributes']);
        }

        return $result;
    }

    /**
     * @param array<mixed> $data
     *
     * @throws ReflectionException
     * @throws ResourceNotFoundException
     */
    public function parseJsonApiArray(array $data): object
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
            } catch (MappingException) {
                $object = new $className;
            }
        }

        $this->parseArrayAttributes($object, $jsonApiObject);
        $this->parseRelationships($object, $jsonApiObject, $data);

        return $object;
    }

    public function getResourceName(object $object): ?string
    {
        return $this->getResourceNames($object)->name;
    }

    public function getPluralResourceName(object $object): ?string
    {
        return $this->getResourceNames($object)->plural;
    }

    public function setApiResourceLocator(ApiResourceLocator $resourceLocator): void
    {
        $this->resourceLocator = $resourceLocator;
    }

    private function getResourceNames(object $object): ApiResource
    {
        $this->objectValidator->throwOnInvalidObject($object);

        $cacheItem = $this->parserCache->getResourceCacheItem($object);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $reflection = new ReflectionClass($object);
        $attributes = $reflection->getAttributes(ApiResource::class);
        $attribute = isset($attributes[0]) ? $attributes[0]->newInstance() : null;
        if ($attribute === null) {
            $attribute = new ApiResource();
        }
        assert($attribute instanceof ApiResource);

        if (!$attribute->name) {
            $attribute->name = $this->nameResolution->getResourceName($reflection->getName());
        }
        if (!$attribute->plural) {
            $attribute->plural = $this->inflector->pluralize($attribute->name);
        }

        $cacheItem->set($attribute);
        $this->parserCache->save($cacheItem);

        return $attribute;
    }

    /**
     * @param array<mixed> $jsonData
     */
    private function getJsonObject(array $jsonData): JsonApiObject
    {
        if (isset($jsonData['data'])) {
            $jsonData = $jsonData['data'];
        }

        return new JsonApiObject($jsonData);
    }

    private function isRelation(mixed $value): bool
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
                && ((!is_object($value) && !is_string($value))
                    || $this->normalizerLocator->getNormalizerForClass($value) === null);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function parseArrayAttributes(object $object, JsonApiObject $jsonApiObject): void
    {
        $availableProperties = $this->propertyParser->getApiProperties($object);

        foreach ($jsonApiObject->getAttributes() as $attribute) {
            if ($attribute->getName() === 'id') {
                continue;
            }
            if (!isset($availableProperties[$attribute->getName()])) {
                throw new InvalidJsonApiArrayException(
                    new UnexpectedValueException("The attribute '{$attribute->getName()}' is not valid"),
                );
            }
            $config = $availableProperties[$attribute->getName()];
            switch ($config->getType()) {
                case ApiObjectAccessor::TYPE_PROPERTY:
                    $getter = $config->getGetter();
                    if (!$config->isReadonly()) {
                        $object->{$getter} = $attribute->getValue();
                    } elseif (!$config->isSilentFail()) {
                        throw new AccessViolationException("The property '{$attribute->getName()}' is readonly");
                    }
                    break;
                case ApiObjectAccessor::TYPE_METHOD:
                    if (!$config->getSetter() && !$config->getAdder() || $config->isReadonly()) {
                        if ($config->isSilentFail()) {
                            break;
                        }
                        throw new InvalidJsonApiArrayException(
                            new UnexpectedValueException("The property '{$attribute->getName()}' is read-only"),
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
                                new UnexpectedValueException("The property '{$attribute->getName()}' does not accept values of type '{$type}'"),
                            );
                        }
                    } else {
                        if (!is_iterable($attribute->getValue())) {
                            throw new InvalidJsonApiArrayException(
                                new UnexpectedValueException("The property '{$attribute->getName()}' accepts only arrays"),
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
                                    new UnexpectedValueException("The property '{$attribute->getName()}' does not accept values of type '{$type}'"),
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
     * @param array<string, array<string, array>> $rawData
     *
     * @throws ReflectionException
     * @throws ResourceNotFoundException
     */
    private function parseRelationships(
        object $object,
        JsonApiObject $jsonApiObject,
        array $rawData,
    ): void {
        $availableProperties = $this->propertyParser->getApiProperties($object);

        foreach ($jsonApiObject->getRelationships() as $relationship) {
            if (!isset($availableProperties[$relationship->getName()])) {
                throw new InvalidJsonApiArrayException(
                    new UnexpectedValueException("The relationship '{$relationship->getName()}' is not valid"),
                );
            }
//            if (!$relationship->hasData()) {
//                continue;
//            }

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
