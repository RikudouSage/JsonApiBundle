<?php

namespace Rikudou\JsonApiBundle\Exception;

use JetBrains\PhpStorm\Pure;
use RuntimeException;
use Throwable;

final class InvalidJsonApiArrayException extends RuntimeException
{
    #[Pure]
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('The array is not a valid JSON:API data structure', 0, $previous);
    }
}
