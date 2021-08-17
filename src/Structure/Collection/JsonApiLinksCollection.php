<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiLink;

/**
 * @extends AbstractCollection<JsonApiLink>
 */
final class JsonApiLinksCollection extends AbstractCollection implements JsonSerializable
{
    /**
     * @phpstan-return array<string,array<mixed>|EmptyObject>
     */
    #[Pure]
    #[ArrayShape(['links' => "array|\Rikudou\JsonApiBundle\Structure\EmptyObject"])]
    public function jsonSerialize(): array
    {
        $result = [
            'links' => [],
        ];
        if (!$this->count()) {
            $result['links'] = new EmptyObject();
        } else {
            foreach ($this->data as $link) {
                $result['links'] = array_merge($result['links'], $link->jsonSerialize());
            }
        }

        return $result;
    }

    /**
     * @return string[]
     */
    protected function getAllowedTypes(): array
    {
        return [
            JsonApiLink::class,
        ];
    }
}
