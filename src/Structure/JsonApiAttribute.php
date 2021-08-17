<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiAttribute implements JsonSerializable
{
    /**
     * @param float|array<mixed>|bool|int|string|null $value
     */
    public function __construct(private string $name, private float|array|bool|int|string|null $value)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float|array<mixed>|bool|int|string|null
     */
    public function getValue(): float|array|bool|int|string|null
    {
        return $this->value;
    }

    /**
     * @param float|int|bool|array<mixed>|string|null $value
     */
    public function setValue(float|int|bool|array|string|null $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @phpstan-return array<string, float|int|bool|array<mixed>|string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->name => $this->value,
        ];
    }
}
