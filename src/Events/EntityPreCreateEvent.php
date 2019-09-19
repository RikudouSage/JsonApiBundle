<?php

namespace Rikudou\JsonApiBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityPreCreateEvent extends Event
{
    /**
     * @var object
     */
    private $entity;

    /**
     * EntityPreCreateEvent constructor.
     *
     * @param object $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object $entity
     *
     * @return EntityPreCreateEvent
     */
    public function setEntity($entity): EntityPreCreateEvent
    {
        $this->entity = $entity;

        return $this;
    }
}
