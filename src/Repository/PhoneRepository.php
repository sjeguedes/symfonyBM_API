<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class PhoneRepository
 *
 * Manage Phone database queries.
 */
class PhoneRepository extends AbstractAPIRepository
{
    /**
     * PhoneRepository constructor.
     *
     * @param ManagerRegistry        $registry
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(ManagerRegistry $registry, TagAwareCacheInterface $doctrineCache)
    {
        parent::__construct($registry, $doctrineCache, Phone::class);
    }

    /**
     * Find a set of Phone entities results depending on a particular partner uuid parameter,
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
        $queryBuilder =$this->createQueryBuilder('pho')
            ->leftJoin('pho.offers', 'off','WITH', 'pho.uuid = off.phone')
            ->leftJoin('off.partner', 'par', 'WITH', 'off.partner = par.uuid')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with or without pagination
        return $this->findList($partnerUuid, $queryBuilder, $paginationData);
    }
}