<?php

namespace Rikudou\JsonApiBundle\Traits;

trait ApiControllerServiceTrait
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var string
     */
    private $serviceName;

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
