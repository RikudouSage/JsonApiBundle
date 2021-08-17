<?php

namespace Rikudou\JsonApiBundle\Listener;

use function is_array;
use JetBrains\PhpStorm\ArrayShape;
use Rikudou\JsonApiBundle\Exception\InvalidJsonApiArrayException;
use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiErrorCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonApiErrorExceptionListener implements EventSubscriberInterface
{
    public function __construct(private bool $enabled)
    {
    }

    #[ArrayShape([KernelEvents::EXCEPTION => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'handleJsonApiException',
        ];
    }

    public function handleJsonApiException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof JsonApiErrorException) {
            if (!$this->enabled) {
                if ($previous = $exception->getPrevious()) {
                    if (
                        $previous instanceof InvalidJsonApiArrayException
                        && $previous->getPrevious() !== null
                    ) {
                        $previous = $previous->getPrevious();
                    }
                    throw $previous;
                }

                return;
            }
            if ($exception->getCode() >= Response::HTTP_OK) {
                $statusCode = $exception->getCode();
            } else {
                $statusCode = Response::HTTP_BAD_REQUEST;
            }

            $result = new JsonApiErrorCollection();

            $data = $exception->getData();
            if (is_array($data)) {
                foreach ($data as $item) {
                    if ($item instanceof JsonApiError) {
                        $result[] = $item;
                    } else {
                        $result[] = new JsonApiError((string) $item, '', $statusCode);
                    }
                }
            } elseif ($data instanceof JsonApiError) {
                $result[] = $data;
            } else {
                $result[] = new JsonApiError((string) $data, '', $statusCode);
            }

            $response = new JsonApiResponse($result->jsonSerialize(), $statusCode);
            $event->setResponse($response);
        }
    }
}
