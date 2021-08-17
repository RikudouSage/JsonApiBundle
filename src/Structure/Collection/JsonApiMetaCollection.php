<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiMeta;

/**
 * @extends() AbstractCollection<JsonApiMeta>
 */
final class JsonApiMetaCollection extends AbstractCollection implements JsonSerializable
{
    #[Pure]
    #[ArrayShape(['meta' => "array|\Rikudou\JsonApiBundle\Structure\EmptyObject"])]
    public function jsonSerialize(): array
    {
        $result = [
            'meta' => [],
        ];
        if (!$this->count()) {
            $result['meta'] = new EmptyObject();
        } else {
            foreach ($this->data as $meta) {
                $result['meta'] = array_merge($result['meta'], $meta->jsonSerialize());
            }
        }

        return $result;
    }

    protected function getAllowedTypes(): array
    {
        return [
            JsonApiMeta::class,
        ];
    }
}
