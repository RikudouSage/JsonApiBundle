<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;

interface FilteredQueryBuilderInterface
{
    /**
     * @param string       $class
     * @param ParameterBag $queryParams
     * @param bool         $useFilter
     * @param bool         $useSort
     *
     * @return QueryBuilder
     */
    public function get(string $class, ParameterBag $queryParams, bool $useFilter = true, bool $useSort = true): QueryBuilder;
}
