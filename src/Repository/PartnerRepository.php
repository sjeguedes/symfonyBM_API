<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class PartnerRepository
 */
class PartnerRepository extends ServiceEntityRepository implements UserLoaderInterface
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

    /**
     * Find one partner by its username.
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
