<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer;

interface ApiObjectNormalizerInterface
{
    /**
     * Returns the value in a normalized format that can be handled by api
     */
    public function getNormalizedValue(object $object): float|int|bool|array|string;

    /**
     * Returns the classes/interfaces that can be handled by the normalizer
     *
     * @return string[]
     */
    public function handles(): array;
}
