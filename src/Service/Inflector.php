<?php

namespace Rikudou\JsonApiBundle\Service;

use RuntimeException;
use Symfony\Component\String\Inflector\EnglishInflector;

final class Inflector
{
    public function __construct(private EnglishInflector $inflector)
    {
    }

    public function singularize(string $string): string
    {
        $result = $this->inflector->singularize($string);

        return reset($result) ?: throw new RuntimeException('Failed to singularize string');
    }

    public function pluralize(string $string): string
    {
        $result = $this->inflector->pluralize($string);

        return reset($result) ?: throw new RuntimeException('Failed to pluralize string');
    }
}
