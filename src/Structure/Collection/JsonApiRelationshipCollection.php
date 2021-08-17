<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiRelationship;

/**
 * @extends() AbstractCollection<JsonApiRelationship>
 */
final class JsonApiRelationshipCollection extends AbstractCollection implements JsonSerializable
{
    #[ArrayShape(['relationships' => "array|\Rikudou\JsonApiBundle\Structure\EmptyObject"])]
    public function jsonSerialize(): array
    {
        $result = [
            'relationships' => [],
        ];
        if (!$this->count()) {
            $result['relationships'] = new EmptyObject();
        } else {
            foreach ($this->data as $relationship) {
                $result['relationships'] = array_merge($result['relationships'], $relationship->jsonSerialize());
            }
        }

        return $result;
    }

    protected function getAllowedTypes(): array
    {
        return [
            JsonApiRelationship::class,
        ];
    }
}
