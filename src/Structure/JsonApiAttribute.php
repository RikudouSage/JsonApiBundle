<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiAttribute implements JsonSerializable
{
    public function __construct(private string $name, private float|array|bool|int|string|null $value)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): float|array|bool|int|string|null
    {
        return $this->value;
    }

    public function setValue(float|int|bool|array|string|null $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            $this->name => $this->value,
        ];
    }
}
