<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PartnerRepository.
 */
class PartnerRepository extends ServiceEntityRepository
{
    /**
     * PartnerRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }
}
