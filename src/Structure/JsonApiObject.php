<?php

namespace Rikudou\JsonApiBundle\Structure;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiAttributesCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiIncludesCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiLinksCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiMetaCollection;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiRelationshipCollection;

final class JsonApiObject implements JsonSerializable
{
    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|int|null
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

    /**
     * @var JsonApiIncludesCollection
     */
    private $includes;

    public function __construct(array $json = [])
    {
        $this->parse($json);
    }

    public function __toString()
    {
        return (string) json_encode($this->jsonSerialize());
    }

    /**
     * @return string
     */
    public function getType(): ?string
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
     * @return int|string|null
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
     * @return JsonApiIncludesCollection
     */
    public function getIncludes(): JsonApiIncludesCollection
    {
        return $this->includes;
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

    /**
     * @param JsonApiObject $include
     *
     * @return $this
     */
    public function addInclude(JsonApiObject $include)
    {
        $this->includes[] = $include;

        return $this;
    }

    public function jsonSerialize()
    {
        $json = array_merge(
            [
                'id' => $this->id,
                'type' => $this->type,
            ],
            $this->attributes->jsonSerialize(),
            $this->relationships->jsonSerialize(),
            $this->meta->jsonSerialize(),
            $this->links->jsonSerialize()
        );

        if (!count($json['meta'])) {
            unset($json['meta']);
        }
        if (!count($json['links'])) {
            unset($json['links']);
        }
        if (!count($json['relationships'])) {
            unset($json['relationships']);
        }

        $result = ['data' => $json];
        $includes = $this->includes->jsonSerialize();
        if (count($includes)) {
            $result = array_merge($result, $includes);
        }

        return $result;
    }

    private function parse(array $json)
    {
        $this->attributes = new JsonApiAttributesCollection();
        $this->links = new JsonApiLinksCollection();
        $this->meta = new JsonApiMetaCollection();
        $this->relationships = new JsonApiRelationshipCollection();
        $this->includes = new JsonApiIncludesCollection();

        $this->type = $json['type'] ?? null;
        $this->id = $json['id'] ?? null;

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
        if (isset($json['included'])) {
            foreach ($json['included'] as $include) {
                $this->includes[] = new JsonApiObject($include);
            }
        }
    }
}
