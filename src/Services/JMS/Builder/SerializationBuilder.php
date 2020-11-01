<?php

declare(strict_types=1);

namespace App\Services\JMS\Builder;

use App\Services\JMS\ExpressionLanguage\ExpressionFunctionsProvider;
use App\Services\JMS\EntityConstruction\ObjectConstructor;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\Expression\ExpressionEvaluator;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class SerializationBuilder
 *
 * Build a JMS serializer with necessary instances.
 */
class SerializationBuilder implements SerializationBuilderInterface
{
    /**
     * @var SerializerBuilder
     */
    private $serializerBuilder;

    /**
     * SerializationBuilder constructor.
     */
    public function __construct()
    {
        $this->serializerBuilder = $this->getSerializerBuilder();
    }

    /**
     * {@inheritdoc}
     *
     * return SerializerInterface
     */
    public function build(): object
    {
        return $this->getSerializer();
    }

    /**
     * Get a JMS Deserialization Object constructor instance.
     *
     * @return ObjectConstructorInterface
     */
    public function getDeserializationObjectConstructor(): ObjectConstructorInterface
    {
        return new ObjectConstructor();
    }

    /**
     * Get a JMS SerializationContext instance.
     *
     * @return SerializationContext
     */
    public function getSerializationContext(): SerializationContext
    {
        return SerializationContext::create();
    }

    /**
     * Get a JMS Serializer instance.
     *
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializerBuilder->build();
    }

    /**
     * Get a JMS SerializerBuilder instance.
     *
     * @return SerializerBuilder
     */
    public function getSerializerBuilder(): SerializerBuilder
    {
        return SerializerBuilder::create();
    }

    /**
     * Initialize a JMS deserialization object constructor instance.
     *
     * @return SerializationBuilder
     */
    public function initDeserializationObjectConstructor(): self
    {
        $this->serializerBuilder->setObjectConstructor($this->getDeserializationObjectConstructor());

        return $this;
    }

    /**
     * Initialize a JMS expression language evaluator instance.
     *
     * @param ExpressionLanguage $expressionLanguage
     *
     * @return SerializationBuilder
     */
    public function initExpressionLanguageEvaluator(ExpressionLanguage $expressionLanguage): self
    {
        /** @var ExpressionFunctionsProvider $expressionProvider */
        $expressionProvider = $expressionLanguage->getExpressionFunctionsProvider();
        $contextVariables = $expressionProvider->getVariables();
        $expressionEvaluator = new ExpressionEvaluator($expressionLanguage, $contextVariables);
        $this->serializerBuilder->setExpressionEvaluator($expressionEvaluator);

        return $this;
    }
}