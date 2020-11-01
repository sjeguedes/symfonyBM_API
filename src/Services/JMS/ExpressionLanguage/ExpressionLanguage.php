<?php

declare(strict_types=1);

namespace App\Services\JMS\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Class ExpressionLanguage
 *
 * Manage custom expression language ecosystem inside API.
 *
 * Please note this class also implements service subscription with a service locator.
 *
 * @see https://symfony.com/doc/current/service_container/service_subscribers_locators.html
 */
class ExpressionLanguage extends BaseExpressionLanguage implements ServiceSubscriberInterface
{
    /**
     * @var ExpressionFunctionProviderInterface
     */
    private $expressionFunctionsProvider;

    /**
     * @var ContainerInterface
     */
    private $serviceLocator;

    /**
     * ExpressionLanguage constructor.
     *
     * @param ContainerInterface          $container
     * @param CacheItemPoolInterface|null $cache
     * @param array                       $providers
     */
    public function __construct(ContainerInterface $container, CacheItemPoolInterface $cache = null, array $providers = [])
    {
        $this->serviceLocator = $container;
        $this->initExpressionFunctionsProvider($container);
        // Prepend API provider to be able to override it
        array_unshift($providers, $this->expressionFunctionsProvider);
        parent::__construct($cache, $providers);
    }

    /**
     * Set an expression functions provider instance to use with JMS Serializer.
     *
     * @param ContainerInterface $container a particular service locator to avoid complete DIC injection
     *
     * @return $this
     */
    public function initExpressionFunctionsProvider(ContainerInterface $container): self
    {
        $this->expressionFunctionsProvider = new ExpressionFunctionsProvider($container);

        return $this;
    }

    /**
     * Get an expression functions provider instance to use with a JMS Serializer instance.
     *
     * @return ExpressionFunctionProviderInterface
     */
    public function getExpressionFunctionsProvider(): ExpressionFunctionProviderInterface
    {
        return $this->expressionFunctionsProvider;
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