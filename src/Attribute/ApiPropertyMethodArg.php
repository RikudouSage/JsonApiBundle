<?php

namespace Rikudou\JsonApiBundle\Attribute;

use Attribute;
use Rikudou\JsonApiBundle\Enum\ApiPropertyArgType;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ApiPropertyMethodArg
{
    public string $argumentType;

    public function __construct(
        string|ApiPropertyArgType $argumentType,
    ) {
        if ($argumentType instanceof ApiPropertyArgType) {
            $this->argumentType = $argumentType->value;
        } else {
            $this->argumentType = $argumentType;
        }
    }
}
