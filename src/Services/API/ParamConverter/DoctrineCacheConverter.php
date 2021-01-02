<?php

declare(strict_types=1);

namespace App\Services\API\ParamConverter;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\AbstractAPIRepository;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Ramsey\Uuid\Uuid;
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
     * @var array
     */
    private $uuidValues;

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
        // Will store several uuid values to find at least two different entity instances
        $this->uuidValues = [];
    }

    /**
     * Retrieve an instance depending on request and controller method argument name.
     *
     * Please note that "stampede prevention" is managed thanks to CacheInterface::get() method implementation
     * with "$beta" third argument set to 1.0 by default.
     *
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        // Interrupt process if no uuid request attribute is found, or no special case matches!
        if (null === $request->get('uuid') && !$this->setEntityUuidWithSpecialCase($request, $configuration)) {
            return false;
        }
        $entityAttributeName = $configuration->getName();
        $parameters = [
            'cacheKey' => ucfirst($entityAttributeName) .'_' . $request->get('uuid'),
            'cacheTag' => $entityAttributeName . '_tag',
            'class'    => array_search($entityAttributeName, self::NAMES),
            'uuid'     => empty($this->uuidValues) ? $request->get('uuid') : $this->uuidValues[$entityAttributeName]
        ];
        // Get entity from cache or create cache data in case of miss:
        $result = $this->cache->get($parameters['cacheKey'], function (ItemInterface $item) use ($parameters) {
            // Expire cache data automatically after 1 hour or earlier with "stampede prevention"
            $item->expiresAfter(AbstractAPIRepository::DEFAULT_CACHE_TTL);
            // Tag item to ease invalidation later
            $item->tag($parameters['cacheTag']); // "client_tag", "phone_tag", ...
            /** @var ObjectRepository $entityRepository */
            $entityRepository = $this->entityManager->getRepository($parameters['class']);
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $entityRepository->getQueryBuilder();
            $rootAlias = $queryBuilder->getRootAliases()[0];
            // Find data and get entity instance, and then cache result (cache also the query thanks to DQL)
            return $entityRepository->findOneByUuid(
                $queryBuilder,
                $rootAlias,
                Uuid::fromString($parameters['uuid'])
            );
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
     * Set necessary entity uuid without finding it in request attributes.
     *
     * @param Request        $request
     * @param ParamConverter $configuration
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function setEntityUuidWithSpecialCase(Request $request, ParamConverter $configuration): bool
    {
        // Particular case for Partner entity which can be found by email (e.g controller forwarding)!
        if (Partner::class === $configuration->getClass() && null !== $email = $request->get('email')) {
            /** @var PartnerRepository $partnerRepository */
            $partnerRepository = $this->entityManager->getRepository(Partner::class);
            if (\is_null($partner = $partnerRepository->findOneByEmail(urldecode($email)))) {
                return false;
            }
            // Define the expected "uuid" attribute which concerns current entity to find with this custom converter.
            $request->attributes->set('uuid', $partner->getUuid()->toString());
            return true;
        }
        // Particular case when at least two uuid attributes exist (e.g. for resource and sub resource)
        $requestAttributeToFind = lcfirst(substr($configuration->getName(), 0, 1)) . 'Uuid';
        if ($request->attributes->has($requestAttributeToFind)) {
            // Define the expected "uuid" parameter which concerns current entity to find with this custom converter.
            $this->uuidValues[$configuration->getName()] = $request->attributes->get($requestAttributeToFind);
            return true;
        }
        return false;
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