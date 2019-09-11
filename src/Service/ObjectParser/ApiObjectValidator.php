<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use function get_class;
use function is_object;
use function method_exists;
use Psr\Container\ContainerInterface;
use Rikudou\JsonApiBundle\Exception\InvalidApiObjectException;
use Rikudou\JsonApiBundle\Exception\InvalidJsonApiArrayException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class ApiObjectValidator implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param object $object
     *
     * @return bool
     */
    public function isObjectValid($object): bool
    {
        return is_object($object) && method_exists($object, 'getId');
    }

    public function isArrayValid(array $data): bool
    {
        if (!isset($data['type']) && !isset($data['data'])) {
            return false;
        }
        if (isset($data['data']) && !isset($data['data']['type'])) {
            return false;
        }

        return true;
    }

    /**
     * @param object $object
     */
    public function throwOnInvalidObject($object): void
    {
        if (!$this->isObjectValid($object)) {
            throw new InvalidApiObjectException();
        }
    }

    public function throwOnInvalidArray(array $data): void
    {
        if (!$this->isArrayValid($data)) {
            throw new InvalidJsonApiArrayException();
        }
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public function getRealClass($object): string
    {
        $class = get_class($object);
        if (!$object instanceof Proxy) {
            return $class;
        }

        return $this->getEntityManager()->getClassMetadata($class)->getName();
    }

    public static function getSubscribedServices()
    {
        return [
            'entity_manager' => EntityManagerInterface::class,
        ];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('entity_manager');
    }
}
