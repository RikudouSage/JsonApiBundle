<?php

namespace Rikudou\JsonApiBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonApiStructureResponseListener implements EventSubscriberInterface
{
    #[ArrayShape([KernelEvents::VIEW => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => 'handleResponse',
        ];
    }

    public function handleResponse(ViewEvent $event)
    {
        $result = $event->getControllerResult();
        if ($result instanceof JsonApiObject || $result instanceof JsonApiCollection) {
            $event->setResponse(new JsonApiResponse($result->jsonSerialize()));
        }
    }
}
