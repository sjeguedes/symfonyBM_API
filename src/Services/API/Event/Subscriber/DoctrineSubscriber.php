<?php

declare(strict_types=1);

namespace App\Services\API\Event\Subscriber;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\AbstractAPIRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
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
     * @var TagAwareCacheInterface
     */
    private $cache;

    /**
     * DoctrineSubscriber constructor.
     *
     * @param TagAwareCacheInterface $doctrineCache
     */
    public function __construct(TagAwareCacheInterface $doctrineCache)
    {
        $this->cache = $doctrineCache;
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
        $entity = $args->getObject();
        $className = \get_class($entity);
        // Cache for new single result is generated during first GET request to retrieve new entity!
        // Delete only cache result(s) for corresponding collection lists, due to this creation
        $this->invalidateResultListDoctrineCache($entity, $className);
    }

    /**
     * Call callback for "postRemove" event.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $className = \get_class($entity);
        // Delete particular entity cache result
        $cacheKey = self::ALLOWED_ENTITIES[$className]. '_' . $entity->getUuid()->toString();
        $this->cache->delete($cacheKey);
        // Delete cache result(s) for corresponding collection lists
        $this->invalidateResultListDoctrineCache($entity, $className);
    }

    /**
     * Call callback for "postUpdate" event.
     *
     * @param LifecycleEventArgs $args
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $className = \get_class($entity);
        // Delete particular entity cache result
        $cacheKey = self::ALLOWED_ENTITIES[$className]. '_' . $entity->getUuid()->toString();
        $this->cache->delete($cacheKey);
        // Delete cache result(s) for corresponding collection lists
        $this->invalidateResultListDoctrineCache($entity, $className);
    }

    /**
     * Invalidate cache for entity (one type) list results,
     * after creation, removal or update of a particular instance.
     *
     * @param object $entity
     * @param string $className a FQCN
     *
     * @return void
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function invalidateResultListDoctrineCache(object $entity, string $className): void
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