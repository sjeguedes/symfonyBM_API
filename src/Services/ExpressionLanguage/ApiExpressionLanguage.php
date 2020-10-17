<?php

declare(strict_types=1);

namespace App\Services\ExpressionLanguage;

use JMS\Serializer\Expression\ExpressionEvaluator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

/**
 * Class ApiExpressionLanguage.
 *
 * Manage custom expression language ecosystem inside API.
 */
class ApiExpressionLanguage extends BaseExpressionLanguage
{
    /**
     * @var ExpressionEvaluator
     */
    private $apiExpressionEvaluator;

    /**
     * @var ExpressionFunctionProviderInterface
     */
    private $apiExpressionFunctionsProvider;

    /**
     * ApiExpressionLanguage constructor.
     *
     * @param ContainerInterface          $container
     * @param CacheItemPoolInterface|null $cache
     * @param array                       $providers
     */
    public function __construct(ContainerInterface $container, CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $this->setApiExpressionFunctionsProvider($container);
        $this->setApiExpressionEvaluator($this, $this->apiExpressionFunctionsProvider->getVariables());
        // Prepend API provider to be able to override it
        array_unshift($providers, $this->apiExpressionFunctionsProvider);
        parent::__construct($cache, $providers);
    }

    /**
     * Set an expression functions provider instance to use with JMS Serializer.
     *
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function setApiExpressionFunctionsProvider(ContainerInterface $container): self
    {
        $this->apiExpressionFunctionsProvider = new ApiExpressionFunctionsProvider($container);

        return $this;
    }

    /**
     * Set a JMS expression evaluator instance.
     *
     * @param ExpressionLanguage $expressionLanguage
     * @param array              $contextVariables
     *
     * @return $this
     */
    public function setApiExpressionEvaluator(ExpressionLanguage $expressionLanguage, $contextVariables = []): self
    {
        $this->apiExpressionEvaluator = new ExpressionEvaluator($expressionLanguage, $contextVariables);

        return $this;
    }

    /**
     * Get an expression functions provider instance to use with a JMS Serializer instance.
     *
     * @return ExpressionFunctionProviderInterface
     */
    public function getApiExpressionFunctionsProvider(): ExpressionFunctionProviderInterface
    {
        return $this->apiExpressionFunctionsProvider;
    }

    /**
     * Get a JMS expression evaluator instance.
     *
     * @return ExpressionEvaluator
     */
    public function getApiExpressionEvaluator(): ExpressionEvaluator
    {
        return $this->apiExpressionEvaluator;
    }
}