<?php

namespace Rikudou\JsonApiBundle\Service;

use Symfony\Component\Uid\Uuid;

final class IdConverter
{
    public function __construct(
        private readonly bool $convertUuidToBinary,
    ) {
    }

    public function convert(int|string|Uuid $id): int|string|Uuid
    {
        if (!$id instanceof Uuid || !$this->convertUuidToBinary) {
            return $id;
        }

        return $id->toBinary();
    }
}
