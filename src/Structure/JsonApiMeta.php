<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiMeta implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array|bool|float|int|string|null
     */
    private $value;

    /**
     * @param string                           $name
     * @param int|string|null|array|float|bool $value
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
     * @return array|bool|float|int|string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            $this->name => $this->value,
        ];
    }
}
