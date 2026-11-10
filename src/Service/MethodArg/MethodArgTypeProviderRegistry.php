<?php

namespace Rikudou\JsonApiBundle\Service\MethodArg;

use Rikudou\JsonApiBundle\Enum\ApiPropertyArgType;

final class MethodArgTypeProviderRegistry
{
    /**
     * @param iterable<MethodArgTypeProvider> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function findByType(string|ApiPropertyArgType $type): ?MethodArgTypeProvider
    {
        if ($type instanceof ApiPropertyArgType) {
            $type = $type->value;
        }

        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }

        return null;
    }
}
