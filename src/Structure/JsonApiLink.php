<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiLink implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $link;

    public function __construct(string $name, string $link)
    {
        $this->name = $name;
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return [
            $this->name => $this->link,
        ];
    }
}
