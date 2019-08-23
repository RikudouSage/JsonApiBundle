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
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Returns the collection of items
     *
     * @return JsonApiCollection|JsonApiResponse
     */
    public function getCollection();

    /**
     * Returns the item with given id
     *
     * @param string|int $id
     *
     * @return JsonApiObject|JsonApiResponse
     */
    public function getItem($id);

    /**
     * Adds a new item, should return the current item
     *
     * @return JsonApiObject|JsonApiResponse
     */
    public function addItem();

    /**
     * Deletes the item with given id
     *
     * @param string|int $id
     *
     * @return Response
     */
    public function deleteItem($id): Response;

    /**
     * Updates the item with given id
     *
     * @param int|string $id
     *
     * @return JsonApiObject|JsonApiResponse
     */
    public function updateItem($id);

    /**
     * This method is called by router to set the resource name
     *
     * @param string $resourceName
     */
    public function setResourceName(string $resourceName): void;

    /**
     * Called by the bundle, sets the controller's service name
     *
     * @param string $serviceName
     */
    public function setServiceName(string $serviceName): void;

    /**
     * @return string
     */
    public function getServiceName(): string;
}
