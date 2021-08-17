<?php

namespace Rikudou\JsonApiBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

final class JsonApiResponse extends JsonResponse
{
    public function __construct(
        mixed $data = null,
        int $status = 200,
        array $headers = [],
        bool $json = false,
    ) {
        $headers['Content-Type'] = 'application/vnd.api+json';
        parent::__construct($data, $status, $headers, $json);
    }
}
