<?php

namespace Rikudou\JsonApiBundle\Exception;

use Exception;
use JetBrains\PhpStorm\Pure;
use Throwable;

final class ResourceNotFoundException extends Exception
{
    #[Pure]
    public function __construct(string $resource, Throwable $previous = null)
    {
        parent::__construct("Could not find controller for resource '{$resource}'", 0, $previous);
    }
}
