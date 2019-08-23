<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiRelationship;
use stdClass;

/**
 * @method JsonApiRelationship current()
 * @method JsonApiRelationship offsetGet($offset)
 * @method void offsetSet($offset, JsonApiRelationship $value)
 * @method $this add(JsonApiRelationship $value)
 */
final class JsonApiRelationshipCollection extends AbstractCollection implements JsonSerializable
{
    public function jsonSerialize()
    {
        $result = [
            'relationships' => [],
        ];
        if (!$this->count()) {
            $result['relationships'] = new stdClass();
        } else {
            /** @var JsonApiRelationship $relationship */
            foreach ($this->data as $relationship) {
                $result['relationships'] = array_merge($result['relationships'], $relationship->jsonSerialize());
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
            JsonApiRelationship::class,
        ];
    }
}
