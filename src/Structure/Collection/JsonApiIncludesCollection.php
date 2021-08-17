<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;

/**
 * @extends AbstractCollection<JsonApiObject>
 */
final class JsonApiIncludesCollection extends AbstractCollection implements JsonSerializable
{
    /**
     * @phpstan-return array<string,array<mixed>>
     */
    #[ArrayShape(['included' => 'array'])]
    public function jsonSerialize(): array
    {
        return [
            'included' => array_map(function (JsonApiObject $item) {
                return $item->jsonSerialize()['data'];
            }, $this->data),
        ];
    }

    #[Pure]
    public function contains(JsonApiObject $include): bool
    {
        foreach ($this->data as $datum) {
            if ($datum->getType() === $include->getType() && $datum->getId() === $include->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function getAllowedTypes(): array
    {
        return [
            JsonApiObject::class,
        ];
    }
}
