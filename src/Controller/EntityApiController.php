<?php

namespace Rikudou\JsonApiBundle\Controller;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use function assert;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use InvalidArgumentException;
use LogicException;
use function min;
use ReflectionException;
use function Rikudou\ArrayMergeRecursive\array_merge_recursive;
use Rikudou\JsonApiBundle\ApiEntityEvents;
use Rikudou\JsonApiBundle\Events\EntityApiResponseCreatedEvent;
use Rikudou\JsonApiBundle\Events\EntityPreCreateEvent;
use Rikudou\JsonApiBundle\Events\EntityPreDeleteEvent;
use Rikudou\JsonApiBundle\Events\EntityPreParseEvent;
use Rikudou\JsonApiBundle\Events\EntityPreUpdateEvent;
use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Interfaces\ApiResourceInterface;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use UnexpectedValueException;

abstract class EntityApiController extends AbstractController implements ApiControllerInterface
{
    protected FilteredQueryBuilderInterface $filteredQueryBuilder;

    protected string $resourceName;

    protected string $serviceName;

    private RequestStack $requestStack;

    private bool $paginationEnabled;

    private int $defaultPerPageLimit;

    private ApiObjectParser $objectParser;

    private EventDispatcherInterface $eventDispatcher;

    private EntityManagerInterface $entityManager;

