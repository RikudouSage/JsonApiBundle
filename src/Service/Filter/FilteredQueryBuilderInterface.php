<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
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

    /**
     * Called by DI to set entity manager
     *
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void;

    /**
     * Called by DI to set property parser
     *
     * @param ApiPropertyParser $propertyParser
     */
    public function setPropertyParser(ApiPropertyParser $propertyParser): void;
}
