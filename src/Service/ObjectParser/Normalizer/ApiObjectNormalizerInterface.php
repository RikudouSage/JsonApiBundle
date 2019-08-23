<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer;

interface ApiObjectNormalizerInterface
{
    /**
     * Returns the value in a normalized format that can be handled by api
     *
     * @param object $object
     *
     * @return int|string|bool|float|array
     */
    public function getNormalizedValue($object);

    /**
     * Returns the classes/interfaces that can be handled by the normalizer
     *
     * @return string[]
     */
    public function handles(): array;
}
