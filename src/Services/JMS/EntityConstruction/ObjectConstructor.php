<?php

declare(strict_types=1);

namespace App\Services\JMS\EntityConstruction;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

/**
 * Class ObjectConstructor
 *
 * Manage JMS deserialization with entity constructor.
 *
 * @see https://stackoverflow.com/questions/31948118/jms-serializer-why-are-new-objects-not-being-instantiated-through-constructor
 */
final class ObjectConstructor implements ObjectConstructorInterface
{
    /**
     * Construct a new entity instance using its constructor when data are un-serialized.
     *
     * {@inheritdoc}
     */
    public function construct(
        DeserializationVisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    ): ?object {
        $className = $metadata->name;
        // Use entity constructor
        return new $className();
    }
}