<?php

namespace Rikudou\JsonApiBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreParseEvent extends Event
{
    public function __construct(private array $data)
    {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
