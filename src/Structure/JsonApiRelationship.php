<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiLinksCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiMetaCollection;

final class JsonApiRelationship implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var JsonApiLinksCollection
     */
    private $links;

    /**
     * @var JsonApiMetaCollection
     */
    private $meta;

    /**
     * @var JsonApiRelationshipData|JsonApiRelationshipData[]|null
     */
    private $data;

    /**
     * @var bool
     */
    private $hasData = false;

    public function __construct(string $name, array $json = [])
    {
        $this->name = $name;
        $this->parse($json);
    }

    public function jsonSerialize()
    {
        if ($this->data instanceof JsonApiRelationshipData) {
            $data = $this->data->jsonSerialize();
        } elseif ($this->data !== null) {
            $data = [];
            foreach ($this->data as $datum) {
                $data[] = $datum->jsonSerialize();
            }
        } else {
            $data = [];
        }

        return [
            $this->name => $data,
        ];
    }

    /**
     * @return JsonApiRelationshipData|JsonApiRelationshipData[]|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasData(): bool
    {
        return $this->hasData;
    }

    private function parse(array $json)
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
