<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function array_merge;
use function assert;
use function in_array;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use function method_exists;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Rikudou\JsonApiBundle\Attribute\ApiProperty;
use Rikudou\JsonApiBundle\Attribute\ApiResource;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class ApiPropertyParser implements ServiceSubscriberInterface
{
    public function __construct(
        private ApiObjectValidator $objectValidator,
        private ContainerInterface $container,
        private ApiParserCache $parserCache,
        private ApiNameResolutionInterface $nameResolution,
    ) {
    }

    /**
     * @throws ReflectionException
     *
     * @return ApiObjectAccessor[]
     */
    public function getApiProperties(object $object): array
    {
        $this->objectValidator->throwOnInvalidObject($object);
        assert(method_exists($object, 'getId'));

        $cache = $this->parserCache->getApiCacheItem($object);
        if ($cache->isHit()) {
            return $cache->get();
        }

        $reflection = new ReflectionClass($this->objectValidator->getRealClass($object));

        $resourceConfig = $this->getResourcePropertyConfig($reflection);
        $properties = $this->parsedProperties($reflection->getProperties(), $resourceConfig['enabled'], $resourceConfig['disabled']);
        $methods = $this->parsedMethods($reflection->getMethods(ReflectionMethod::IS_PUBLIC));

        $result = array_merge($properties, $methods);
        $cache->set($result);
        $this->parserCache->save($cache);

        return $cache->get();
    }

    #[ArrayShape(['rikudou_api.object_parser.parser' => 'string'])]
    public static function getSubscribedServices(): array
    {
        return [
            'rikudou_api.object_parser.parser' => ApiObjectParser::class,
        ];
    }

    private function getObjectParser(): ApiObjectParser
    {
        return $this->container->get('rikudou_api.object_parser.parser');
    }

    #[Pure]
    #[ArrayShape(['enabled' => 'array|mixed', 'disabled' => 'array|mixed'])]
    private function getResourcePropertyConfig(ReflectionClass $class): array
    {
        $result = [
            'enabled' => [],
            'disabled' => [],
        ];
        $attributes = $class->getAttributes(ApiResource::class);
        if (!count($attributes)) {
            return $result;
        }

        $attribute = $attributes[0];
        if ($attribute instanceof ApiResource) {
            $result['enabled'] = $attribute->enabledProperties;
            $result['disabled'] = $attribute->disabledProperties;
        }

        return $result;
    }

    /**
     * @param ReflectionProperty[] $properties
     * @param ApiProperty[]        $classProperties
     * @param string[]             $disabled
     *
     * @return ApiObjectAccessor[]
     */
    private function parsedProperties(
        array $properties,
        array $classProperties,
        array $disabled,
    ): array {
        $result = [];

        foreach ($properties as $property) {
            if (in_array($property->getName(), $disabled, true) && $property->getName() !== 'id') {
                continue;
            }
            if (isset($classProperties[$property->getName()])) {
                $attribute = $classProperties[$property->getName()];
            } else {
                $attributes = $property->getAttributes(ApiProperty::class);
                $attribute = $attributes[0] ?? null;
            }

            if ($attribute === null) {
                if ($property->getName() === 'id') {
                    $attribute = new ApiProperty();
                } else {
                    continue;
                }
            }
            assert($attribute instanceof ApiProperty);

            if ($attribute->name) {
                $name = $attribute->name;
            } else {
                $name = $this->nameResolution->getAttributeNameFromProperty($property->getName());
            }

            $result[$name] = $this->findPropertyAccessor($attribute, $property);
        }

        return $result;
    }

    private function findPropertyAccessor(
        ApiProperty $attribute,
        ReflectionProperty $property,
    ): ApiObjectAccessor {
        $class = $property->getDeclaringClass();

        $getter = $attribute->getter;
        $setter = $attribute->setter;
        $adder = $attribute->adder;
        $remover = $attribute->remover;
        $isRelation = $attribute->relation;

        if ($attribute->readonly) {
            $setter = null;
            $adder = null;
            $remover = null;
        }

        if ($getter === null && $property->isPublic()) {
            return new ApiObjectAccessor(
                ApiObjectAccessor::TYPE_PROPERTY,
                $property->getName(),
                null,
                null,
                null,
                $isRelation,
                $attribute->readonly,
                $attribute->silentFail,
            );
        }

        if ($getter !== null && !$class->hasMethod($getter)) {
            throw new InvalidApiPropertyConfig(
                InvalidApiPropertyConfig::TYPE_GETTER,
                $property->getName(),
            );
        }
        if ($setter !== null && !$class->hasMethod($setter)) {
            throw new InvalidApiPropertyConfig(
                InvalidApiPropertyConfig::TYPE_SETTER,
                $property->getName(),
            );
        }
        if ($adder !== null && !$class->hasMethod($adder)) {
            throw new InvalidApiPropertyConfig(
                InvalidApiPropertyConfig::TYPE_ADDER,
                $property->getName(),
            );
        }
        if ($remover !== null && !$class->hasMethod($remover)) {
            throw new InvalidApiPropertyConfig(
                InvalidApiPropertyConfig::TYPE_REMOVER,
                $property->getName(),
            );
        }

        if ($getter === null) {
            $getterMethods = [
                $this->nameResolution->getGetter($property->getName()),
                $this->nameResolution->getIsser($property->getName()),
                $this->nameResolution->getHasser($property->getName()),
            ];
            foreach ($getterMethods as $getterMethod) {
                if ($class->hasMethod($getterMethod)) {
                    $getter = $getterMethod;
                    break;
                }
            }
            if ($getter === null) {
                throw new InvalidApiPropertyConfig(
                    InvalidApiPropertyConfig::TYPE_GETTER,
                    $property->getName(),
                );
            }
        }

        if (!$attribute->readonly) {
            $setter = $setter ?? $this->nameResolution->getSetter($property->getName());
            $adder = $adder ?? $this->nameResolution->getAdder($property->getName());
            $remover = $remover ?? $this->nameResolution->getRemover($property->getName());
        } else {
            $setter = '';
            $adder = '';
            $remover = '';
        }

        return new ApiObjectAccessor(
            ApiObjectAccessor::TYPE_METHOD,
            $getter,
            $class->hasMethod($setter) ? $setter : null,
            $class->hasMethod($adder) ? $adder : null,
            $class->hasMethod($remover) ? $remover : null,
            $isRelation,
            $attribute->readonly,
            $attribute->silentFail,
        );
    }

    /**
     * @param ReflectionMethod[] $methods
     *
     * @return ApiObjectAccessor[]
     */
    private function parsedMethods(array $methods): array
    {
        $result = [];
        foreach ($methods as $method) {
            $attributes = $method->getAttributes(ApiProperty::class);
            $attribute = $attributes[0] ?? null;
            if ($attribute === null) {
                if ($method->getName() === 'getId') {
                    $attribute = new ApiProperty();
                } else {
                    continue;
                }
            }

            assert($attribute instanceof ApiProperty);

            if ($attribute->name) {
                $name = $attribute->name;
            } else {
                $name = $this->nameResolution->getAttributeNameFromMethod($method->getName());
            }

            $result[$name] = new ApiObjectAccessor(
                ApiObjectAccessor::TYPE_METHOD,
                $method->getName(),
                $attribute->setter,
                $attribute->adder,
                $attribute->remover,
                $attribute->relation,
                $attribute->readonly,
                $attribute->silentFail,
            );
        }

        return $result;
    }
}
