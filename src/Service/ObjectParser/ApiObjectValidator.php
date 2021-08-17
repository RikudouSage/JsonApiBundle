<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use function get_class;
use JetBrains\PhpStorm\ArrayShape;
use function method_exists;
use Psr\Container\ContainerInterface;
use Rikudou\JsonApiBundle\Exception\InvalidApiObjectException;
use Rikudou\JsonApiBundle\Exception\InvalidJsonApiArrayException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class ApiObjectValidator implements ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function isObjectValid(object $object): bool
    {
        return method_exists($object, 'getId');
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

    public function throwOnInvalidObject(object $object): void
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

    public function getRealClass(object $object): string
    {
        $class = get_class($object);
        if (!$object instanceof Proxy) {
            return $class;
        }

        return $this->getEntityManager()->getClassMetadata($class)->getName();
    }

    #[ArrayShape(['entity_manager' => 'string'])]
    public static function getSubscribedServices(): array
    {
        return [
            'entity_manager' => EntityManagerInterface::class,
        ];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
