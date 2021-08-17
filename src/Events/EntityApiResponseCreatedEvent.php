<?php

namespace Rikudou\JsonApiBundle\Events;

use JetBrains\PhpStorm\ExpectedValues;
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

    public function __construct(
        private JsonApiCollection|JsonApiObject|null $data,
        #[ExpectedValues(valuesFromClass: EntityApiResponseCreatedEvent::class)]
        private string $responseType,
        private string $apiResource,
        private string $apiResourceClass,
    ) {
    }

    public function getData(): JsonApiCollection|JsonApiObject|null
    {
        return $this->data;
    }

    #[ExpectedValues(valuesFromClass: EntityApiResponseCreatedEvent::class)]
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    public function getApiResource(): string
    {
        return $this->apiResource;
    }

    public function getApiResourceClass(): string
    {
        return $this->apiResourceClass;
    }

    public function setData(JsonApiCollection|JsonApiObject $data): self
    {
        $this->data = $data;

        return $this;
    }
}
