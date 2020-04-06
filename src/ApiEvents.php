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
}
