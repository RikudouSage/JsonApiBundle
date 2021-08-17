<?php

namespace Rikudou\JsonApiBundle\Interfaces;

use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\HttpFoundation\Response;

interface ApiControllerInterface
{
    /**
     * Returns the name of the class that this controller handles
     */
    public function getClass(): string;

    /**
     * Returns the collection of items
     */
    public function getCollection(): JsonApiCollection|JsonApiResponse;

    /**
     * Returns the item with given id
     */
    public function getItem(int|string $id): JsonApiObject|JsonApiResponse;

    /**
     * Adds a new item, should return the current item
     */
    public function addItem(): JsonApiObject|JsonApiResponse;

    /**
     * Deletes the item with given id
     */
    public function deleteItem(int|string $id): JsonApiResponse|Response|JsonApiObject;

    /**
     * Updates the item with given id
     */
    public function updateItem(int|string $id): JsonApiObject|JsonApiResponse;

    /**
     * This method is called by router to set the resource name
     */
    public function setResourceName(string $resourceName): void;

    /**
     * Called by the bundle, sets the controller's service name
     */
    public function setServiceName(string $serviceName): void;

    public function getServiceName(): string;
}
