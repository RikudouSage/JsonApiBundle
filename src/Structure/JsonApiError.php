<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

final class JsonApiError implements JsonSerializable
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $statusCode;

    public function __construct(string $title, string $description = '', int $statusCode = Response::HTTP_BAD_REQUEST)
    {
        $this->title = $title;
        $this->description = $description;
        $this->statusCode = (string) $statusCode;
    }

    public function jsonSerialize()
    {
        return [
            'title' => $this->title,
            'detail' => $this->description,
            'status' => $this->statusCode,
        ];
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return JsonApiError
     */
    public function setTitle(string $title): JsonApiError
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return JsonApiError
     */
    public function setDescription(string $description): JsonApiError
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    /**
     * @param string $statusCode
     *
     * @return JsonApiError
     */
    public function setStatusCode(string $statusCode): JsonApiError
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
