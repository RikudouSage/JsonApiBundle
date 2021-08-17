<?php

namespace Rikudou\JsonApiBundle\Exception;

use function is_array;
use function is_string;
use JetBrains\PhpStorm\Pure;
use Rikudou\JsonApiBundle\Structure\JsonApiError;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class JsonApiErrorException extends RuntimeException
{
    private string|array|JsonApiError $data;

    #[Pure]
    public function __construct(
        array|string|JsonApiError|null $message = null,
        int $code = Response::HTTP_BAD_REQUEST,
        Throwable $previous = null,
    ) {
        if (!is_array($message) && !is_string($message)) {
            $message = '';
        }
        $this->data = $message;
        if (!is_string($message)) {
            $message = '';
        }
        parent::__construct($message, $code, $previous);
    }

    public function getData(): JsonApiError|array|string
    {
        return $this->data;
    }
}
