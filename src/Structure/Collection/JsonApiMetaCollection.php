<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiMeta;

/**
 * @extends AbstractCollection<JsonApiMeta>
 */
final class JsonApiMetaCollection extends AbstractCollection implements JsonSerializable
{
    /**
     * @phpstan-return array<string,array<mixed>|EmptyObject>
     */
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

    /**
     * @return string[]
     */
    protected function getAllowedTypes(): array
    {
        return [
            JsonApiMeta::class,
        ];
    }

    protected function getKeyProperty(): ?string
    {
        return 'name';
    }

    protected function getKeys(): iterable
    {
        foreach ($this->data as $item) {
            yield $item->getName();
        }
    }
}
