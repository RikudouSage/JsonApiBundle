<?php

namespace Rikudou\JsonApiBundle\Exception;

use JetBrains\PhpStorm\Pure;
use LogicException;
use Throwable;

final class InvalidApiObjectException extends LogicException
{
    #[Pure]
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('The variable is not a valid api object, it must be an object that has method getId()', 0, $previous);
    }
}
