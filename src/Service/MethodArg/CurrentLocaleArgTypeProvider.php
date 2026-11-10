<?php

namespace Rikudou\JsonApiBundle\Service\MethodArg;

use Rikudou\JsonApiBundle\Enum\ApiPropertyArgType;
use Symfony\Component\HttpFoundation\RequestStack;

final class CurrentLocaleArgTypeProvider implements MethodArgTypeProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function supports(string $type): bool
    {
        return $type === ApiPropertyArgType::CurrentLocale->value;
    }

    public function resolve(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale();
    }
}
