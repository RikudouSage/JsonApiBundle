<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use function get_class;
use function is_object;
use function method_exists;
use Psr\Container\ContainerInterface;
use Rikudou\JsonApiBundle\Exception\InvalidApiObjectException;
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
    public function isValid($object): bool
    {
        return is_object($object) && method_exists($object, 'getId');
    }

    /**
     * @param object $object
     */
    public function throwOnInvalidObject($object): void
    {
        if (!$this->isValid($object)) {
            throw new InvalidApiObjectException();
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
            'doctrine',
        ];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
