<?php

namespace Rikudou\JsonApiBundle\Structure;

use Countable;
use JsonSerializable;
use stdClass;

final class EmptyObject implements Countable, JsonSerializable
{
    public function count()
    {
        return 0;
    }

    public function jsonSerialize()
    {
        return new stdClass();
    }
}
