<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use function array_keys;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use function explode;
use function in_array;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectAccessor;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
use function substr;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Uid\Uuid;

abstract class AbstractFilteredQueryBuilder implements FilteredQueryBuilderInterface
{
    private EntityManagerInterface $entityManager;

    private ApiPropertyParser $propertyParser;

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
                    }
                    $query = '';

                    for ($j = 0; $j < count($values); $j++) {
                        $operator = '=';
                        if (str_starts_with($values[$j], '!')) {
                            $operator = '!=';
                            $values[$j] = substr($values[$j], 1);
                        }
                        $query .= "entity.{$key} {$operator} :value{$i}{$j} OR ";
                    }

                    $query = substr($query, 0, -4);
                    $builder
                        ->andWhere($query);
                    for ($j = 0; $j < count($values); $j++) {
                        $builder
                            ->setParameter("value{$i}{$j}", $values[$j]);
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

    /**
     * @param class-string<object> $class
     *
     * @throws ReflectionException
     */
    private function isUuid(string $key, string $class): bool
    {
        $properties = $this->propertyParser->getApiProperties(new $class);
        if (!isset($properties[$key])) {
            return false;
        }

        $property = $properties[$key];
        if ($property->getType() === ApiObjectAccessor::TYPE_PROPERTY) {
            $reflection = new ReflectionProperty($class, $property->getGetter());
            $type = $reflection->getType();
        } else {
            $reflection = new ReflectionMethod($class, $property->getGetter());
            $type = $reflection->getReturnType();
        }

        if ($type === null) {
            return false;
        }

        $types = $type instanceof ReflectionNamedType ? [$type] : $type->getTypes();
        foreach ($types as $type) {
            if (is_a($type->getName(), Uuid::class, true)) {
                return true;
            }
        }

        return false;
    }
}