    private UrlGeneratorInterface $urlGenerator;

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @throws ReflectionException
     *
     * @return JsonApiCollection
     */
    public function getCollection(): JsonApiCollection
    {
        $request = $this->requestStack->getCurrentRequest();
        assert($request !== null);

        $queryParams = $request->query;
        $currentPage = $queryParams->getInt('page', 1);
        $query = $this->getFilteredQueryBuilder();
        $paginator = new Paginator($query);
        $total = $paginator->count();

        if ($this->paginationEnabled) {
            $perPage = $queryParams->getInt('limit', $this->defaultPerPageLimit);
            $lastPage = max(ceil($total / $perPage), 1);
        } else {
            $perPage = -1;
            $lastPage = 1;
        }

        $response = new JsonApiCollection();
        $response->addLink('self', $this->route(
            'rikudou_json_api.router',
            $queryParams->all(),
        ));
        $response->addLink('first', $this->route(
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => 1]),
        ));
        $response->addLink('last', $this->route(
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => $lastPage]),
        ));
        $response->addLink(
            'prev',
            $currentPage > 1
                ? $this->route(
                    'rikudou_json_api.router',
                    array_merge_recursive($queryParams->all(), ['page' => min($currentPage - 1, $lastPage)]),
                )
                : null,
        );
        $response->addLink(
            'next',
            $currentPage < $lastPage
                ? $this->route(
                    'rikudou_json_api.router',
                    array_merge_recursive($queryParams->all(), ['page' => $currentPage + 1]),
                )
                : null,
        );
        $response->addMeta('totalItems', (int) $total);
        $response->addMeta('itemsPerPage', $perPage);
        $response->addMeta('currentPage', $currentPage);

        if ($total > 0) {
            if ($perPage > 0) {
                $query
                    ->setFirstResult(($currentPage - 1) * $perPage)
                    ->setMaxResults($perPage);
            }

            $result = $query
                ->getQuery()
                ->getResult();

            foreach ($result as $item) {
                $response->addObject(new JsonApiObject($this->objectParser->getJsonApiArray($item)));
            }
        }

        // todo refactor
        if ($includes = $request->query->get('include')) {
            assert(is_string($includes));
            $includes = explode(',', $includes);
            $objects = $response->getData();
            foreach ($objects as $object) {
                $relationships = $object->getRelationships();
                foreach ($includes as $include) {
                    foreach ($relationships as $relationship) {
                        if ($relationship->getName() === $include) {
                            $relationshipData = $relationship->getData();
                            if (!is_iterable($relationshipData)) {
                                $relationshipData = [$relationshipData];
                            }

                            foreach ($relationshipData as $relationshipDatum) {
                                if ($relationshipDatum === null) {
                                    continue;
                                }
                                $includeResponse = json_decode((string) $this->forward('rikudou_api.controller.api_router::router', [
                                    'resourceName' => $relationshipDatum->getType(),
                                    'id' => $relationshipDatum->getId(),
                                ])->getContent(), true)['data'];

                                $includeObject = new JsonApiObject($includeResponse);
                                if ($response->getIncludes()->contains($includeObject)) {
                                    continue;
                                }
                                $response->addInclude($includeObject);
                            }
                        }
                    }
                }
            }
        }

        $event = new EntityApiResponseCreatedEvent(
            $response,
            EntityApiResponseCreatedEvent::TYPE_GET_COLLECTION,
            $this->resourceName,
            $this->getClass(),
        );

        $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);

        $response = $event->getData();
        if (!$response instanceof JsonApiCollection) {
            throw new LogicException('Get collection request must return instance of ' . JsonApiCollection::class);
        }

        return $response;
    }

    /**
     * @throws ReflectionException
     */
    public function getItem(int|string|Uuid $id): JsonApiObject
    {
        try {
            $queryBuilder = $this->getFilteredQueryBuilder(false, false);

            $idToUse = $id;
            if ($idToUse instanceof Uuid) {
                $platform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();
                if ($platform instanceof MySqlPlatform) {
                    $idToUse = $idToUse->toBinary();
                } else {
                    $idToUse = (string) $idToUse;
                }
            }

            $entity = $queryBuilder
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $idToUse)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            if (!$id instanceof Uuid && is_string($id) && Uuid::isValid($id)) {
                return $this->getItem(Uuid::fromString($id));
            }
            throw new JsonApiErrorException(
                'The resource does not exist',
                Response::HTTP_NOT_FOUND,
                $e,
            );
        }

        $response = new JsonApiObject($this->objectParser->getJsonApiArray($entity));

        $request = $this->requestStack->getCurrentRequest();
        assert($request !== null);
        // todo refactor
        if ($includes = $request->query->get('include')) {
            assert(is_string($includes));
            $includes = explode(',', $includes);
            $relationships = $response->getRelationships();
            foreach ($includes as $include) {
                foreach ($relationships as $relationship) {
                    if ($relationship->getName() === $include) {
                        $relationshipData = $relationship->getData();
                        if (!is_iterable($relationshipData)) {
                            $relationshipData = [$relationshipData];
                        }

                        foreach ($relationshipData as $relationshipDatum) {
                            if ($relationshipDatum === null) {
                                continue;
                            }
                            $includeResponse = json_decode((string) $this->forward('rikudou_api.controller.api_router::router', [
                                'resourceName' => $relationshipDatum->getType(),
                                'id' => $relationshipDatum->getId(),
                            ])->getContent(), true)['data'];

                            $includeObject = new JsonApiObject($includeResponse);
                            if ($response->getIncludes()->contains($includeObject)) {
                                continue;
                            }
                            $response->addInclude($includeObject);
                        }
                    }
                }
            }
        }

        $event = new EntityApiResponseCreatedEvent(
            $response,
            EntityApiResponseCreatedEvent::TYPE_GET_ITEM,
            $this->resourceName,
            $this->getClass(),
        );

        $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);
        $response = $event->getData();

        if (!$response instanceof JsonApiObject) {
            throw new LogicException('Get item request must return instance of ' . JsonApiObject::class);
        }

        return $response;
    }

    public function addItem(): JsonApiObject|JsonApiResponse
    {
        try {
            $post = $this->getPostData();

            if (!$post->has('data')) {
                throw new UnexpectedValueException("The JSON data must contain a root 'data' key");
            }
            $data = $post->get('data');

            if (isset($data['id'])) {
                throw new UnexpectedValueException('This api does not support user generated IDs. If you are trying to update resource, use the PATCH method');
            }
            if (!isset($data['type'])) {
                throw new UnexpectedValueException("The JSON data must contain 'type'");
            }
            if ($data['type'] !== $this->resourceName) {
                throw new UnexpectedValueException("The 'type' value does not match the type from URL");
            }

            $event = new EntityPreParseEvent([
                'data' => $data,
            ], $this->getClass());
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_PARSE);
            $data = $event->getData();

            /** @var ApiResourceInterface $entity */
            $entity = $this->objectParser->parseJsonApiArray($data);

            if (!is_a($entity, $this->getClass(), true)) {
                throw new UnexpectedValueException("The parsed entity tree does not translate to type '{$this->getClass()}'");
            }

            $event = new EntityPreCreateEvent($entity);
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_CREATE);
            $entity = $event->getEntity();
            assert(method_exists($entity, 'getId'));

            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $jsonApiObject = new JsonApiObject($this->objectParser->getJsonApiArray($entity));

            $response = new JsonApiResponse($jsonApiObject);
            $response->setStatusCode(Response::HTTP_CREATED);
            $response->headers->set('Location', $this->route('rikudou_json_api.router', [
                'resourceName' => $this->resourceName,
                'id' => $entity->getId(),
            ]));

            $event = new EntityApiResponseCreatedEvent(
                $jsonApiObject,
                EntityApiResponseCreatedEvent::TYPE_CREATE_ITEM,
                $this->resourceName,
                $this->getClass(),
            );

            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);
            $jsonApiObject = $event->getData();

            return $response->setContent($jsonApiObject);
        } catch (InvalidArgumentException $e) {
            throw new JsonApiErrorException(
                'Could not parse the request data',
                Response::HTTP_BAD_REQUEST,
                $e,
            );
        } catch (UnexpectedValueException $e) {
            throw new JsonApiErrorException(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                $e,
            );
        } catch (Exception $e) {
            throw new JsonApiErrorException(
                'The server encountered an internal error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e,
            );
        }
    }

    public function deleteItem(int|string|Uuid $id): JsonApiResponse|Response|JsonApiObject|JsonApiCollection
    {
        try {
            $entity = $this
                ->getFilteredQueryBuilder(false, false)
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $id instanceof Uuid ? $id->toBinary() : $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            if (!$id instanceof Uuid && is_string($id) && Uuid::isValid($id)) {
                return $this->deleteItem(Uuid::fromString($id));
            }
            throw new JsonApiErrorException(
                'The resource does not exist',
                Response::HTTP_NOT_FOUND,
                $e,
            );
        }

        $event = new EntityPreDeleteEvent($entity);
        $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_DELETE);
        $entity = $event->getEntity();

        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $event = new EntityApiResponseCreatedEvent(
                null,
                EntityApiResponseCreatedEvent::TYPE_DELETE_ITEM,
                $this->resourceName,
                $this->getClass(),
            );
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);

            $response = $event->getData();
            if ($response === null) {
                return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
            }

            return $response;
        } catch (ORMException $e) {
            throw new JsonApiErrorException(
                'Internal server error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e,
            );
        }
    }

    public function updateItem(int|string|Uuid $id): JsonApiObject|JsonApiResponse
    {
        if (is_string($id) && is_numeric($id) && strval(intval($id)) === $id) {
            $id = (int) $id;
        }

        try {
            try {
                /** @var ApiResourceInterface $entity */
                $entity = $this
                    ->getFilteredQueryBuilder(false, false)
                    ->setMaxResults(1)
                    ->andWhere('entity.id = :entityId')
                    ->setParameter('entityId', $id instanceof Uuid ? $id->toBinary() : $id)
                    ->getQuery()
                    ->getSingleResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                if (!$id instanceof Uuid && is_string($id) && Uuid::isValid($id)) {
                    return $this->updateItem(Uuid::fromString($id));
                }
                throw new JsonApiErrorException(
                    'The resource does not exist',
                    Response::HTTP_NOT_FOUND,
                    $e,
                );
            }

            $post = $this->getPostData();
            if (!$post->has('data')) {
                throw new UnexpectedValueException("The JSON data must contain a root 'data' key");
            }
            $data = $post->get('data');

            $event = new EntityPreParseEvent([
                'data' => $data,
            ], $this->getClass());
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_PARSE);
            $data = $event->getData()['data'];

            if (!isset($data['id']) || !isset($data['type'])) {
                throw new UnexpectedValueException("The JSON data must contain 'id' and 'type'");
            }
            if ($data['id'] !== ($id instanceof Uuid ? (string) $id : $id)) {
                throw new UnexpectedValueException("The 'id' value does not match the id from URL");
            }
            if ($data['type'] !== $this->resourceName) {
                throw new UnexpectedValueException("The 'type' value does not match the type from URL");
            }

            if (!isset($data['attributes']) && !isset($data['relationships'])) {
                $response = new JsonApiObject($this->objectParser->getJsonApiArray($entity));
            } else {
                /** @var ApiResourceInterface $updatedEntity */
                $updatedEntity = $this->objectParser->parseJsonApiArray([
                    'data' => $data,
                ]);
                if ($updatedEntity->getId() !== $entity->getId()) {
                    throw new LogicException('The updated entity ID is not the same as the original ID');
                }
                if (!is_a($updatedEntity, (string) $this->getClass(), true)) {
                    throw new UnexpectedValueException("The parsed entity tree does not translate to type '{$this->getClass()}'");
                }

                $event = new EntityPreUpdateEvent($updatedEntity);
                $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_UPDATE);
                $updatedEntity = $event->getEntity();

                $this->entityManager->persist($updatedEntity);
                $this->entityManager->flush();
                $response = new JsonApiObject($this->objectParser->getJsonApiArray($updatedEntity));
            }

            $event = new EntityApiResponseCreatedEvent(
                $response,
                EntityApiResponseCreatedEvent::TYPE_UPDATE_ITEM,
                $this->resourceName,
                $this->getClass(),
            );

            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);

            assert($event->getData() instanceof JsonApiObject);

            return $event->getData();
        } catch (InvalidArgumentException $e) {
            throw new JsonApiErrorException(
                'Could not parse the request data',
                Response::HTTP_BAD_REQUEST,
                $e,
            );
        } catch (UnexpectedValueException $e) {
            throw new JsonApiErrorException(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                $e,
            );
        } catch (Exception $e) {
            throw new JsonApiErrorException(
                'The server encountered an internal error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e,
            );
        }
    }

    //////////// CONTAINER ////////////

    /**
     * @internal
     */
    public function setFilteredQueryBuilder(
        FilteredQueryBuilderInterface $filteredQueryBuilder,
    ): void {
        $this->filteredQueryBuilder = $filteredQueryBuilder;
    }

    /**
     * @internal
     */
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @internal
     */
    public function setPaginationEnabled(bool $paginationEnabled): void
    {
        $this->paginationEnabled = $paginationEnabled;
    }

    /**
     * @internal
     */
    public function setDefaultPerPageLimit(int $defaultPerPageLimit): void
    {
        $this->defaultPerPageLimit = $defaultPerPageLimit;
    }

    /**
     * @internal
     */
    public function setObjectParser(ApiObjectParser $objectParser): void
    {
        $this->objectParser = $objectParser;
    }

    /**
     * @internal
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @internal
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @internal
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    //////////////// CONTAINER END ////////////////

    protected function getFilteredQueryBuilder(bool $useFilter = true, bool $useSort = true): QueryBuilder
    {
        $request = $this->requestStack->getCurrentRequest();
        assert($request !== null);

        return $this->filteredQueryBuilder->get(
            $this->getClass(),
            $request->query,
            $useFilter,
            $useSort,
        );
    }

    /**
     * @param array<string,string> $parameters
     */
    protected function route(string $route, array $parameters = []): string
    {
        if (!isset($parameters['resourceName'])) {
            $parameters['resourceName'] = $this->resourceName;
        }

        return $this->urlGenerator->generate($route, $parameters);
    }

    /**
     * @return ParameterBag<mixed>
     */
    protected function getPostData(): ParameterBag
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new RuntimeException('The request cannot be null');
        }
        /** @var string $contentType */
        $contentType = $request->headers->get('Content-Type');
        if (fnmatch('application/*json*', $contentType)) {
            /** @var string $body */
            $body = $request->getContent();
            $data = @json_decode($body, true);
            if (json_last_error()) {
                throw new InvalidArgumentException(json_last_error_msg());
            }

            return new ParameterBag($data);
        } else {
            throw new UnexpectedValueException('The content type must be a valid JSON content type');
        }
    }
}
