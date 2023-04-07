<?php

namespace Rikudou\JsonApiBundle\Traits;

use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

trait UnupdatableApiTrait
{
    public function updateItem(int|string|Uuid $id): JsonApiObject|JsonApiResponse
    {
        throw new JsonApiErrorException('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
