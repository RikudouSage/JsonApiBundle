<?php

namespace Rikudou\JsonApiBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ApiProperty
{
    /**
     * @param string|null $name       The property name, will be constructed automatically if not set
     * @param string|null $setter     The setter method, will be guessed automatically if not set
     * @param string|null $adder      The adder method, will be guessed automatically if not set
     * @param string|null $getter     The getter method, will be guessed automatically if not set
     * @param string|null $remover    The remover method, will be guessed automatically if not set
     * @param bool|null   $relation   Sets whether the property should be treated as relation or not. Defaults to null which means auto-detect.
     * @param bool        $readonly   Whether the property is readonly or not
     * @param bool        $silentFail Whether unsupported operation should fail silently (e.g. trying to set property with no setter)
     */
    public function __construct(
        public ?string $name = null,
        public ?string $setter = null,
        public ?string $adder = null,
        public ?string $getter = null,
        public ?string $remover = null,
        public ?bool $relation = null,
        public bool $readonly = false,
        public bool $silentFail = false,
    ) {
    }
}
