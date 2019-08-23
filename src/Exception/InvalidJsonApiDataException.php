<?php

namespace Rikudou\JsonApiBundle\Exception;

use InvalidArgumentException;
use Throwable;

final class InvalidJsonApiDataException extends InvalidArgumentException
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('The array is not a valid JSON:API data', 0, $previous);
    }
}
