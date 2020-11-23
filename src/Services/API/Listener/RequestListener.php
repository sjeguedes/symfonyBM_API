<?php

declare(strict_types=1);

namespace App\Services\API\Listener;

use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class RequestListener
 *
 * Manage API requests.
 */
class RequestListener
{
    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * RequestListener constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Call kernel request handler to filter expected query parameters
     * in order to return a JSON response instead.
     *
     * @param RequestEvent $event
     *
     * @return void
     *
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $wrongParameters = [];
        // Filter wrong query parameters to return a custom JSON error response with kernel exception listener
        foreach ($request->query->keys() as $parameterName) {
            if (!\in_array($parameterName, FilterRequestHandler::AVAILABLE_FILTERS)) {
                $wrongParameters[] = $parameterName;
            }
        }
        // Will list wrong parameters in custom JSON error response through custom exception
        if (0 !== \count($wrongParameters)) {
            $wrongParameters = implode(', ', $wrongParameters);
            throw new BadRequestHttpException(
                sprintf('Invalid request: unknown (%s) query parameter(s)', $wrongParameters)
            );
        }
    }
}