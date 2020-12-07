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
    const DEFAULT_CACHE_TTL = 3600;

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
     * @return \IteratorAggregate|Paginator
     */
    abstract public function findListByPartner(string $partnerUuid, ?array $paginationData): \IteratorAggregate;

    /**
     * Find a set of entities with offset and limit integers parameters with Doctrine paginated results
     * or all results.
     *
     * Please note that this query is used for hateoas collection representation.
     *
     * @param string        $partnerUuid
     * @param QueryBuilder  $queryBuilder,
     * @param array|null    $paginationData
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
        ?array $paginationData
    ): \IteratorAggregate {
        $page = $paginationData['page'];
        $limit = $paginationData['per_page'];
        $firstResult = !\is_null($page) ? ($page - 1) * $limit : null;
        $maxResults = $limit ?? null;
        // Prepare data for cache and query
        preg_match('/\\\(\w+)$/', $queryBuilder->getRootEntities()[0], $matches);
        $listIdentifier = (!\is_null($limit) ? '_list_' . $page . '_' . $limit : '_full_list');
        $cacheKeySuffix = $listIdentifier . "_for_partner[{$partnerUuid}]";
        // Will produce "client_list_1_10_for_partner[0847df13-c88f-4c4a-8943-876f5ab402c5]", "phone_full_list", etc...
        $cacheKey = $matches[1] . $cacheKeySuffix;
        // Will produce "client_tag", "partner_tag", etc...
        $cacheTag = lcfirst($matches[1]) . '_list_tag';
        $parameters = [
            'cacheKey'     => $cacheKey,
            'cacheTag'     => $cacheTag,
            'filter'       => ['firstResult' => $firstResult, 'maxResults' => $maxResults],
            'queryBuilder' => $queryBuilder
        ];
        $data = $this->manageCacheForData($cacheKey, $matches[1], function (ItemInterface $item) use ($parameters) {
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
        // Return custom iterator in order to serialize list later for representation
        return new DoctrineCacheResultListIterator(
            $data['itemsTotalCount'],
            $data['offset'],
            $data['limit'],
            $data['selectedItems']
        );
    }

    /**
     * Manage cache item if necessary depending on a result value.
     *
     * @param string   $cacheKey
     * @param string   $listType
     * @param callable $callable
     *
     * @return array
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function manageCacheForData(string $cacheKey, string $listType, callable $callable): array
    {
        // Get data from cache or create cache data in case of miss:
        $data = $this->cache->get($cacheKey, $callable);
        // Failure state: no result was found!
        if (0 === count($data['selectedItems'])) {
            $this->cache->delete($cacheKey);
            throw new BadRequestHttpException(sprintf('No %s list result found', lcfirst($listType)));
        }
        // Return data if results exist.
        return $data;
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
