<?php

namespace Rikudou\JsonApiBundle\Exception;

use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Throwable;

final class LockedCollectionException extends RuntimeException
{
    #[Pure]
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('The collection is locked and the values cannot be changed', 0, $previous);
    }
}
