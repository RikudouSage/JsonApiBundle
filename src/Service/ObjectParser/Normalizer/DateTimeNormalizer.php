<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer;

use function assert;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

final class DateTimeNormalizer implements DisablableApiObjectNormalizerInterface
{
    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var string
     */
    private $format;

    public function __construct(bool $enabled, string $format)
    {
        $this->enabled = $enabled;
        $this->format = $format;
    }

    /**
     * Returns the value in a normalized format that can be handled by api
     *
     * @param object $object
     *
     * @return int|string|bool|float|array
     */
    public function getNormalizedValue($object)
    {
        assert($object instanceof DateTimeInterface);

        return $object->format($this->format);
    }

    /**
     * Returns the classes/interfaces that can be handled by the normalizer
     *
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

    /**
     * Whether the normalizer is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
