<?php

declare(strict_types=1);

namespace App\Services\API\ParamConverter;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\AbstractAPIRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class DoctrineCacheConverter
 *
 * Manage a custom converter to retrieve an entity instance and cache this data.
 *
 * @see https://symfony.com/doc/current/components/cache.html
 * @see https://en.wikipedia.org/wiki/Cache_stampede
 */
class DoctrineCacheConverter implements ParamConverterInterface
{
    /**
     * Define corresponding entity to find depending on controller method argument entity name.
     */
    const NAMES = [
        Client::class  => 'client',
        Offer::class   => 'offer',
        Partner::class => 'partner',
        Phone::class   => 'phone'
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TagAwareAdapterInterface
     */
    private $cache;

    /**
     * DoctrineCacheConverter constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(EntityManagerInterface $entityManager, TagAwareCacheInterface $doctrineCache)
    {
        $this->entityManager = $entityManager;
        $this->cache = $doctrineCache;
    }

    /**
     * Retrieve an instance depending on request and controller method argument name.
     *
     * Please note that "stampede prevention" is managed thanks to CacheInterface::get() method implementation
     * with "$beta" third argument set to 1.0 by default.
     *
     * {@inheritdoc}
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        // Interrupt process if no uuid is requested (e.g controller forwarding)!
        if (null === $request->get('uuid')) {
            return false;
        }
        $entityAttributeName = $configuration->getName();
        $parameters = [
            'cacheKey' => ucfirst($entityAttributeName) .'_' . $request->get('uuid'),
            'cacheTag' => $entityAttributeName . '_tag',
            'class'    => $request->get('entityType'),
            'uuid'     => $request->get('uuid')
        ];
        // Get entity from cache or create cache data in case of miss:
        $result = $this->cache->get($parameters['cacheKey'], function (ItemInterface $item) use ($parameters) {
            // Expire cache data automatically after 1 hour or earlier with "stampede prevention"
            $item->expiresAfter(AbstractAPIRepository::DEFAULT_CACHE_TTL);
            // Tag item to ease invalidation later
            $item->tag($parameters['cacheTag']); // "client_tag", "phone_tag", ...
            /** @var ObjectRepository $repository */
            $repository = $this->entityManager->getRepository($parameters['class']);
            // Find data and get entity instance, and then cache result
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $repository->getQueryBuilder();
            $rootAlias = $queryBuilder->getRootAliases()[0];
            return $queryBuilder
                ->andWhere($rootAlias . '.uuid = ?1')
                ->getQuery()
                ->setParameter(1, $parameters['uuid'])
                ->getOneOrNullResult();
        });
        // Failure state: no result was found!
        if (\is_null($result)) {
            $this->cache->delete($parameters['cacheKey']);
            throw new BadRequestHttpException(
                sprintf('No %s result found for uuid %s', $entityAttributeName, $parameters['uuid'])
            );
        }
        // Set requested attribute $configuration->getName() with corresponding hydrated Entity instance
        $request->attributes->set($entityAttributeName, $result);
        // Return success state: instance was retrieved!
        return true;
    }

    /**
     * Check if this custom converter must be called depending on configuration.
     *
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        // Check correct entity argument name
        return \in_array($configuration->getName(), self::NAMES);
    }
}