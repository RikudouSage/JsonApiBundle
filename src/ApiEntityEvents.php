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

    /**
     * Triggered before the json data are parsed into an entity
     */
    public const PRE_PARSE = 'rikudou_api.entity.pre_parse';

    /**
     * Triggered before rendering the response, you can modify the json api object that will be sent to browser.
     *
     * @Event("Rikudou\JsonApiBundle\Events\ApiResponseCreatedEvent")
     */
    public const PRE_RESPONSE = 'rikudou_api.entity.pre_response';
}
