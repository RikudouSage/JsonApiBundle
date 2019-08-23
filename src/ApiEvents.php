<?php

namespace Rikudou\JsonApiBundle;

final class ApiEvents
{
    /**
     * Triggered after the controller for the resource is found.
     * You can use this to change the resource and/or resource id.
     *
     * @Event("Rikudou\JsonApiBundle\Events\RouterPreroutingEvent")
     */
    public const PREROUTING = 'rikudou_api.prerouting';

    /**
     * Triggered before rendering the response, you can modify the data that will be sent to browser.
     *
     * @Event("Rikudou\JsonApiBundle\Events\ApiResponseCreatedEvent")
     */
    public const PRE_RESPONSE = 'rikudou_api.pre_response';
}
