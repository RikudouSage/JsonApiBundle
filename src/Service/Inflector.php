<?php

namespace Rikudou\JsonApiBundle\Service;

use Symfony\Component\Inflector\Inflector as SymfonyOldInflector;
use Symfony\Component\String\Inflector\EnglishInflector;

final class Inflector
{
    private $inflector = null;

    public function __construct()
    {
        if (class_exists(EnglishInflector::class)) {
            $this->inflector = new EnglishInflector();
        }
    }

    public function singularize(string $string): string
    {
        $result = $this->inflector !== null
            ? $this->inflector->singularize($string)
            : SymfonyOldInflector::singularize($string);

        return is_array($result) ? reset($result) : $result;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function pluralize(string $string): string
    {
        $result = $this->inflector !== null
            ? $this->inflector->pluralize($string)
            : SymfonyOldInflector::pluralize($string);

        return is_array($result) ? reset($result) : $result;
    }
}
