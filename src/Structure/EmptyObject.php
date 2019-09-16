<?php

namespace Rikudou\JsonApiBundle\Structure;

use Countable;

final class EmptyObject implements Countable
{
    public function count()
    {
        return 0;
    }
}
