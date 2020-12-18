<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class PartnerRepository
 *
 * Manage Partner database queries.
 */
class PartnerRepository extends AbstractAPIRepository implements UserLoaderInterface
{
    /**
     * PartnerRepository constructor.
     *
     * @param ManagerRegistry        $registry
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(ManagerRegistry $registry, TagAwareCacheInterface $doctrineCache)
    {
        parent::__construct($registry, $doctrineCache, Partner::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findListByPartner(string $partnerUuid, ?array $paginationData): \IteratorAggregate
    {
        // Not necessary for this repository
    }

    /**
     * Find one partner by his username.
     *
     * @param string $username
     *
     * @return Partner|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByUserName(string $username): ?Partner
    {
        return $this->createQueryBuilder('par')
            ->where('par.name = :query OR par.email = :query')
            ->setParameter('query', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Load the user for the given email (which is considered as a username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $email the unique property to retrieve user in database
     *
     * @return UserInterface|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($email): ?UserInterface
    {
        $result =  $this->createQueryBuilder('par')
            ->where('par.email = :query')
            ->orWhere('par.username = :query')
            ->setParameter('query', $email)
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }
}
