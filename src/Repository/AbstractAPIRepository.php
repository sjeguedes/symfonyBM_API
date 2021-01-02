<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Services\API\Cache\DoctrineCacheResultListIterator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class AbstractAPIRepository
 *
 * Centralize common queries handling.
 */
abstract class AbstractAPIRepository extends ServiceEntityRepository
{
    /**
     * Define Doctrine cache list results key prefix.
     */
    const CACHE_KEY_LIST_PREFIX = 'list_';

    /**
     * Define Doctrine cache list results tag suffix.
     */
    const CACHE_TAG_LIST_SUFFIX = '_list_tag';

    /**
     * Define entities aliases for Doctrine query builder.
     */
    const DATABASE_ENTITIES_ALIASES = [
        Client::class  => 'cli',
        Partner::class => 'par',
        Phone::class   => 'pho',
        Offer::class   => 'off',
    ];

    /**
     * Define "time to live" cache duration.
     */
    const DEFAULT_CACHE_TTL = 3600; // 1 hour

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TagAwareCacheInterface
     */
    protected $cache;

    /**
     * AbstractAPIRepository constructor.
     *
     * @param ManagerRegistry        $registry
     * @param TagAwareCacheInterface $doctrineCache
     * @param string                 $entityClassName
     */
    public function __construct(
        ManagerRegistry $registry,
        TagAwareCacheInterface $doctrineCache,
        string $entityClassName
    ) {
        parent::__construct($registry, $entityClassName);
        $this->entityManager = $this->getEntityManager();
        $this->cache = $doctrineCache;
    }

    /**
     * Find all associated entities (Client, Phone, ...) depending on a particular partner uuid string parameter
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|Paginator|null
     */
    abstract public function findListByPartner(string $partnerUuid, ?array $paginationData): ?\IteratorAggregate;

    /**
     * Find a set of entities with offset and limit integers parameters with Doctrine paginated results
     * or all results.
     *
     * Please note that this query is used for hateoas collection representation.
     *
     * @param string       $partnerUuid
     * @param QueryBuilder $queryBuilder,
     * @param array|null   $paginationData
     * @param bool         $isFullListAllowed
     *
     * @return \IteratorAggregate|Paginator
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findList(
        string $partnerUuid,
        QueryBuilder $queryBuilder,
        ?array $paginationData,
        bool $isFullListAllowed = false
    ): \IteratorAggregate {
        $page = $paginationData['page'];
        $limit = $paginationData['per_page'];
        $firstResult = !\is_null($page) ? ($page - 1) * $limit : null;
        $maxResults = $limit ?? null;
        // Prepare data for cache and query
        if (\is_null($firstResult)) {
            $listIdentifier = self::CACHE_KEY_LIST_PREFIX . 'without_pagination_';
        } elseif (\is_null($maxResults)) {
            $listIdentifier = self::CACHE_KEY_LIST_PREFIX . $page . '_without_limit_';
        } else {
            $listIdentifier = self::CACHE_KEY_LIST_PREFIX . $page . '_' . $limit . '_';
        }
        // Get particular or complete list indicator
        $listIdentifier = (!$isFullListAllowed ? '_restricted_' : '_full_') . $listIdentifier;
        $cacheKeySuffix = $listIdentifier . 'for_consumer_' . $partnerUuid;
        // e.g. "client_full_list_without_pagination_for_consumer_0847df13-c88f-4c4a-8943-876f5ab402c5",
        // e.g. "client_restricted_list_1_10_for_consumer_0847df13-c88f-4c4a-8943-876f5ab402c5", etc...
        preg_match('/\\\(\w+)$/', $queryBuilder->getRootEntities()[0], $matches);
        $cacheKey = $matches[1] . $cacheKeySuffix;
        // e.g. "client_list_tag", "partner_list_tag", etc...
        $cacheTag = lcfirst($matches[1]) . self::CACHE_TAG_LIST_SUFFIX;
        $parameters = [
            'cacheKey'     => $cacheKey,
            'cacheTag'     => $cacheTag,
            'filter'       => ['firstResult' => $firstResult, 'maxResults' => $maxResults],
            'queryBuilder' => $queryBuilder
        ];
        // Store data from database in cache and return a custom iterator as result
        return $data = $this->manageCacheForData($cacheKey, $matches[1], $parameters);
    }

    /**
     * Find a single entity instance by uuid.
     *
     * @param QueryBuilder  $queryBuilder
     * @param string        $rootAlias
     * @param UuidInterface $uuid
     *
     * @return object|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByUuid(
        QueryBuilder $queryBuilder,
        string $rootAlias,
        UuidInterface $uuid
    ): ?object {
        return $queryBuilder
            ->where($rootAlias . '.uuid = ?1')
            ->getQuery()
            ->setParameter(1, $uuid->toString())
            ->getOneOrNullResult();
    }

    /**
     * Manage results list cache item if necessary or data depending on a query.
     *
     * @param string $cacheKey
     * @param string $listType
     * @param array  $parameters
     *
     * @return \IteratorAggregate
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function manageCacheForData(string $cacheKey, string $listType, array $parameters): \IteratorAggregate
    {
        // Get data from cache or create cache data in case of miss:
        $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($parameters) {
            // Expire cache data automatically after 1 hour or earlier with "stampede prevention"
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            // Tag item to ease invalidation later
            $item->tag($parameters['cacheTag']);
            /** @var Query $query */
            $query = $parameters['queryBuilder']
                // Define offset value
                ->setFirstResult($parameters['filter']['firstResult'])
                // Pass limit value
                ->setMaxResults($parameters['filter']['maxResults'])
                ->getQuery();
            return [
                'itemsTotalCount' => (new Paginator($query))->count(),
                'offset'          => $query->getFirstResult(),
                'limit'           => $query->getMaxResults(),
                'selectedItems'   => $query->getResult()
            ];
        });
        // Failure state: no result was found!
        if (0 === count($data['selectedItems'])) {
            $this->cache->delete($cacheKey);
            throw new BadRequestHttpException(sprintf('No %s list result found', lcfirst($listType)));
        }
        // Return a custom iterator in order to serialize list later for representation
        return new DoctrineCacheResultListIterator(
            $data['itemsTotalCount'],
            $data['offset'],
            $data['limit'],
            $data['selectedItems']
        );
    }

    /**
     * Get corresponding Doctrine query builder.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder(self::DATABASE_ENTITIES_ALIASES[$this->getEntityName()]);
    }
}
