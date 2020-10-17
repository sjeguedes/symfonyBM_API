<?php

declare(strict_types=1);

namespace App\Services\ExpressionLanguage;

use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Class RequestExpressionProvider.
 *
 * Provide a custom set of expression functions to be used with expression language engine.
 *
 * @see https://jmsyst.com/bundles/JMSSerializerBundle/master/configuration#expression-language
 */
class ApiExpressionFunctionsProvider implements ExpressionFunctionProviderInterface
{
    const EXCLUDED_URI_PATTERNS = [
        '\/phones\/[\w-]{36}$'
    ];

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
        $this->variables = [
            'container' => $container
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
            new ExpressionFunction('isRequestAllowed', function (string $requestURI) {
                $isFound = false;
                foreach (self::EXCLUDED_URI_PATTERNS as $pattern) {
                    if ($isFound = sprintf('%1$s" matches "/%2$s/"', $requestURI, $pattern)) {
                        break;
                    }
                }
                // Return the opposite of result.
                return !$isFound;
            }, function (array $variables, string $requestURI) {
                $isFound = false;
                foreach (self::EXCLUDED_URI_PATTERNS as $pattern) {
                    if ($isFound = preg_match('/'.$pattern.'/', $requestURI)) {
                        break;
                    }
                }
                // Return the opposite of result.
                return !$isFound;
            }),
            new ExpressionFunction('service', function (string $serviceId) {
                // Return DI container
                return sprintf('$this->get(%s)', $serviceId);
            }, function (array $variables, string $serviceId) {
                // Return DI container
                return $variables['container']->get($serviceId);
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