<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ClientRepository
 */
class ClientRepository extends AbstractAPIRepository
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
     * Find one associated Client entity depending on a particular partner uuid string parameter.
     *
     * @param string $partnerUuid
     * @param string $entityUuid
     *
     * @return Client|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByPartner(string $partnerUuid, string $entityUuid): ?object
    {
        return $this->createQueryBuilder('cli')
            ->leftJoin('cli.partner', 'par', 'WITH', 'par.uuid = cli.partner')
            ->where('par.uuid = ?1')
            ->andWhere('cli.uuid = ?2')
            ->setParameter(1, $partnerUuid)
            ->setParameter(2, $entityUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find a set of Client entities results depending on a particular partner uuid parameter,
     * with possible paginated results.
     *
     * @param string $partnerUuid
     * @param array  $paginationData
     *
     * @return array|Client[]
     */
    public function findAllByPartner(string $partnerUuid, array $paginationData = []): array
    {
        $queryBuilder = $this->createQueryBuilder('cli')
            ->leftJoin('cli.partner', 'par', 'WITH', 'par.uuid = cli.partner')
            ->where('par.uuid = ?1')
            ->setParameter(1, $partnerUuid);
        // Get results with a pagination
        if (!empty($paginationData)) {
            return iterator_to_array($this->findPaginatedOnes(
                $queryBuilder,
                $paginationData['page'],
                $paginationData['per_page']
            ));
        }
        // Get all results
        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
