<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiAttribute implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array|float|int|string|null|bool
     */
    private $value;

    /**
     * JsonApiObjectAttribute constructor.
     *
     * @param string                           $name
     * @param string|int|float|null|array|bool $value
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array|float|int|string|null|bool
     */
    public function getValue()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            $this->name => $this->value,
        ];
    }
}
