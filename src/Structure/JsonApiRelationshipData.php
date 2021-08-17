<?php

namespace Rikudou\JsonApiBundle\Structure;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

final class JsonApiRelationshipData implements JsonSerializable
{
    public function __construct(private string $type, private int|string|null $id)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    #[ArrayShape(['id' => 'int|null|string', 'type' => 'string'])]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
        ];
    }
}
