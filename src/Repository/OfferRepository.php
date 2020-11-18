<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class OfferRepository
 *
 * Manage Offer database queries.
 */
class OfferRepository extends AbstractAPIRepository
{
    /**
     * OfferRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    /**
     * Find a set of Offer entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): \IteratorAggregate
    {
        $queryBuilder = $this->createQueryBuilder('off')
            ->leftJoin('off.partner', 'par', 'WITH', 'par.uuid = off.partner')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with a pagination
        if (!\is_null($paginationData)) {
            return $this->filterWithPagination($queryBuilder, $paginationData['page'], $paginationData['per_page']);
        }
        // Get all results as \IteratorAggregate Doctrine Paginator implementation
        return new Paginator($queryBuilder->setFirstResult(0)->getQuery());
    }

    /**
     * Find a set of Offer entities results depending on a particular phone uuid parameter,
     * with possible paginated results.
     *
     * @param string     $phoneUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findListByPhone(string $phoneUuid, ?array $paginationData): \IteratorAggregate
    {
        $queryBuilder = $this->createQueryBuilder('off')
            ->leftJoin('off.phone', 'pho', 'WITH', 'pho.uuid = off.phone')
            ->where('pho.uuid = ?1')
            ->setParameter(1, $phoneUuid);
        // Get results with a pagination
        if (!\is_null($paginationData)) {
            return $this->filterWithPagination($queryBuilder, $paginationData['page'], $paginationData['per_page']);
        }
        // Get all results as \IteratorAggregate Doctrine Paginator implementation
        return new Paginator($queryBuilder->setFirstResult(0)->getQuery());
    }
}
