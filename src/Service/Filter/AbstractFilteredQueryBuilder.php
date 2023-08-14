<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use function array_keys;
use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use function explode;
use function in_array;
use LogicException;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectAccessor;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectValidator;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
use function substr;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Uid\Uuid;

abstract class AbstractFilteredQueryBuilder implements FilteredQueryBuilderInterface
{
    private EntityManagerInterface $entityManager;

    private ApiPropertyParser $propertyParser;

    private ApiObjectValidator $objectValidator;

    /**
     * @throws ReflectionException
     */
    public function get(
        string $class,
        ParameterBag $queryParams,
        bool $useFilter = true,
        bool $useSort = true,
    ): QueryBuilder {
        $builder = $this->entityManager->createQueryBuilder();
        $builder
            ->select('entity')
            ->from($class, 'entity');

        if (($queryParams->has('filter') || $queryParams->has('sort')) && ($useFilter || $useSort)) {
            $filter = $queryParams->all('filter');
            $sort = $queryParams->get('sort', '');

            $allowedProperties = array_keys($this->propertyParser->getApiProperties(new $class));

            if ($useFilter) {
                $i = 0;
                foreach ($filter as $key => $values) {
                    if (!in_array($key, $allowedProperties, true)) {
                        continue;
                    }

                    $values = explode(',', $values);
                    if ($this->isUuid($key, $class)) {
                        $values = array_map(
                            fn (string $value) => Uuid::fromString($value)->toBinary(),
                            $values,
                        );
                    } elseif ($this->isBackedEnum($key, $class)) {
                        $enumType = $this->getPropertyType($key, $class);
                        assert(is_a($enumType, BackedEnum::class, true));
                        $values = array_map(
                            fn (string $value) => $enumType::from(is_numeric($value) ? (int) $value : $value),
                            $values,
                        );
                    } elseif ($this->isRelation($key, $class)) {
                        $values = array_map(
                            fn (string $value) => $this->findEntityById($key, $class, $value),
                            $values,
                        );
                    }
                    $query = '';

                    for ($j = 0; $j < count($values); $j++) {
                        $operator = '=';
                        if (is_string($values[$j]) && str_starts_with($values[$j], '!')) {
                            $operator = '!=';
                            $values[$j] = substr($values[$j], 1);
                        }
                        $query .= "entity.{$key} {$operator} :value{$i}{$j} OR ";
                    }

                    $query = substr($query, 0, -4);
                    $builder
                        ->andWhere($query);
                    for ($j = 0; $j < count($values); $j++) {
                        $currentValue = $values[$j];
                        if ($this->objectValidator->isObjectValid($currentValue)) {
                            $currentValue = $currentValue->getId();
                            if ($currentValue instanceof Uuid) {
                                $currentValue = $currentValue->toBinary();
                            }
                        }
                        $builder->setParameter("value{$i}{$j}", $currentValue);
                    }
                    $i++;
                }
            }

            if ($sort && $useSort) {
                $sortFields = explode(',', $sort);
                foreach ($sortFields as $sortField) {
                    $mode = 'ASC';
                    if (str_starts_with($sortField, '-')) {
                        $mode = 'DESC';
                        $sortField = substr($sortField, 1);
                    }
                    if (!in_array($sortField, $allowedProperties)) {
                        continue;
                    }
                    $builder->addOrderBy("entity.{$sortField}", $mode);
                }
            }
        }

        return $builder;
    }

    ////////////// CONTAINER //////////////

    /**
     * @internal
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @internal
     */
    public function setPropertyParser(ApiPropertyParser $propertyParser): void
    {
        $this->propertyParser = $propertyParser;
    }

    public function setObjectValidator(ApiObjectValidator $validator): void
    {
        $this->objectValidator = $validator;
    }

    /**
     * @param class-string<object> $class
     *
     * @throws ReflectionException
     */
    private function isUuid(string $key, string $class): bool
    {
        try {
            $type = $this->getPropertyType($key, $class);

            return is_a($type, Uuid::class, true);
        } catch (LogicException) {
            return false;
        }
    }

    private function isBackedEnum(string $key, string $class): bool
    {
        try {
            $type = $this->getPropertyType($key, $class);

            return is_a($type, BackedEnum::class, true);
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * @param class-string<object> $class
     *
     * @throws ReflectionException
     */
    private function isRelation(string $key, string $class): bool
    {
        $properties = $this->propertyParser->getApiProperties(new $class);
        if (!isset($properties[$key])) {
            return false;
        }

        $property = $properties[$key];
        $isRelation = $property->isRelation();
        if (is_bool($isRelation)) {
            return $isRelation;
        }

        try {
            $type = $this->getPropertyType($key, $class);
        } catch (LogicException) {
            return false;
        }

        return $this->entityManager->getMetadataFactory()->isTransient($type);
    }

    private function findEntityById(string $key, string $class, string $id): ?object
    {
        $targetClass = $this->getPropertyType($key, $class);

        return $this->entityManager->getRepository($targetClass)->find(
            Uuid::isValid($id) ? Uuid::fromString($id)->toBinary() : $id,
        );
    }

    /**
     * @param class-string<object> $class
     *
     * @return class-string<object>
     */
    private function getPropertyType(string $key, string $class): string
    {
        $properties = $this->propertyParser->getApiProperties(new $class);
        if (!isset($properties[$key])) {
            throw new LogicException("Property '{$key}' does not exist.");
        }

        $property = $properties[$key];

        if ($property->getType() === ApiObjectAccessor::TYPE_PROPERTY) {
            $reflection = new ReflectionProperty($class, $property->getGetter());
            $type = $reflection->getType();
        } else {
            $reflection = new ReflectionMethod($class, $property->getGetter());
            $type = $reflection->getReturnType();
        }
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Property '{$key}' has an invalid type.");
        }
        $targetClass = $type->getName();
        if (!class_exists($targetClass)) {
            throw new LogicException("Property '{$key}' type does not reference a valid class.");
        }

        return $targetClass;
    }
}
