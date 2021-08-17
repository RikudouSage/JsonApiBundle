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
    private ?string $type;

    private string|int|null $id;

    private JsonApiAttributesCollection $attributes;

    private JsonApiLinksCollection $links;

    private JsonApiMetaCollection $meta;

    private JsonApiRelationshipCollection $relationships;

    private JsonApiIncludesCollection $includes;

    /**
     * @param array<mixed> $json
     */
    public function __construct(array $json = [])
    {
        $this->parse($json);
    }

    public function __toString()
    {
        return (string) json_encode($this->jsonSerialize());
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getAttributes(): JsonApiAttributesCollection
    {
        return $this->attributes;
    }

    public function getLinks(): JsonApiLinksCollection
    {
        return $this->links;
    }

    public function getMeta(): JsonApiMetaCollection
    {
        return $this->meta;
    }

    public function getRelationships(): JsonApiRelationshipCollection
    {
        return $this->relationships;
    }

    public function getIncludes(): JsonApiIncludesCollection
    {
        return $this->includes;
    }

    /**
     * @param float|array<mixed>|bool|int|string|null $value
     */
    public function addAttribute(string $name, float|array|bool|int|string|null $value): self
    {
        $this->attributes[] = new JsonApiAttribute($name, $value);

        return $this;
    }

    public function addLink(string $name, string $link): self
    {
        $this->links[] = new JsonApiLink($name, $link);

        return $this;
    }

    /**
     * @param float|array<mixed>|bool|int|string|null $value
     */
    public function addMeta(string $name, float|array|bool|int|string|null $value): self
    {
        $this->meta[] = new JsonApiMeta($name, $value);

        return $this;
    }

    /**
     * @param array<mixed> $data
     */
    public function addRelationship(string $name, array $data): self
    {
        $this->relationships[] = new JsonApiRelationship($name, [
            'data' => $data,
        ]);

        return $this;
    }

    public function addInclude(JsonApiObject $include): self
    {
        $this->includes[] = $include;

        return $this;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function jsonSerialize(): array
    {
        /** @var array<string, array> $json */
        $json = array_merge(
            [
                'id' => $this->id,
                'type' => $this->type,
            ],
            $this->attributes->jsonSerialize(),
            $this->relationships->jsonSerialize(),
            $this->meta->jsonSerialize(),
            $this->links->jsonSerialize(),
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

    /**
     * @param array<mixed> $json
     */
    private function parse(array $json): void
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
