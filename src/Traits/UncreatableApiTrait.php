<?php

namespace Rikudou\JsonApiBundle\Traits;

use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\HttpFoundation\Response;

trait UncreatableApiTrait
{
    public function addItem(): JsonApiObject|JsonApiResponse
    {
        throw new JsonApiErrorException('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
