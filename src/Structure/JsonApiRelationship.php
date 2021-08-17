<?php

namespace Rikudou\JsonApiBundle\Structure;

use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiLinksCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiMetaCollection;

final class JsonApiRelationship implements JsonSerializable
{
    private string $name;

    private JsonApiLinksCollection $links;

    private JsonApiMetaCollection $meta;

    /**
     * @var JsonApiRelationshipData|JsonApiRelationshipData[]|null
     */
    private JsonApiRelationshipData|array|null $data;

    private bool $hasData = false;

    /**
     * @param array<mixed> $json
     */
    public function __construct(string $name, array $json = [])
    {
        $this->name = $name;
        $this->parse($json);
    }

    /**
     * @phpstan-return array<string, array<string, array<mixed>|null>>
     */
    #[Pure]
    public function jsonSerialize(): array
    {
        if ($this->data instanceof JsonApiRelationshipData) {
            $data = $this->data->jsonSerialize();
        } elseif ($this->data !== null) {
            $data = [];
            foreach ($this->data as $datum) {
                $data[] = $datum->jsonSerialize();
            }
        } else {
            $data = null;
        }

        return [
            $this->name => [
                'data' => $data,
            ],
        ];
    }

    /**
     * @return JsonApiRelationshipData|JsonApiRelationshipData[]|null
     */
    public function getData(): array|JsonApiRelationshipData|null
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasData(): bool
    {
        return $this->hasData;
    }

    /**
     * @param array<mixed> $json
     */
    private function parse(array $json): void
    {
        $this->links = new JsonApiLinksCollection();
        $this->meta = new JsonApiMetaCollection();
        $this->data = null;

        if (isset($json['links'])) {
            foreach ($json['links'] as $linkName => $link) {
                $this->links[] = new JsonApiLink($linkName, $link);
            }
        }
        if (isset($json['meta'])) {
            foreach ($json['meta'] as $key => $value) {
                $this->meta[] = new JsonApiMeta($key, $value);
            }
        }
        if (isset($json['data'])) {
            $this->hasData = true;
            if (isset($json['data']['type'])) {
                $this->data = new JsonApiRelationshipData($json['data']['type'], $json['data']['id'] ?? null);
            } else {
                $this->data = [];
                foreach ($json['data'] as $data) {
                    $this->data[] = new JsonApiRelationshipData($data['type'], $data['id'] ?? null);
                }
            }
        }
    }
}
