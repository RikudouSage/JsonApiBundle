<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiLink implements JsonSerializable
{
    public function __construct(private string $name, private ?string $link)
    {
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): array
    {
        return [
            $this->name => $this->link,
        ];
    }
}
