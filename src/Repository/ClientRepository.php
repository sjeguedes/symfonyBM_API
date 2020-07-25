<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ClientRepository
 */
class ClientRepository extends ServiceEntityRepository
{
    /**
     * ClientRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Find all clients entities depending on a particular partner uuid string parameter.
     *
     * @param string $uuid
     *
     * @return array
     */
    public function findAllByPartner(string $uuid): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('p')
            ->leftJoin('c.partner', 'p', 'WITH', 'p.uuid = c.partner')
            ->where('p.uuid = ?1')
            ->setParameter(1, $uuid)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a set of clients entities depending on partner uuid, offset and limit integers parameters.
     *
     * @param string $uuid
     * @param int    $page
     * @param int    $limit
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findPaginatedOnesByPartner(string $uuid, int $page, int $limit): \IteratorAggregate
    {
        $query = $this->createQueryBuilder('c')
            ->addSelect('p')
            ->leftJoin('c.partner', 'p', 'WITH', 'p.uuid = c.partner')
            ->where('p.uuid = ?1')
            ->setParameter(1, $uuid)
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
