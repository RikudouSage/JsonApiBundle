<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use function array_merge;
use function assert;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use function in_array;
use function method_exists;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Rikudou\JsonApiBundle\Annotation\ApiProperty;
use Rikudou\JsonApiBundle\Annotation\ApiResource;
use Rikudou\JsonApiBundle\Exception\InvalidApiPropertyConfig;
use Rikudou\JsonApiBundle\NameResolution\ApiNameResolutionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class ApiPropertyParser implements ServiceSubscriberInterface
{
    /**
     * @var ApiObjectValidator
     */
    private $objectValidator;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ApiParserCache
     */
    private $parserCache;

    /**
     * @var ApiNameResolutionInterface
     */
    private $nameResolution;

    public function __construct(
        ApiObjectValidator $objectValidator,
        ContainerInterface $container,
        ApiParserCache $parserCache,
        ApiNameResolutionInterface $nameResolution
    ) {
        $this->objectValidator = $objectValidator;
        $this->container = $container;
        $this->parserCache = $parserCache;
        $this->nameResolution = $nameResolution;
    }

    /**
     * @param object $object
     *
     *@throws \ReflectionException
     * @throws AnnotationException
     *
     * @return ApiObjectAccessor[]
     *
     */
    public function getApiProperties($object)
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

    public static function getSubscribedServices()
    {
        return [
            'rikudou_api.object_parser.parser' => ApiObjectParser::class,
        ];
    }

    private function getObjectParser(): ApiObjectParser
    {
        return $this->container->get('rikudou_api.object_parser.parser');
    }

    /**
     * @param ReflectionClass $class
     *
     * @throws AnnotationException
     *
     * @return array
     */
    private function getResourcePropertyConfig(ReflectionClass $class)
    {
        $result = [
            'enabled' => [],
            'disabled' => [],
        ];
        $annotationReader = new AnnotationReader();
        $annotation = $annotationReader->getClassAnnotation($class, ApiResource::class);
        if ($annotation instanceof ApiResource) {
            $result['enabled'] = $annotation->enabledProperties;
            $result['disabled'] = $annotation->disabledProperties;
        }

        return $result;
    }

    /**
     * @param ReflectionProperty[] $properties
     * @param ApiProperty[]        $classProperties
     * @param string[]             $disabled
     *
     *@throws AnnotationException
     *
     * @return ApiObjectAccessor[]
     *
     */
    private function parsedProperties(array $properties, array $classProperties, array $disabled)
    {
        $annotationReader = new AnnotationReader();
        $result = [];

        foreach ($properties as $property) {
            if (in_array($property->getName(), $disabled, true) && $property->getName() !== 'id') {
                continue;
            }
            if (isset($classProperties[$property->getName()])) {
                $annotation = $classProperties[$property->getName()];
            } else {
                $annotation = $annotationReader->getPropertyAnnotation($property, ApiProperty::class);
            }

            if ($annotation === null) {
                if ($property->getName() === 'id') {
                    $annotation = new ApiProperty();
                } else {
                    continue;
                }
            }
            assert($annotation instanceof ApiProperty);

            if ($annotation->name) {
                $name = $annotation->name;
            } else {
                $name = $this->nameResolution->getAttributeNameFromProperty($property->getName());
            }

            $result[$name] = $this->findPropertyAccessor($annotation, $property);
        }

        return $result;
    }

    /**
     * @param ApiProperty        $annotation
     * @param ReflectionProperty $property
     *
     * @return ApiObjectAccessor
     */
    private function findPropertyAccessor(ApiProperty $annotation, ReflectionProperty $property)
    {
        $class = $property->getDeclaringClass();

        $getter = $annotation->getter;
        $setter = $annotation->setter;
        $adder = $annotation->adder;
        $remover = $annotation->remover;
        $isRelation = $annotation->relation;

        if ($getter === null && $property->isPublic()) {
            return new ApiObjectAccessor(
                ApiObjectAccessor::TYPE_PROPERTY,
                null,
                null,
                null,
                null,
                $isRelation
            );
        }

        if ($getter !== null && !$class->hasMethod($getter)) {
            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_GETTER);
        }
        if ($setter !== null && !$class->hasMethod($setter)) {
            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_SETTER);
        }
        if ($adder !== null && !$class->hasMethod($adder)) {
            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_ADDER);
        }
        if ($remover !== null && !$class->hasMethod($remover)) {
            throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_REMOVER);
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
                throw new InvalidApiPropertyConfig(InvalidApiPropertyConfig::TYPE_GETTER);
            }
        }

        $setter = $setter ?? $this->nameResolution->getSetter($property->getName());
        $adder = $adder ?? $this->nameResolution->getAdder($property->getName());
        $remover = $remover ?? $this->nameResolution->getRemover($property->getName());

        return new ApiObjectAccessor(
            ApiObjectAccessor::TYPE_METHOD,
            $getter,
            $class->hasMethod($setter) ? $setter : null,
            $class->hasMethod($adder) ? $adder : null,
            $class->hasMethod($remover) ? $remover : null,
            $isRelation
        );
    }

    /**
     * @param ReflectionMethod[] $methods
     *
     * @throws AnnotationException
     *
     * @return ApiObjectAccessor[]
     */
    private function parsedMethods(array $methods)
    {
        $annotationReader = new AnnotationReader();
        $result = [];
        foreach ($methods as $method) {
            $annotation = $annotationReader->getMethodAnnotation($method, ApiProperty::class);
            if ($annotation === null) {
                if ($method->getName() === 'getId') {
                    $annotation = new ApiProperty();
                } else {
                    continue;
                }
            }

            assert($annotation instanceof ApiProperty);

            if ($annotation->name) {
                $name = $annotation->name;
            } else {
                $name = $this->nameResolution->getAttributeNameFromMethod($method->getName());
            }

            $result[$name] = new ApiObjectAccessor(
                ApiObjectAccessor::TYPE_METHOD,
                $method->getName(),
                $annotation->setter,
                $annotation->adder,
                $annotation->remover,
                $annotation->relation
            );
        }

        return $result;
    }
}
