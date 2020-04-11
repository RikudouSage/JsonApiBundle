<?php

namespace Rikudou\JsonApiBundle\Structure\Collection;

use function array_merge;
use JsonSerializable;
use Rikudou\JsonApiBundle\Structure\JsonApiLink;
use Rikudou\JsonApiBundle\Structure\JsonApiMeta;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;

final class JsonApiCollection implements JsonSerializable
{
    /**
     * @var JsonApiObject[]
     */
    private $data = [];

    /**
     * @var JsonApiMetaCollection
     */
    private $meta;

    /**
     * @var JsonApiLinksCollection
     */
    private $links;

    /**
     * @var JsonApiIncludesCollection
     */
    private $includes;

    public function __construct(array $data = [])
    {
        $this->parse($data);
    }

    public function __toString()
    {
        return (string) json_encode($this->jsonSerialize());
    }

    /**
     * @return JsonApiObject[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return JsonApiMetaCollection
     */
    public function getMeta(): JsonApiMetaCollection
    {
        return $this->meta;
    }

    /**
     * @return JsonApiLinksCollection
     */
    public function getLinks(): JsonApiLinksCollection
    {
        return $this->links;
    }

    public function addObject(JsonApiObject $object)
    {
        $this->data[] = $object;

        return $this;
    }

    public function addInclude(JsonApiObject $include)
    {
        $this->includes[] = $include;

        return $this;
    }

    public function addLink(string $name, ?string $link)
    {
        $this->links[] = new JsonApiLink($name, $link);

        return $this;
    }

    /**
     * @param string                           $name
     * @param int|string|float|bool|array|null $value
     *
     * @return JsonApiCollection
     */
    public function addMeta(string $name, $value)
    {
        $this->meta[] = new JsonApiMeta($name, $value);

        return $this;
    }

    public function jsonSerialize()
    {
        $result = array_merge(
            $this->meta->jsonSerialize() ?: [],
            $this->links->jsonSerialize() ?: [],
            ['data' => []]
        );

        foreach ($this->data as $apiObject) {
            $result['data'][] = $apiObject->jsonSerialize()['data'];
        }

        return $result;
    }

    private function parse(array $json)
    {
        $this->meta = new JsonApiMetaCollection();
        $this->links = new JsonApiLinksCollection();
        $this->includes = new JsonApiIncludesCollection();

        if (isset($json['meta'])) {
            foreach ($json['meta'] as $key => $value) {
                $this->meta[] = new JsonApiMeta($key, $value);
            }
        }

        if (isset($json['links'])) {
            foreach ($json['links'] as $name => $link) {
                $this->links[] = new JsonApiLink($name, $link);
            }
        }

        if (isset($json['data'])) {
            foreach ($json['data'] as $object) {
                $this->data[] = new JsonApiObject($object);
            }
        }

        if (isset($json['included'])) {
            foreach ($json['included'] as $include) {
                $this->includes[] = new JsonApiObject($include);
            }
        }
    }
}
