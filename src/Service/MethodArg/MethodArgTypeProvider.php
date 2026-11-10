<?php

namespace Rikudou\JsonApiBundle\Service\MethodArg;

interface MethodArgTypeProvider
{
    public function supports(string $type): bool;

    public function resolve(): mixed;
}
