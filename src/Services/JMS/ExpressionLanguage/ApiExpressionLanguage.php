<?php

declare(strict_types=1);

namespace App\Services\JMS\ExpressionLanguage;

use JMS\Serializer\Expression\ExpressionEvaluator;
use JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Class ApiExpressionLanguage
 *
 * Manage custom expression language ecosystem inside API.
 *
 * Please note this class also implements service subscription with a service locator.
 *
 * @see https://symfony.com/doc/current/service_container/service_subscribers_locators.html
 */
class ApiExpressionLanguage extends BaseExpressionLanguage implements ServiceSubscriberInterface
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
     * @var ContainerInterface
     */
    private $serviceLocator;

    /**
     * ApiExpressionLanguage constructor.
     *
     * @param ContainerInterface          $container
     * @param CacheItemPoolInterface|null $cache
     * @param array                       $providers
     */
    public function __construct(ContainerInterface $container, CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $this->serviceLocator = $container;
        $this->setApiExpressionFunctionsProvider($container);
        $this->setApiExpressionEvaluator($this, $this->apiExpressionFunctionsProvider->getVariables());
        // Prepend API provider to be able to override it
        array_unshift($providers, $this->apiExpressionFunctionsProvider);
        parent::__construct($cache, $providers);
    }

    /**
     * Set an expression functions provider instance to use with JMS Serializer.
     *
     * @param ContainerInterface $container a particular service locator to avoid complete DIC injection
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
    public function setApiExpressionEvaluator(ExpressionLanguage $expressionLanguage, array $contextVariables = []): self
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
     * @return ExpressionEvaluatorInterface
     */
    public function getApiExpressionEvaluator(): ExpressionEvaluatorInterface
    {
        return $this->apiExpressionEvaluator;
    }

    /**
     * Return an array of service types required by such instances thanks to a service locator,
     * optionally keyed by the service names used internally.
     *
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        // CAUTION: each key must be exactly the service id used!
        return [
            'request_stack' => RequestStack::class,
        ];
    }
}