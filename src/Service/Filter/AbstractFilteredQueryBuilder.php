<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use function array_keys;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use function explode;
use function in_array;
use InvalidArgumentException;
use function is_array;
use ReflectionException;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
use function substr;
use Symfony\Component\HttpFoundation\ParameterBag;

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

        if (($queryParams->get('filter') || $queryParams->get('sort')) && ($useFilter || $useSort)) {
            $filter = $queryParams->get('filter', []);
            $sort = $queryParams->get('sort', '');

            if (!is_array($filter)) {
                throw new InvalidArgumentException('Invalid filter format');
            }

            $allowedProperties = array_keys($this->propertyParser->getApiProperties(new $class));

            if ($useFilter) {
                $i = 0;
                foreach ($filter as $key => $values) {
                    if (!in_array($key, $allowedProperties, true)) {
                        continue;
                    }

                    $values = explode(',', $values);
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
}
