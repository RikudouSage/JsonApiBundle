<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;

final class JsonApiRelationshipData implements JsonSerializable
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int|string
     */
    private $id;

    /**
     * JsonApiRelationshipData constructor.
     *
     * @param string     $type
     * @param int|string $id
     */
    public function __construct(string $type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
        ];
    }
}
