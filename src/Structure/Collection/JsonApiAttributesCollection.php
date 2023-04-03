<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\EmptyObject;
use Rikudou\JsonApiBundle\Structure\JsonApiAttribute;

/**
 * @extends AbstractCollection<JsonApiAttribute>
 */
final class JsonApiAttributesCollection extends AbstractCollection implements JsonSerializable
{
    /**
     * @phpstan-return array<string, array<mixed>|EmptyObject>
     */
    #[Pure]
    #[ArrayShape(['attributes' => "array|\Rikudou\JsonApiBundle\Structure\EmptyObject"])]
    public function jsonSerialize(): array
    {
        $result = [
            'attributes' => [],
        ];
        if (!$this->count()) {
            $result['attributes'] = new EmptyObject();
        } else {
            foreach ($this->data as $attribute) {
                $result['attributes'] = array_merge($result['attributes'], $attribute->jsonSerialize());
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
            JsonApiAttribute::class,
        ];
    }

    protected function getKeys(): iterable
    {
        foreach ($this->data as $item) {
            yield $item->getName();
        }
    }

    protected function getKeyProperty(): ?string
    {
        return 'name';
    }
}
