<?php

namespace Rikudou\JsonApiBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreDeleteEvent extends Event
{
    private object $entity;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
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
