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
use LogicException;
use function min;
use ReflectionException;
use function Rikudou\ArrayMergeRecursive\array_merge_recursive;
use Rikudou\JsonApiBundle\ApiEvents;
use Rikudou\JsonApiBundle\Events\ApiResponseCreatedEvent;
use Rikudou\JsonApiBundle\Exception\JsonApiErrorException;
use Rikudou\JsonApiBundle\Interfaces\ApiControllerInterface;
use Rikudou\JsonApiBundle\Response\JsonApiResponse;
use Rikudou\JsonApiBundle\Service\Filter\FilteredQueryBuilderInterface;
use Rikudou\JsonApiBundle\Service\ObjectParser\ApiObjectParser;
use Rikudou\JsonApiBundle\Structure\Collection\JsonApiCollection;
use Rikudou\JsonApiBundle\Structure\JsonApiObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    public function __construct(
        FilteredQueryBuilderInterface $filteredQueryBuilder,
        RequestStack $requestStack,
        bool $paginationEnabled,
        int $defaultPerPageLimit,
        ApiObjectParser $objectParser,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager
    ) {
        $this->filteredQueryBuilder = $filteredQueryBuilder;
        $this->requestStack = $requestStack;
        $this->paginationEnabled = $paginationEnabled;
        $this->defaultPerPageLimit = $defaultPerPageLimit;
        $this->objectParser = $objectParser;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
    }

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    /**
     * @param UrlGeneratorInterface|null $urlGenerator
     *
     * @throws AnnotationException
     * @throws ReflectionException
     *
     * @return JsonApiCollection
     */
    public function getCollection(UrlGeneratorInterface $urlGenerator = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        assert($request !== null);
        assert($urlGenerator !== null);

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
            $urlGenerator,
            'rikudou_json_api.router',
            $queryParams->all()
        ));
        $response->addLink('first', $this->route(
            $urlGenerator,
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => 1])
        ));
        $response->addLink('last', $this->route(
            $urlGenerator,
            'rikudou_json_api.router',
            array_merge_recursive($queryParams->all(), ['page' => $lastPage])
        ));
        $response->addLink(
            'prev',
            $currentPage > 1
            ? $this->route(
                $urlGenerator,
                'rikudou_json_api.router',
                array_merge_recursive($queryParams->all(), ['page' => min($currentPage - 1, $lastPage)])
            )
            : null
        );
        $response->addLink(
            'next',
            $currentPage + 1 < $lastPage
            ? $this->route(
                $urlGenerator,
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
                $response->addObject(new JsonApiObject($this->objectParser->getArray($item)));
            }
        }

        $event = new ApiResponseCreatedEvent(
            $response,
            ApiResponseCreatedEvent::TYPE_GET_COLLECTION,
            $this->resourceName,
            $this->getClass()
        );

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->eventDispatcher->dispatch($event, ApiEvents::PRE_RESPONSE);

        $response = $event->getData();
        if (!$response instanceof JsonApiCollection) {
            throw new LogicException('Get collection request must return instance of ' . JsonApiCollection::class);
        }

        return $response;
    }

    /**
     * @param int|string $id
     *
     * @throws AnnotationException
     * @throws ReflectionException
     *
     * @return JsonApiResponse|JsonApiObject
     */
    public function getItem($id)
    {
        try {
            $item = $this
                ->getFilteredQueryBuilder(false, false)
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $item = null;
        }

        if ($item === null) {
            throw new JsonApiErrorException('The resource does not exist', Response::HTTP_NOT_FOUND);
        }

        $response = new JsonApiObject($this->objectParser->getArray($item));

        $event = new ApiResponseCreatedEvent(
            $response,
            ApiResponseCreatedEvent::TYPE_GET_ITEM,
            $this->resourceName,
            $this->getClass()
        );

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->eventDispatcher->dispatch($event, ApiEvents::PRE_RESPONSE);
        $response = $event->getData();

        if (!$response instanceof JsonApiObject) {
            throw new LogicException('Get item request must return instance of ' . JsonApiObject::class);
        }

        return $response;
    }

    public function addItem()
    {
        // TODO: Implement addItem() method.
    }

    public function deleteItem($id): Response
    {
        try {
            $item = $this
                ->getFilteredQueryBuilder(false, false)
                ->setMaxResults(1)
                ->andWhere('entity.id = :itemId')
                ->setParameter('itemId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            $item = null;
        }

        if ($item === null) {
            throw new JsonApiErrorException('The resource does not exist', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($item);
            $this->entityManager->flush();

            return new JsonApiResponse(null, Response::HTTP_NO_CONTENT);
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
        // TODO: Implement updateItem() method.
    }

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

    protected function route(UrlGeneratorInterface $urlGenerator, string $route, array $parameters = [])
    {
        if (!isset($parameters['resourceName'])) {
            $parameters['resourceName'] = $this->resourceName;
        }

        return $urlGenerator->generate($route, $parameters);
    }
}
