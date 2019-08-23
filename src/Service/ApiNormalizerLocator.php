<?php

namespace Rikudou\JsonApiBundle\Service;

use function class_exists;
use function get_class;
use function is_a;
use function is_object;
use function is_string;
use Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer\ApiObjectNormalizerInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\Normalizer\DisablableApiObjectNormalizerInterface;

final class ApiNormalizerLocator
{
    /**
     * @var ApiObjectNormalizerInterface[]
     */
    private $normalizers = [];

    public function addNormalizer(ApiObjectNormalizerInterface $normalizer)
    {
        foreach ($normalizer->handles() as $class) {
            $this->normalizers[$class] = $normalizer;
        }
    }

    /**
     * @param $classOrObject
     *
     * @return ApiObjectNormalizerInterface|null
     */
    public function getNormalizerForClass($classOrObject): ?ApiObjectNormalizerInterface
    {
        if (is_string($classOrObject) && class_exists($classOrObject)) {
            $className = $classOrObject;
        } elseif (is_object($classOrObject)) {
            $className = get_class($classOrObject);
        } else {
            return null;
        }

        $normalizer = null;

        if (isset($this->normalizers[$className])) {
            $normalizer = $this->normalizers[$className];
        } else {
            foreach ($this->normalizers as $class => $classNormalizer) {
                if (is_a($className, $class, true)) {
                    $normalizer = $this->normalizers[$class];
                    break;
                }
            }
        }

        if ($normalizer === null) {
            return null;
        }

        if ($normalizer instanceof DisablableApiObjectNormalizerInterface && !$normalizer->isEnabled()) {
            return null;
        }

        return $normalizer;
    }
}
