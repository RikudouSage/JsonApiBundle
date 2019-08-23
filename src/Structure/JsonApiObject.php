<?php

namespace Rikudou\JsonApiBundle\Structure;

use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiAttributesCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiLinksCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiMetaCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiRelationshipCollection;

final class JsonApiObject implements JsonSerializable
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string|int
     */
    private $id;

    /**
     * @var JsonApiAttributesCollection
     */
    private $attributes;

    /**
     * @var JsonApiLinksCollection
     */
    private $links;

    /**
     * @var JsonApiMetaCollection
     */
    private $meta;

    /**
     * @var JsonApiRelationshipCollection
     */
    private $relationships;

    public function __construct(array $json = [])
    {
        $this->parse($json);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return JsonApiObject
     */
    public function setType(string $type): JsonApiObject
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     *
     * @return JsonApiObject
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return JsonApiAttributesCollection
     */
    public function getAttributes(): JsonApiAttributesCollection
    {
        return $this->attributes;
    }

    /**
     * @return JsonApiLinksCollection
     */
    public function getLinks(): JsonApiLinksCollection
    {
        return $this->links;
    }

    /**
     * @return JsonApiMetaCollection
     */
    public function getMeta(): JsonApiMetaCollection
    {
        return $this->meta;
    }

    /**
     * @return JsonApiRelationshipCollection
     */
    public function getRelationships(): JsonApiRelationshipCollection
    {
        return $this->relationships;
    }

    /**
     * @param string                           $name
     * @param int|string|bool|float|array|null $value
     *
     * @return JsonApiObject
     */
    public function addAttribute(string $name, $value)
    {
        $this->attributes[] = new JsonApiAttribute($name, $value);

        return $this;
    }

    public function addLink(string $name, string $link)
    {
        $this->links[] = new JsonApiLink($name, $link);

        return $this;
    }

    /**
     * @param string                           $name
     * @param int|string|float|bool|array|null $value
     *
     * @return JsonApiObject
     */
    public function addMeta(string $name, $value)
    {
        $this->meta[] = new JsonApiMeta($name, $value);

        return $this;
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return JsonApiObject
     */
    public function addRelationship(string $name, array $data)
    {
        $this->relationships[] = new JsonApiRelationship($name, [
            'data' => $data,
        ]);

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'attributes' => $this->attributes->jsonSerialize(),
            'relationships' => $this->relationships->jsonSerialize(),
            'links' => $this->links->jsonSerialize(),
            'meta' => $this->meta->jsonSerialize(),
        ];
    }

    private function parse(array $json)
    {
        $this->attributes = new JsonApiAttributesCollection();
        $this->links =  new JsonApiLinksCollection();
        $this->meta = new JsonApiMetaCollection();
        $this->relationships = new JsonApiRelationshipCollection();

        if (!isset($json['type']) || !isset($json['id'])) {
            return;
        }

        $this->type = $json['type'];
        $this->id = $json['id'];

        if (isset($json['attributes'])) {
            foreach ($json['attributes'] as $attribute => $value) {
                $this->attributes[] = new JsonApiAttribute($attribute, $value);
            }
        }
        if (isset($json['links'])) {
            foreach ($json['links'] as $linkName => $link) {
                $this->links[] = new JsonApiLink($linkName, $link);
            }
        }
        if (isset($json['meta'])) {
            foreach ($json['meta'] as $name => $value) {
                $this->meta[] = new JsonApiMeta($name, $value);
            }
        }
        if (isset($json['relationships'])) {
            foreach ($json['relationships'] as $name => $relationship) {
                $this->relationships[] = new JsonApiRelationship($name, $relationship);
            }
        }
    }
}
