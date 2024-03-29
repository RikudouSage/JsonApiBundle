<?php

namespace Rikudou\JsonApiBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreParseEvent extends Event
{
    /**
     * @param array<mixed> $data
     * @param class-string $class,
     */
    public function __construct(
        private array $data,
        private string $class,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}
