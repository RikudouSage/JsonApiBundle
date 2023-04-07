<?php

namespace Rikudou\JsonApiBundle\Traits;

use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

trait UndeletableApiTrait
{
    public function deleteItem(int|string|Uuid $id): JsonApiResponse|Response|JsonApiObject|JsonApiCollection
    {
        throw new JsonApiErrorException('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
