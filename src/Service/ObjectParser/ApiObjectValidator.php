<?php

namespace Rikudou\JsonApiBundle\Service\ObjectParser;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
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

    public function isObjectValid(mixed $object): bool
    {
        return is_object($object) && method_exists($object, 'getId');
    }

    /**
     * @param array<mixed> $data
     */
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

    public function throwOnInvalidObject(mixed $object): void
    {
        if (!$this->isObjectValid($object)) {
            throw new InvalidApiObjectException();
        }
    }

    /**
     * @param array<mixed> $data
     */
    public function throwOnInvalidArray(array $data): void
    {
        if (!$this->isArrayValid($data)) {
            throw new InvalidJsonApiArrayException();
        }
    }

    /**
     * @return class-string
     */
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
