<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PhoneRepository
 */
class PhoneRepository extends ServiceEntityRepository
{
    /**
     * PhoneRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Phone::class);
    }
}
