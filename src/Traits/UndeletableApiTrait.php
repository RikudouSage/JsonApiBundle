<?php

namespace Rikudou\JsonApiBundle\Traits;

use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Symfony\Component\HttpFoundation\Response;

trait UndeletableApiTrait
{
    public function deleteItem($id): Response
    {
        throw new JsonApiErrorException('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
