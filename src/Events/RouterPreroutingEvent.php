<?php

namespace Rikudou\JsonApiBundle\Events;

use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class RouterPreroutingEvent extends Event
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var int|string|null
     */
    private $id;

    /**
     * @var ApiControllerInterface
     */
    private $controller;

    /**
     * RouterPreroutingEvent constructor.
     *
     * @param string                 $resourceName
     * @param int|string|null        $id
     * @param ApiControllerInterface $controller
     */
    public function __construct(string $resourceName, $id, ApiControllerInterface $controller)
    {
        $this->resourceName = $resourceName;
        $this->id = $id;
        $this->controller = $controller;
    }

    /**
     * @return ApiControllerInterface
     */
    public function getController(): ApiControllerInterface
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    /**
     * @param string $resourceName
     */
    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    /**
     * @return int|string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string|null $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }
}
