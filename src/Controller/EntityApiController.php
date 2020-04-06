<?php

namespace Rikudou\JsonApiBundle\Controller;

use function assert;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use InvalidArgumentException;
use LogicException;
use function min;
use ReflectionException;
use function Rikudou\ArrayMergeRecursive\array_merge_recursive;
use Rikudou\JsonApiBundle\ApiEntityEvents;
use Rikudou\JsonApiBundle\ApiEvents;
use Rikudou\JsonApiBundle\Events\EntityApiResponseCreatedEvent;
use Rikudou\JsonApiBundle\Events\EntityPreCreateEvent;
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
use UnexpectedValueException;

abstract class EntityApiController extends AbstractController implements ApiControllerInterface
{
    /**
     * @var FilteredQueryBuilderInterface
     */
    protected $filteredQueryBuilder;

    /**
     * @var string
     */
    protected $resourceName;

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var bool
     */
    private $paginationEnabled;

    /**
     * @var int
     */
    private $defaultPerPageLimit;

    /**
     * @var ApiObjectParser
     */
    private $objectParser;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    /**
     * @param string $serviceName
     */
    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return JsonApiCollection
     *
     */
    public function getCollection()
    {
        $request = $this->requestStack->getCurrentRequest();
        assert($request !== null);

        $queryParams = $request->query;
        $currentPage = $queryParams->getInt('page', 1);
        $query = $this->getFilteredQueryBuilder(true, false);
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
            $queryParams->all()
        ));
        $response->addLink('first', $this->route(
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => 1])
        ));
        $response->addLink('last', $this->route(
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => $lastPage])
        ));
        $response->addLink(
            'prev',
            $currentPage > 1
                ? $this->route(
                    'rikudou_json_api.router',
                    array_merge_recursive($queryParams->all(), ['page' => min($currentPage - 1, $lastPage)])
                )
                : null
        );
        $response->addLink(
            'next',
            $currentPage + 1 < $lastPage
                ? $this->route(
                    'rikudou_json_api.router',
                    array_merge_recursive($queryParams->all(), ['page' => $currentPage + 1])
                )
                : null
        );
        $response->addMeta('totalItems', (int) $total);
        $response->addMeta('itemsPerPage', $perPage);
        $response->addMeta('currentPage', $currentPage);

        if ($total > 0) {
            $query = $this->getFilteredQueryBuilder();
            if ($perPage > 0) {
                $query
                    ->setFirstResult(($currentPage - 1) * $perPage)
                    ->setMaxResults($perPage);
            }

            $query = $query
                ->getQuery()
                ->getResult();

            foreach ($query as $item) {
                $response->addObject(new JsonApiObject($this->objectParser->getJsonApiArray($item)));
            }
        }

        $event = new EntityApiResponseCreatedEvent(
            $response,
            EntityApiResponseCreatedEvent::TYPE_GET_COLLECTION,
            $this->resourceName,
            $this->getClass()
        );

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);

        $response = $event->getData();
        if (!$response instanceof JsonApiCollection) {
            throw new LogicException('Get collection request must return instance of ' . JsonApiCollection::class);
        }

        return $response;
    }

    /**
     * @param int|string $id
     *
     * @throws ReflectionException
     * @throws AnnotationException
     *
     * @return JsonApiResponse|JsonApiObject
     *
     */
    public function getItem($id)
    {
        try {
            $entity = $this
                ->getFilteredQueryBuilder(false, false)
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new JsonApiErrorException(
                'The resource does not exist',
                Response::HTTP_NOT_FOUND,
                $e
            );
        }

        $response = new JsonApiObject($this->objectParser->getJsonApiArray($entity));

        $event = new EntityApiResponseCreatedEvent(
            $response,
            EntityApiResponseCreatedEvent::TYPE_GET_ITEM,
            $this->resourceName,
            $this->getClass()
        );

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);
        $response = $event->getData();

        if (!$response instanceof JsonApiObject) {
            throw new LogicException('Get item request must return instance of ' . JsonApiObject::class);
        }

        return $response;
    }

    public function addItem()
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

            /** @var ApiResourceInterface $entity */
            $entity = $this->objectParser->parseJsonApiArray([
                'data' => $data,
            ]);

            if (!is_a($entity, $this->getClass(), true)) {
                throw new UnexpectedValueException("The parsed entity tree does not translate to type '{$this->getClass()}'");
            }

            $event = new EntityPreCreateEvent($entity);
            /** @noinspection PhpMethodParametersCountMismatchInspection */
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
                $this->getClass()
            );

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);
            $jsonApiObject = $event->getData();

            return $response->setContent($jsonApiObject);
        } catch (InvalidArgumentException $e) {
            throw new JsonApiErrorException(
                'Could not parse the request data',
                Response::HTTP_BAD_REQUEST,
                $e
            );
        } catch (UnexpectedValueException $e) {
            throw new JsonApiErrorException(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                $e
            );
        } catch (Exception $e) {
            throw new JsonApiErrorException(
                'The server encountered an internal error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    public function deleteItem($id)
    {
        try {
            $entity = $this
                ->getFilteredQueryBuilder(false, false)
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new JsonApiErrorException(
                'The resource does not exist',
                Response::HTTP_NOT_FOUND,
                $e
            );
        }

        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();

            $event = new EntityApiResponseCreatedEvent(
                null,
                EntityApiResponseCreatedEvent::TYPE_DELETE_ITEM,
                $this->resourceName,
                $this->getClass()
            );
            /** @noinspection PhpMethodParametersCountMismatchInspection */
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
                $e
            );
        }
    }

    public function updateItem($id)
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
                    ->setParameter('entityId', $id)
                    ->getQuery()
                    ->getSingleResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                throw new JsonApiErrorException(
                    'The resource does not exist',
                    Response::HTTP_NOT_FOUND,
                    $e
                );
            }

            $post = $this->getPostData();
            if (!$post->has('data')) {
                throw new UnexpectedValueException("The JSON data must contain a root 'data' key");
            }
            $data = $post->get('data');

            if (!isset($data['id']) || !isset($data['type'])) {
                throw new UnexpectedValueException("The JSON data must contain 'id' and 'type'");
            }
            if ($data['id'] !== $id) {
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

                $this->entityManager->persist($updatedEntity);
                $this->entityManager->flush();
                $response = new JsonApiObject($this->objectParser->getJsonApiArray($updatedEntity));
            }

            $event = new EntityApiResponseCreatedEvent(
                $response,
                EntityApiResponseCreatedEvent::TYPE_UPDATE_ITEM,
                $this->resourceName,
                $this->getClass()
            );

            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $this->eventDispatcher->dispatch($event, ApiEntityEvents::PRE_RESPONSE);
            /** @var JsonApiObject $response */
            $response = $event->getData();

            return $response;
        } catch (InvalidArgumentException $e) {
            throw new JsonApiErrorException(
                'Could not parse the request data',
                Response::HTTP_BAD_REQUEST,
                $e
            );
        } catch (UnexpectedValueException $e) {
            throw new JsonApiErrorException(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST,
                $e
            );
        } catch (Exception $e) {
            throw new JsonApiErrorException(
                'The server encountered an internal error',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    //////////// CONTAINER ////////////

    /**
     * @param FilteredQueryBuilderInterface $filteredQueryBuilder
     *
     * @internal
     */
    public function setFilteredQueryBuilder(FilteredQueryBuilderInterface $filteredQueryBuilder)
    {
        $this->filteredQueryBuilder = $filteredQueryBuilder;
    }

    /**
     * @param RequestStack $requestStack
     *
     * @internal
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param bool $paginationEnabled
     *
     * @internal
     */
    public function setPaginationEnabled(bool $paginationEnabled)
    {
        $this->paginationEnabled = $paginationEnabled;
    }

    /**
     * @param int $defaultPerPageLimit
     *
     * @internal
     */
    public function setDefaultPerPageLimit(int $defaultPerPageLimit)
    {
        $this->defaultPerPageLimit = $defaultPerPageLimit;
    }

    /**
     * @param ApiObjectParser $objectParser
     *
     * @internal
     */
    public function setObjectParser(ApiObjectParser $objectParser)
    {
        $this->objectParser = $objectParser;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @internal
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param EntityManagerInterface $entityManager
     *
     * @internal
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @internal
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
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
            $useSort
        );
    }

    protected function route(string $route, array $parameters = [])
    {
        if (!isset($parameters['resourceName'])) {
            $parameters['resourceName'] = $this->resourceName;
        }

        return $this->urlGenerator->generate($route, $parameters);
    }

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
            /** @var array $data */
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
