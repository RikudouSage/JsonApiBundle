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
     * @Event("Rikudou\JsonApiBundle\Events\EntityPreUpdateEvent")
     */
    public const PRE_UPDATE = 'rikudou_api.entity.pre_update';

    /**
     * @Event("Rikudou\JsonApiBundle\Events\EntityPreDeleteEvent")
     */
    public const PRE_DELETE = 'rikudou_api.entity.pre_delete';

    /**
     * Triggered before the json data are parsed into an entity
     *
     * @Event("Rikudou\JsonApiBundle\Events\EntityPreParseEvent")
     */
    public const PRE_PARSE = 'rikudou_api.entity.pre_parse';

    /**
     * Triggered before rendering the response, you can modify the json api object that will be sent to browser.
     *
     * @Event("Rikudou\JsonApiBundle\Events\EntityApiResponseCreatedEvent")
     */
    public const PRE_RESPONSE = 'rikudou_api.entity.pre_response';

    /**
     * Triggered for each filter property, allowing to customize the QueryBuilder
     *
     * @Event("Rikudou\JsonApiBundle\Events\EntityApiOnFilterSearchEvent")
     */
    public const ON_FILTER_SEARCH = 'rikudou_api.entity.on_filter_search';
}
