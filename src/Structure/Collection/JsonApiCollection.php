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

    public function __construct(array $data = [])
    {
        $this->parse($data);
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
            $result['data'][] = $apiObject->jsonSerialize();
        }

        return $result;
    }

    private function parse(array $json)
    {
        $this->meta = new JsonApiMetaCollection();
        $this->links = new JsonApiLinksCollection();

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
    }
}
