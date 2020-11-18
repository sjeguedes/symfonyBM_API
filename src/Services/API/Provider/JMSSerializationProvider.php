<?php

declare(strict_types=1);

namespace App\Services\API\Provider;

use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

/**
 * Class SerializationJMSProvider
 *
 * Provide JMS serialization capabilities.
 */
class JMSSerializationProvider
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContextFactoryInterface
     */
    private $serializationContextFactory;

    /**
     * SerializationJMSProvider constructor.
     *
     * @param SerializerInterface                  $serializer
     * @param SerializationContextFactoryInterface $serializationContextFactory
     */
    public function __construct(SerializerInterface $serializer, SerializationContextFactoryInterface $serializationContextFactory)
    {
        $this->serializer = $serializer;
        $this->serializationContextFactory = $serializationContextFactory;
    }

    /**
     * Get a JMS serializer service instance.
     *
     * @return SerializerInterface a serializer instance
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * Get a JMS serialization context service instance.
     *
     * @return SerializationContext a serialization context instance
     */
    public function getSerializationContext(): SerializationContext
    {
        return $this->serializationContextFactory->createSerializationContext();
    }
}