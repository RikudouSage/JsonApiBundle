<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiError;

/**
 * @method JsonApiError current()
 * @method JsonApiError offsetGet($offset)
 * @method void offsetSet($offset, JsonApiError $value)
 * @method $this add(JsonApiError $value)
 */
final class JsonApiErrorCollection extends AbstractCollection implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = [
            'errors' => [],
        ];

        /** @var JsonApiError $error */
        foreach ($this->data as $error) {
            $result['errors'][] = $error->jsonSerialize();
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
            JsonApiError::class,
        ];
    }
}
