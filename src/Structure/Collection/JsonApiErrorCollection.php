<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiError;

/**
 * @extends() AbstractCollection<JsonApiError>
 */
final class JsonApiErrorCollection extends AbstractCollection implements JsonSerializable
{
    #[Pure]
    #[ArrayShape(['errors' => 'array'])]
    public function jsonSerialize(): array
    {
        $result = [
            'errors' => [],
        ];

        foreach ($this->data as $error) {
            $result['errors'][] = $error->jsonSerialize();
        }

        return $result;
    }

    protected function getAllowedTypes(): array
    {
        return [
            JsonApiError::class,
        ];
    }
}
