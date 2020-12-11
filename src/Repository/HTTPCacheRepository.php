<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HTTPCache;
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
}
