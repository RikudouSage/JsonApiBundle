<?php

namespace Rikudou\JsonApiBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ApiResource
{
    /**
     * @param string|null   $name               Sets the name of the resource, will be guessed automatically if empty
     * @param string[]      $disabledProperties Name of properties that shouldn't be treated as api properties.
     *                                          Useful when the parent class has api properties, but you don't want to show them on children.
     * @param ApiProperty[] $enabledProperties  Properties that you want to enable additionally.
     *                                          Useful when you can't (or don't want to) edit the parent class, but you need to include its properties in api.
     * @param string|null   $plural             The plural name of the resource, will be guessed automatically if empty
     */
    public function __construct(
        public ?string $name = null,
        public array $disabledProperties = [],
        public array $enabledProperties = [],
        public ?string $plural = null,
    ) {
    }
}
