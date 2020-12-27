<?php

declare(strict_types=1);

namespace App\Services\API\Event\Subscriber;

use App\Entity\Client;
use App\Entity\HTTPCache;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\AbstractAPIRepository;
use App\Repository\HTTPCacheRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Cache\Adapter\DoctrineAdapter;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Class DoctrineSubscriber
 *
 * Manage Doctrine ORM operations callbacks.
 *
 * Please note that cache invalidation with tag(s) is made for collection list results.
 *
 * @see https://symfony.com/doc/current/doctrine/events.html#doctrine-lifecycle-listener
 * @see https://symfony.com/doc/current/components/cache/cache_invalidation.html
 */
class DoctrineSubscriber implements EventSubscriber
{
    /**
     * Define entity types involved in this subscriber.
     */
    const ALLOWED_ENTITIES = [
        Client::class  => 'Client',
        Offer::class   => 'Offer',
        Partner::class => 'Partner',
        Phone::class   => 'Phone'
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TagAwareCacheInterface
     */
    private $cache;

    /**
     * DoctrineSubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $doctrineCache
    ) {
        $this->entityManager = $entityManager;
        $this->cache = $doctrineCache;
    }

    /**
     * Get involved entity necessary parameters.
     *
     * Please not this is useful to avoid issue with Doctrine proxies.
     *
     * @param LifecycleEventArgs $args
     *
     * @return array|null
     */
    private function getEntityParameters(LifecycleEventArgs $args): ?array
    {
        $entity = $args->getObject();
        $className = \get_class($entity);
        if (!\array_key_exists($className, self::ALLOWED_ENTITIES)) {
            return null;
        }
        return ['entity' => $entity, 'className' => $className];
    }

    /**
     * Return an array of events this subscriber wants to listen to.
     *
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    /**
     * Call callback for "postPersist" event.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if (\is_null($parameters = $this->getEntityParameters($args))) {
            return;
        }
        // Cache for new single result is generated during first GET request to retrieve new entity!
        // Delete only cache result(s) for corresponding collection lists, due to this creation
        $this->invalidateDoctrineCacheListResult($parameters['className']);
    }

    /**
     * Call callback for "postRemove" event.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if (\is_null($parameters = $this->getEntityParameters($args))) {
            return;
        }
        $entity = $parameters['entity'];
        $className = $parameters['className'];
        // Delete particular entity cache result if it exists.
        $this->invalidateDoctrineCacheUniqueResult($entity->getUuid(), $className);
        // Invalidate (delete) HTTPCache instance associated to involved entity,
        // and possibly expire only related collection list(s) by updating HTTPCache instance(s)
        $this->invalidateHTTPCache($entity->getUuid(), $className, true);
        // Delete cache result(s) for corresponding collection lists
        $this->invalidateDoctrineCacheListResult($className);
    }

    /**
     * Call callback for "postUpdate" event.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (\is_null($parameters = $this->getEntityParameters($args))) {
            return;
        }
        $entity = $parameters['entity'];
        $className = $parameters['className'];
        // Delete particular entity cache result if it exists.
        $this->invalidateDoctrineCacheUniqueResult($entity->getUuid(), $className);
        // Invalidate HTTP cache by updating HTTPCache instance(s) associated to involved entity,
        // and also possibly related collection list(s) HTTPCache instance(s)
        $this->invalidateHTTPCache($entity->getUuid(), $className);
        // Delete cache result(s) for corresponding collection lists
        $this->invalidateDoctrineCacheListResult($className);
    }

    /**
     * Invalidate/expire corresponding HTTP cache.
     * 
     * IMPORTANT:
     * Please not that authenticated partner is not taken into account to simply process:
     * HTTP cache changes are only based on involved entity and all corresponding collection lists.
     *
     * Removal: Invalidate corresponding entity result by deleting HTTPCache instance
     *          and by modifying collection list HTTPCache instance(s) update date ("Last-Modified" header)
     *          and token ("Etag" header)
     * Update: Expire only corresponding result(s) by modifying collection list HTTPCache instance(s)
     *         update date ("Last-Modified" header) and token ("Etag" header)
     *
     * @param UuidInterface $entityUuid
     * @param string        $className
     * @param bool          $isRemoval
     *
     * @return void
     *
     * @throws \Exception
     */
    private function invalidateHTTPCache(UuidInterface $entityUuid, string $className, bool $isRemoval = false): void
    {
        /** @var HTTPCacheRepository $httpCacheRepository */
        $httpCacheRepository = $this->entityManager->getRepository(HTTPCache::class);
        $results = $httpCacheRepository->findBy(['classShortName' =>  self::ALLOWED_ENTITIES[$className]]);
        if (empty($results)) {
            return;
        }
        /** @var HTTPCache $httpCache */
        foreach ($results as $httpCache) {
            switch ($httpCache->getType()) {
                // Update or remove particular involved entity corresponding HTTP Cache instance
                case HTTPCache::RESOURCE_TYPES['unique']:
                    // Get possible entry based on request URI which corresponds to involved entity
                    $requestURI = $httpCache->getRequestUri();
                    preg_match('/\/([\w-]{36})(\?[\w-&=]+)?$/', $requestURI, $matches, PREG_UNMATCHED_AS_NULL);
                    // This result matches involved entity!
                    if (!\is_null($matches[1]) && $entityUuid->toString() === $matches[1]) {
                        // Modify these properties to change "Last-Modified" and "Etag" headers values later in controller
                        $httpCache->setUpdateDate(new \DateTimeImmutable());
                        $httpCache->setEtagToken($httpCache->getUpdateDate());
                        // Remove HTTPCache entity if necessary
                        !$isRemoval ?: $this->entityManager->remove($httpCache);

                    }
                    break;
                // Update collection list HTTPCache instance(s)
                case HTTPCache::RESOURCE_TYPES['list']:
                    // Change this to change "Last-Modified" and "Etag" headers values later in controller
                    $httpCache->setUpdateDate(new \DateTimeImmutable());
                    $httpCache->setEtagToken($httpCache->getUpdateDate());
                    break;
                default:
                    throw new \LogicException('Unknown HTTPCache type');
            }
        }
        // Save global changes
        $this->entityManager->flush();
    }

    /**
     * Invalidate cache for involved entity if needed.
     *
     * @param UuidInterface $entityUuid
     * @param string $className
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateDoctrineCacheUniqueResult(UuidInterface $entityUuid, string $className): void
    {
        // Delete particular entity cache result
        $cacheKey = self::ALLOWED_ENTITIES[$className]. '_' . $entityUuid->toString();
        /** @var DoctrineAdapter $cacheAdapter */
        if ($cacheAdapter = $this->cache->hasItem($cacheKey)) {
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * Invalidate cache for entity (one type) list results,
     * after creation, removal or update of a particular instance.
     *
     * @param string $className a FQCN
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateDoctrineCacheListResult(string $className): void
    {
        // Check involved entity class name to apply cache invalidation
        if (!\array_key_exists($className, self::ALLOWED_ENTITIES)) {
            return;
        }
        // Retrieve tag(s) to invalidate (e.g. "client_list_tag")
        $cacheTag = lcfirst(self::ALLOWED_ENTITIES[$className]) . AbstractAPIRepository::CACHE_TAG_LIST_SUFFIX;
        // Invalidate all collection list cache results with tag(s) depending on involved entity
        $this->cache->invalidateTags([$cacheTag]);
    }
}