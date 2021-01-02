<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class ClientRepository
 *
 * Manage Client database queries.
 */
class ClientRepository extends AbstractAPIRepository
{
    /**
     * ClientRepository constructor.
     *
     * @param ManagerRegistry        $registry
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(ManagerRegistry $registry, TagAwareCacheInterface $doctrineCache)
    {
        parent::__construct($registry, $doctrineCache, Client::class);
    }

    /**
     * Find a set of Client entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): ?\IteratorAggregate
    {
        $queryBuilder = $this->createQueryBuilder('cli')
            ->leftJoin('cli.partner', 'par', 'WITH', 'par.uuid = cli.partner')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with or without pagination
        return $this->findList($partnerUuid, $queryBuilder, $paginationData);
    }
}
