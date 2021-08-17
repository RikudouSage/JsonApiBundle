<?php

namespace Rikudou\JsonApiBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreDeleteEvent extends Event
{
    public function __construct(private object $entity)
    {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function setEntity(object $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
