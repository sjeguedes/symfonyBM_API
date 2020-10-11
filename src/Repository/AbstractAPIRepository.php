<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\Traits\FilteredQueryHelperTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AbstractAPIRepository.
 */
abstract class AbstractAPIRepository extends ServiceEntityRepository
{
    use FilteredQueryHelperTrait;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        parent::__construct($registry, $entityClass);
        $this->entityManager = $this->getEntityManager();
    }

    /**
     * Define entities aliases for Doctrine query builder.
     */
    const DATABASE_ENTITIES_ALIASES = [
        Client::class => 'cli',
        Partner::class  => 'par',
        Phone::class  => 'pho',
        Offer::class  => 'off',
    ];

    /**
     * Find one associated entity (Client, Phone, ...) depending on a particular partner uuid string parameter.
     *
     * @param string $partnerUuid
     * @param string $entityUuid
     *
     * @return object|null a particular entity which can be a Phone, Client entity instance
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    abstract public function findOneByPartner(string $partnerUuid, string $entityUuid): ?object;

    /**
     * Find all associated entities (client, Phone, ...) depending on a particular partner uuid string parameter
     * with possible paginated results.
     *
     * @param string $partnerUuid
     * @param array  $paginationData
     *
     * @return array
     */
    abstract public function findAllByPartner(string $partnerUuid, array $paginationData = []): array;

    /**
     * Find a set of entities with offset and limit integers parameters with Doctrine paginated results.
     *
     * @param QueryBuilder $queryBuilder,
     * @param int          $page
     * @param int          $limit
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findPaginatedOnes(
        QueryBuilder $queryBuilder,
        int $page,
        int $limit
    ): \IteratorAggregate {
        return $this->filterWithPagination($queryBuilder, $page, $limit);
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

    /**
     * Get one result for a particular query.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return object|null
     */
    public function getQueryResult(QueryBuilder $queryBuilder): ?object
    {
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get results for a particular query.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return array
     */
    public function getQueryResults(QueryBuilder $queryBuilder): array
    {
        return $queryBuilder->getQuery()->getResult();
    }
}
