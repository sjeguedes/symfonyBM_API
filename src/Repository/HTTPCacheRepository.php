<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HTTPCache;
use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class HTTPCacheRepository
 *
 * Manage HTTPCache database queries.
 */
class HTTPCacheRepository extends ServiceEntityRepository
{
    /**
     * HTTPCacheRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HTTPCache::class);
    }

    /**
     *  Find a single HTTPCache entity instance by associated partner and particular request URI.
     *
     * @param Partner $partner
     * @param string  $requestURI
     *
     * @return HTTPCache|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByPartnerAndRequestURI(Partner $partner, string $requestURI): ?HTTPCache
    {
        return $this->createQueryBuilder('hca')
            ->where('hca.partner = ?1 AND hca.requestURI = ?2')
            ->getQuery()
            ->setParameters([1 => $partner, 2 => $requestURI])
            ->getOneOrNullResult();
    }
}
