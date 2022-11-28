<?php

namespace Rikudou\JsonApiBundle\Structure;

use BackedEnum;
use JsonSerializable;

final class JsonApiAttribute implements JsonSerializable
{
    /**
     * @param float|array<mixed>|bool|int|string|null|BackedEnum $value
     */
    public function __construct(
        private readonly string $name,
        private float|array|bool|int|string|null|BackedEnum $value,
    ) {
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
        if ($this->value instanceof BackedEnum) {
            return $this->value->value;
        }

        return $this->value;
    }

    /**
     * @param float|int|bool|array<mixed>|string|null $value
     */
    public function setValue(float|int|bool|array|string|null|BackedEnum $value): self
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
            $this->name => $this->getValue(),
        ];
    }
}
