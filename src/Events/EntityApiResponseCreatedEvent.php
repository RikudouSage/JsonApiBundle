<?php

namespace Rikudou\JsonApiBundle\Events;

use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityApiResponseCreatedEvent extends Event
{
    public const TYPE_GET_ITEM = 'get_item';

    public const TYPE_GET_COLLECTION = 'get_collection';

    public const TYPE_UPDATE_ITEM = 'update_item';

    public const TYPE_CREATE_ITEM = 'create_item';

    public const TYPE_DELETE_ITEM = 'delete_item';

    /**
     * @var JsonApiCollection|JsonApiObject|null
     */
    private $data;

    /**
     * @var string
     */
    private $responseType;

    /**
     * @var string
     */
    private $apiResource;

    /**
     * @var string
     */
    private $apiResourceClass;

    /**
     * @param JsonApiObject|JsonApiCollection|null $data
     * @param string                          $responseType
     * @param string                          $apiResource
     * @param string                          $apiResourceClass
     */
    public function __construct($data, string $responseType, string $apiResource, string $apiResourceClass)
    {
        $this->data = $data;
        $this->responseType = $responseType;
        $this->apiResource = $apiResource;
        $this->apiResourceClass = $apiResourceClass;
    }

    /**
     * @return JsonApiCollection|JsonApiObject|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @return string
     */
    public function getApiResource(): string
    {
        return $this->apiResource;
    }

    /**
     * @return string
     */
    public function getApiResourceClass(): string
    {
        return $this->apiResourceClass;
    }

    /**
     * @param JsonApiCollection|JsonApiObject $data
     *
     * @return EntityApiResponseCreatedEvent
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
