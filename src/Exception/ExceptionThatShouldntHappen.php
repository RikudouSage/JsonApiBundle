<?php

namespace Rikudou\JsonApiBundle\Exception;

use RuntimeException;
use Throwable;

final class ExceptionThatShouldntHappen extends RuntimeException
{
    public function __construct(Throwable $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }
}
