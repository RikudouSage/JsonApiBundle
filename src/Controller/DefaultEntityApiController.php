<?php

namespace Rikudou\JsonApiBundle\Controller;

final class DefaultEntityApiController extends EntityApiController
{
    private string $className;

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * Returns the name of the class that this controller handles
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->className;
    }
}
