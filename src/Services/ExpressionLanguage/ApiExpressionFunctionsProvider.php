<?php

declare(strict_types=1);

namespace App\Services\ExpressionLanguage;

use App\Entity\Partner;
use App\Entity\Phone;
use Doctrine\Common\Util\ClassUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Class ApiExpressionFunctionsProvider
 *
 * Provide a custom set of expression functions to be used with expression language engine.
 *
 * @see https://jmsyst.com/bundles/JMSSerializerBundle/master/configuration#expression-language
 */
class ApiExpressionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    const EXCLUDED_URI_PATTERNS = [
        Phone::class   => '\/phones\/[\w-]{36}$', // Phone properties to exclude for phone details
        Partner::class => '\/clients\/[\w-]{36}$', // Partner properties to exclude for client details
    ];

    /**
     * @var ContainerInterface
     */
    private $serviceLocator;

    /**
     * @var array
     */
    private $variables;

    /**
     * ApiExpressionFunctionsProvider constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->serviceLocator = $container;
        $this->variables = [
            'serviceLocator' => $container,
            'requestStack'   => $container->has('request_stack') ? $container->get('request_stack') : null
        ];
    }

    /**
     * Define custom expression functions.
     *
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
    {
        return [
            // Exclude a property from serialization depending on request and resource which has this property
            new ExpressionFunction('isRequestAllowed', function (string $requestURI, object $resource) {
                // Get real class name from Doctrine proxy instance
                $resourceClassName = ClassUtils::getClass($resource);
                $isFound = false;
                foreach (self::EXCLUDED_URI_PATTERNS as $key => $pattern) {
                    $isResourceMatched = $key === $resourceClassName;
                    if ($isResourceMatched && $isFound = sprintf('%1$s matches "/%2$s/"', $requestURI, $pattern)) {
                        break;
                    }
                }
                // Return the opposite of result.
                return !$isFound;
            }, function (array $variables, string $requestURI, object $resource) {
                // Get real class name from Doctrine proxy instance
                $resourceClassName = ClassUtils::getClass($resource);
                $isFound = false;
                foreach (self::EXCLUDED_URI_PATTERNS as $key => $pattern) {
                    $isResourceMatched = $key === $resourceClassName;
                    if ($isResourceMatched && $isFound = preg_match('/'.$pattern.'/', $requestURI)) {
                        break;
                    }
                }
                // Return the opposite of result.
                return !$isFound;
            }),
            // Get an existing instance from the service locator
            new ExpressionFunction('service', function (string $serviceId) {
                // Return service
                return sprintf(
                    'call_user_func_array([%1$s, \'get\'], [%2$s])',
                    $this->variables['serviceLocator'],
                    $serviceId
                );
            }, function (array $variables, string $serviceId) {
                // Return service
                return $variables['serviceLocator']->get($serviceId);
            }),
        ];
    }

    /**
     * Get variables to evaluate in expression functions.
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}