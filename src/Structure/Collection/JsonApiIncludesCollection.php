<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;

final class JsonApiIncludesCollection extends AbstractCollection implements JsonSerializable
{
    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'included' => array_map(function(JsonApiObject $item) {
                return $item->jsonSerialize()['data'];
            }, $this->data),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getAllowedTypes(): ?array
    {
        return [
            JsonApiObject::class,
        ];
    }
}
