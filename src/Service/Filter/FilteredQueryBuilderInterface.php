<?php

namespace Rikudou\JsonApiBundle\Service\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectValidator;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiPropertyParser;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method void setObjectValidator(ApiObjectValidator $validator)
 */
interface FilteredQueryBuilderInterface
{
    /**
     * @phpstan-param ParameterBag<string> $queryParams
     */
    public function get(string $class, ParameterBag $queryParams, bool $useFilter = true, bool $useSort = true): QueryBuilder;

    /**
     * Called by DI to set entity manager
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void;

    /**
     * Called by DI to set property parser
     */
    public function setPropertyParser(ApiPropertyParser $propertyParser): void;
}
