<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer;

interface DisablableApiObjectNormalizerInterface extends ApiObjectNormalizerInterface
{
    /**
     * Whether the normalizer is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;
}
