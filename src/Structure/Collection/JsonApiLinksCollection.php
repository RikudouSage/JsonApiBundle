<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiLink;

/**
 * @method JsonApiLink current()
 * @method JsonApiLink offsetGet($offset)
 * @method void offsetSet($offset, JsonApiLink $value)
 * @method $this add(JsonApiLink $value)
 */
final class JsonApiLinksCollection extends AbstractCollection implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = [
            'links' => [],
        ];
        if (!$this->count()) {
            $result['links'] = new EmptyObject();
        } else {
            /** @var JsonApiLink $link */
            foreach ($this->data as $link) {
                $result['links'] = array_merge($result['links'], $link->jsonSerialize());
            }
        }

        return $result;
    }

    /**
     * Returns the allowed type of value for this collection.
     * Return null to allow every type.
     *
     * @return array|null
     */
    protected function getAllowedTypes(): ?array
    {
        return [
            JsonApiLink::class,
        ];
    }
}
