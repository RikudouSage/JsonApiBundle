<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer;

use function assert;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

final class DateTimeNormalizer implements DisablableApiObjectNormalizerInterface
{
    public function __construct(private bool $enabled, private string $format)
    {
    }

    public function getNormalizedValue(object $object): string
    {
        assert($object instanceof DateTimeInterface);

        return $object->format($this->format);
    }

    /**
     * @return string[]
     */
    public function handles(): array
    {
        // the matching is faster if it's exact match
        return [
            DateTime::class,
            DateTimeImmutable::class,
            DateTimeInterface::class,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
