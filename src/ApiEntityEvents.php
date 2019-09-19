<?php

namespace Rikudou\JsonApiBundle;

final class ApiEntityEvents
{
    /**
     * Triggered before the entity is persisted via Doctrine
     *
     * @Event("Rikudou\JsonApiBundle\Events\EntityPreCreateEvent")
     */
    public const PRE_CREATE = 'rikudou_api.entity.pre_create';
}
