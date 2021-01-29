<?php

declare(strict_types=1);

namespace App\Services\API\Event\Listener;

use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class ResponseListener
 *
 * Manage API responses.
 */
final class ResponseListener
{
    /**
     * Define managed response status codes.
     */
    const SELECTED_RESPONSE_STATUS_CODE = [
        Response::HTTP_UNAUTHORIZED,
        Response::HTTP_FORBIDDEN
    ];

    /**
     * Call kernel response handler to filter Lexik bundle authentication failure response
     * in order to return a JSON response content type header set to "application/problem+json".
     *
     * @param ResponseEvent $event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Modify response object content type header depending on authentication failure context and selected status code
        $response = $event->getResponse();
        $isStatusCodeInSelection = \in_array($response->getStatusCode(), self::SELECTED_RESPONSE_STATUS_CODE);
        if ($response instanceof JWTAuthenticationFailureResponse && $isStatusCodeInSelection) {
            $response->headers->set('Content-Type', 'application/problem+json');
        }
    }
}