<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    /**
     * Find a set of phones entities depending on offset and limit integers parameters.
     *
     * @param int $page
     * @param int $limit
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findPaginatedOnes(int $page, int $limit): \IteratorAggregate
    {
        $query = $this->createQueryBuilder('p')
            ->getQuery()
            // Define offset value
            ->setFirstResult(($page - 1) * $limit)
            // Pass limit value
            ->setMaxResults($limit);
        // Paginator is returned since it is an IteratorAggregate implementation.
        // An array can also be returned with "iterator_to_array(new Paginator($query));".
        return new Paginator($query);
    }
}
