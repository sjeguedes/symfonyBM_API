<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class OfferRepository
 */
class OfferRepository extends ServiceEntityRepository
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
}
