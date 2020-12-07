<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
     * @param ManagerRegistry        $registry
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(ManagerRegistry $registry, TagAwareCacheInterface $doctrineCache)
    {
        parent::__construct($registry, $doctrineCache, Offer::class);
    }

    /**
     * Find a set of Offer entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): \IteratorAggregate
    {
        $queryBuilder = $this->createQueryBuilder('off')
            ->leftJoin('off.partner', 'par', 'WITH', 'par.uuid = off.partner')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with or without pagination
        return $this->findList($partnerUuid, $queryBuilder, $paginationData);
    }

    /**
     * Find a set of Offer entities results depending on a particular phone uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param string     $phoneUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|Paginator
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findListByPhone(string $partnerUuid, string $phoneUuid, ?array $paginationData): \IteratorAggregate
    {
        $queryBuilder = $this->createQueryBuilder('off')
            ->leftJoin('off.phone', 'pho', 'WITH', 'pho.uuid = off.phone')
            ->where('pho.uuid = ?1')
            ->setParameter(1, $phoneUuid);
        // Get results with or without pagination
        return $this->findList($partnerUuid, $queryBuilder, $paginationData);
    }
}
