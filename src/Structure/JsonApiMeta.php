<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiMeta implements JsonSerializable
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
     * @return float|int|bool|array<mixed>|string|null
     */
    public function getValue(): float|int|bool|array|string|null
    {
        return $this->value;
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
