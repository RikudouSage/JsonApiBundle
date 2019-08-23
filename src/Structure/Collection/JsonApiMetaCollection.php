<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiMeta;

/**
 * @method JsonApiMeta current()
 * @method JsonApiMeta offsetGet($offset)
 * @method void offsetSet($offset, JsonApiMeta $value)
 * @method $this add(JsonApiMeta $value)
 */
final class JsonApiMetaCollection extends AbstractCollection implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = [
            'meta' => [],
        ];
        /** @var JsonApiMeta $meta */
        foreach ($this->data as $meta) {
            $result['meta'] = array_merge($result['meta'], $meta->jsonSerialize());
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
            JsonApiMeta::class,
        ];
    }
}
