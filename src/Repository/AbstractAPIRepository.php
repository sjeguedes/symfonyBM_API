<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AbstractAPIRepository
 *
 * Centralize common queries handling.
 */
abstract class AbstractAPIRepository extends ServiceEntityRepository
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * AbstractAPIRepository constructor.
     *
     * @param ManagerRegistry $registry
     * @param string          $entityClassName
     */
    public function __construct(ManagerRegistry $registry, string $entityClassName)
    {
        parent::__construct($registry, $entityClassName);
        $this->entityManager = $this->getEntityManager();
    }

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
     * Find a set of entities paginated results depending on a particular Doctrine query builder,
     * offset and limit integers parameters
     * using Doctrine paginator.
     *
     * @param QueryBuilder $queryBuilder
     * @param int          $page
     * @param int          $limit
     *
     * @return array
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     */
    public function filterWithPagination(QueryBuilder $queryBuilder, int $page, int $limit): array
    {
        $query = $queryBuilder->getQuery()
            // Define offset value
            ->setFirstResult(($page - 1) * $limit)
            // Pass limit value
            ->setMaxResults($limit);
        // Paginator can be returned as an IteratorAggregate implementation.
        // An array is returned with "iterator_to_array(new Paginator($query))" for APi needs.
        return iterator_to_array(new Paginator($query));
    }

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
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return array
     */
    abstract public function findListByPartner(string $partnerUuid, ?array $paginationData): array;

    /**
     * Find a set of entities with offset and limit integers parameters with Doctrine paginated results
     * or all results.
     *
     * @param QueryBuilder $queryBuilder,
     * @param array|null   $paginationData
     *
     * @return array
     */
    public function findList(QueryBuilder $queryBuilder, ?array $paginationData): array {
        if (!\is_null($paginationData)) {
            return $this->filterWithPagination($queryBuilder, $paginationData['page'], $paginationData['per_page']);
        }
        return $this->findAll();
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
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getQueryResult(QueryBuilder $queryBuilder): ?object
    {
        return $queryBuilder->getQuery()->getOneOrNullResult();
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
