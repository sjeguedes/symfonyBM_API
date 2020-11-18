<?php

declare(strict_types=1);

namespace App\Services\Hateoas\Event\Subscriber;

use App\Services\Hateoas\Representation\CollectionResourceRepresentation;
use Hateoas\Representation\AbstractSegmentedRepresentation;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

/**
 * Class CollectionRepresentationSubscriber
 *
 * Manage hateoas collection representation serialization.
 */
class CollectionRepresentationSubscriber implements EventSubscriberInterface
{
    /**
     * @var CollectionResourceRepresentation|null
     */
    private $collectionObject;

    /**
     * CollectionRepresentationSubscriber constructor.
     */
    public function __construct()
    {
        $this->collectionObject = null;
    }

    /**
     * Define subscribed events.
     *
     * {inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
                'format' => 'json', // optional format
                'priority' => 0, // optional priority
            ],
            [
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
                'format' => 'json', // optional format
                'priority' => 0, // optional priority
            ]
        ];
    }

    /**
     * Call pre-serialization process.
     *
     * @param PreSerializeEvent $event
     *
     * @return void
     */
    public function onPreSerialize(PreSerializeEvent $event): void
    {
        // Replace paginated representation key "limit" with custom key "per_page"
        if ($event->getObject() instanceof AbstractSegmentedRepresentation) {
            $metadata = $event->getContext()
                ->getMetadataFactory()
                ->getMetadataForClass(AbstractSegmentedRepresentation::class);
            $limitPropertyMetaData = $metadata->propertyMetadata['limit'];
            /** @var ClassMetadata */
            $limitPropertyMetaData->serializedName = 'per_page';
            unset($metadata->propertyMetadata['limit']);
            $metadata->propertyMetadata['per_page'] = $limitPropertyMetaData;
        }
        // Get resource collection representation object before serialization
        if ($event->getObject() instanceof CollectionResourceRepresentation) {
           $this->collectionObject = $event->getObject();
        }
    }

    /**
     * Call post-serialization process.
     *
     * @param ObjectEvent $event
     *
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event): void
    {
        if (\is_null($this->collectionObject)) {
            return;
        }
        // Replace embedded collection representation key "items" with custom items label
        if ($event->getObject() instanceof AbstractSegmentedRepresentation) {
            // Replace "_embedded" property serialized data representation to change "items" label
            $event->getVisitor()->visitProperty(
                new StaticPropertyMetadata ('', '_embedded', null),
                [$this->collectionObject->getItemsLabel() => $this->collectionObject->getResources()]
            );
        }
    }
}