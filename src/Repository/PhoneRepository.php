<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Phone;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PhoneRepository
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
     * Find one associated Phone entity depending on a particular partner uuid string parameter.
     *
     * @param string $partnerUuid
     * @param string $entityUuid
     *
     * @return Phone|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByPartner(string $partnerUuid, string $entityUuid): ?object
    {
        return $this->createQueryBuilder('pho')
            ->leftJoin('pho.offers', 'off','WITH', 'pho.uuid = off.phone')
            ->leftJoin('off.partner', 'par', 'WITH', 'off.partner = par.uuid')
            ->andWhere('pho.uuid = ?2')
            ->setParameter(1, $partnerUuid)
            ->setParameter(2, $entityUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a set of Phone entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string     $partnerUuid
     * @param array|null $paginationData
     *
     * @return array|Phone[]
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): array
    {
        $queryBuilder =$this->createQueryBuilder('pho')
            ->leftJoin('pho.offers', 'off','WITH', 'pho.uuid = off.phone')
            ->leftJoin('off.partner', 'par', 'WITH', 'off.partner = par.uuid')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with a pagination
        if (!\is_null($paginationData)) {
            return $this->findList($queryBuilder, $paginationData);
        }
        // Get all results
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
