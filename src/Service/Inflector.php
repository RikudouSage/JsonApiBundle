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

    /**
     * @param string $string
     *
     * @return string|string[]
     */
    public function singularize(string $string)
    {
        return $this->inflector !== null
            ? $this->inflector->singularize($string)
            : SymfonyOldInflector::singularize($string);
    }

    /**
     * @param string $string
     *
     * @return string|string[]
     */
    public function pluralize(string $string)
    {
        return $this->inflector !== null
            ? $this->inflector->pluralize($string)
            : SymfonyOldInflector::pluralize($string);
    }
}
