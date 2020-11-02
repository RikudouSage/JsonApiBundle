<?php

namespace Rikudou\JsonApiBundle\Controller;

use Rikudou\JsonApiBundle\ApiEvents;
use Rikudou\JsonApiBundle\Events\RouterPreroutingEvent;
use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Exception\ResourceNotFoundException;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Service\ApiResourceLocator;
use Rikudou\JsonApiBundle\Service\Inflector;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ApiRouter extends AbstractController
{
    private const GET_COLLECTION_METHOD = 'getCollection';

    private const GET_ITEM_METHOD = 'getItem';

    private const ADD_ITEM_METHOD = 'addItem';

    private const DELETE_ITEM_METHOD = 'deleteItem';

    private const UPDATE_ITEM_METHOD = 'updateItem';

    /**
     * @var Inflector
     */
    private $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
    }

    /**
     * @param string                   $resourceName
     * @param int|string|null          $id
     * @param EventDispatcherInterface $eventDispatcher
     * @param ApiResourceLocator       $resourceLocator
     * @param Request                  $request
     *
     * @return Response|JsonApiObject
     */
    public function router(
        string $resourceName,
        $id,
        EventDispatcherInterface $eventDispatcher,
        ApiResourceLocator $resourceLocator,
        Request $request
    ) {
        try {
            $controller = $resourceLocator->findControllerForResource($resourceName);
            $controller->setResourceName($resourceName);
        } catch (ResourceNotFoundException $e) {
            throw new JsonApiErrorException('Resource not found', Response::HTTP_NOT_FOUND, $e);
        }

        $event = new RouterPreroutingEvent($resourceName, $id, $controller);

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $eventDispatcher->dispatch($event, ApiEvents::PREROUTING);

        if ($event->getResourceName() !== $resourceName || $event->getId() !== $id) {
            return $this->router(
                $event->getResourceName(),
                $event->getId(),
                $eventDispatcher,
                $resourceLocator,
                $request
            );
        }

        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                if ($id === null) {
                    $method = self::GET_COLLECTION_METHOD;
                } else {
                    $method = self::GET_ITEM_METHOD;
                }
                break;
            case Request::METHOD_POST:
                $method = self::ADD_ITEM_METHOD;
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case Request::METHOD_PATCH:
                if ($id !== null) {
                    $method = self::UPDATE_ITEM_METHOD;
                    break;
                }
            /** @noinspection PhpMissingBreakStatementInspection */
            // no break
            case Request::METHOD_DELETE:
                if ($id !== null) {
                    $method = self::DELETE_ITEM_METHOD;
                    break;
                }
                // no break
            default:
                throw new JsonApiErrorException(
                    "Unsupported method '{$request->getMethod()}'",
                    Response::HTTP_METHOD_NOT_ALLOWED
                );
        }

        return $this->forward("{$controller->getServiceName()}::{$method}", [
            'id' => $id,
        ], $request->query->all());
    }

    public function home(ApiResourceLocator $resourceLocator, UrlGeneratorInterface $urlGenerator)
    {
        $links = [
            'self' => $urlGenerator->generate('rikudou_json_api.home', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $names = $resourceLocator->getResourceNames(false);
        $namesPlural = $resourceLocator->getResourceNames(true);

        foreach ($names as $index => $name) {
            $links[$name] = $urlGenerator->generate('rikudou_json_api.router', [
                'resourceName' => $namesPlural[$index],
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new JsonApiResponse([
            'links' => $links,
        ]);
    }
}
