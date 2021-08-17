<?php

namespace Rikudou\JsonApiBundle\Events;

use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class RouterPreroutingEvent extends Event
{
    public function __construct(
        private string $resourceName,
        private int|string|null $id,
        private ApiControllerInterface $controller,
    ) {
    }

    public function getController(): ApiControllerInterface
    {
        return $this->controller;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): self
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): self
    {
        $this->id = $id;

        return $this;
    }
}
