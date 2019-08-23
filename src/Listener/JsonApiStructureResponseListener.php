<?php

namespace Rikudou\JsonApiBundle\Listener;

use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonApiStructureResponseListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
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
