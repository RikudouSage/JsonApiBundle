<?php

namespace Rikudou\JsonApiBundle\Events;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityApiOnFilterSearchEvent extends Event
{
    public bool $handled = false;

    /**
     * @param class-string $className
     * @param array<string> $filterValues
     */
    public function __construct(
        public readonly QueryBuilder $queryBuilder,
        public readonly string $className,
        public readonly string $filterName,
        public readonly array $filterValues,
    ) {
    }
}
