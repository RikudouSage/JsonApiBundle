<?php

namespace Rikudou\JsonApiBundle\Traits;

trait ImmutableApiTrait
{
    use UncreatableApiTrait;
    use UnupdatableApiTrait;
    use UndeletableApiTrait;
}
