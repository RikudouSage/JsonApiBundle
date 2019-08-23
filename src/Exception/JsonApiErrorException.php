<?php

namespace Rikudou\JsonApiBundle\Exception;

use function is_array;
use function is_string;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class JsonApiErrorException extends RuntimeException
{
    /**
     * @var string|array
     */
    private $data;

    public function __construct($message = null, int $code = Response::HTTP_BAD_REQUEST, Throwable $previous = null)
    {
        if (!is_array($message) && !is_string($message)) {
            $message = '';
        }
        $this->data = $message;
        if (!is_string($message)) {
            $message = '';
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array|string
     */
    public function getData()
    {
        return $this->data;
    }
}
