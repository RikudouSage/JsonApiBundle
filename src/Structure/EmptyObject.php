<?php

namespace Rikudou\JsonApiBundle\Structure;

use Countable;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use stdClass;

final class EmptyObject implements Countable, JsonSerializable
{
    public function count(): int
    {
        return 0;
    }

    #[Pure]
    public function jsonSerialize(): stdClass
    {
        return new stdClass();
    }
}
