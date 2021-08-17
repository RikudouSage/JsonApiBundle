<?php

namespace Rikudou\JsonApiBundle\Structure;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

final class JsonApiError implements JsonSerializable
{
    private string $statusCode;

    public function __construct(
        private string $title,
        private string $description = '',
        int $statusCode = Response::HTTP_BAD_REQUEST,
    ) {
        $this->statusCode = (string) $statusCode;
    }

    /**
     * @return array<string,string>
     */
    #[ArrayShape(['title' => 'string', 'detail' => 'string', 'status' => 'string'])]
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'detail' => $this->description,
            'status' => $this->statusCode,
        ];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    public function setStatusCode(string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
