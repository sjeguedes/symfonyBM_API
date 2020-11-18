<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

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
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Phone::class);
    }

    /**
     * Find a set of Phone entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return \IteratorAggregate|Paginator
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): \IteratorAggregate
    {
        $queryBuilder =$this->createQueryBuilder('pho')
            ->leftJoin('pho.offers', 'off','WITH', 'pho.uuid = off.phone')
            ->leftJoin('off.partner', 'par', 'WITH', 'off.partner = par.uuid')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with a pagination
        if (!\is_null($paginationData)) {
            return $this->filterWithPagination($queryBuilder, $paginationData['page'], $paginationData['per_page']);
        }
        // Get all results as \IteratorAggregate Doctrine Paginator implementation
        return new Paginator($queryBuilder->setFirstResult(0)->getQuery());
    }
}
