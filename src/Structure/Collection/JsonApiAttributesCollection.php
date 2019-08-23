<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiAttribute;

/**
 * @method JsonApiAttribute current()
 * @method JsonApiAttribute offsetGet($offset)
 * @method void offsetSet($offset, JsonApiAttribute $value)
 * @method $this add(JsonApiAttribute $value)
 */
final class JsonApiAttributesCollection extends AbstractCollection implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = [
            'attributes' => [],
        ];
        /** @var JsonApiAttribute $attribute */
        foreach ($this->data as $attribute) {
            $result['attributes'] = array_merge($result['attributes'], $attribute->jsonSerialize());
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
            JsonApiAttribute::class,
        ];
    }
}
