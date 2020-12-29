<?php

declare(strict_types=1);

namespace App\Services\API\Cache;

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class CacheKernel
 *
 * Enable Symfony simple reverse proxy for HTTP cache.
 *
 * Please look at public/index.php front controller changes.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching#Shared_proxy_caches
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-None-Match
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Modified-Since
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Vary
 * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpKernel/HttpCache/HttpCache.php
 * @see https://symfony.com/doc/current/http_cache.html
 */
class CacheKernel extends HttpCache
{
    protected function getOptions(): array
    {
        return [
            'trace_header' => 'X-Symfony-Cache'
        ];
    }

    /**
     * Override (adapt behavior) parent method:
     * - in order to deliver cached response in case of server response 304 status code.
     * to get requested data as an API consumer,
     * - in order to refresh expired response to be able to store it again.
     *
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true): Response
    {
        $response = parent::handle($request, $type, $catch);
        // Refresh cache after expiration to forward to client!
        if (!$this->isFreshEnough($request, $response)) {
            $response = $this->refreshValidCachedResponseAfterExpiration($request, $response);
        }
        // If 304 status code "Not-modified" is returned by server, get cached response to forward to client!
        if (Response::HTTP_NOT_MODIFIED === $response->getStatusCode()) {
            $response = $this->forwardNotModifiedCachedResponse($request, $response);
        }

        // Get response from parent method
        return $response;
    }

    /**
     * Forward cached response to client, if it exists in case of not modified server response
     * to behave like a reverse proxy cache.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    private function forwardNotModifiedCachedResponse(Request $request, Response $response): Response
    {
        // Return not modified response directly if no cached corresponding result is found!
        if (\is_null($cachedResponse = $this->getStore()->lookup($request))) {
            return $response;
        }
        $fileCachePath = $cachedResponse->headers->get('X-Body-File');
        $cachedResponse->setContent(file_get_contents($fileCachePath));
        $cachedResponse->headers->set('Age', $response->getAge());
        // Set custom trace header to follow transformed not modified server response
        if ('dev' === $this->getKernel()->getEnvironment()) {
            $sfTraceHeader = $this->getOptions()['trace_header'];
            $sfCustomTraceHeaderValue = $request->getMethod() . ' ' . $request->getRequestUri() . ': not-modified, fresh';
            $cachedResponse->headers->set($sfTraceHeader, $sfCustomTraceHeaderValue);
        }
        // Remove sensitive cache path from headers
        $cachedResponse->headers->remove('X-Body-File');
        return $cachedResponse;
    }

    /**
     * Refresh cache after expiration to get updated stored cached response headers
     * which corresponds to a valid cached result.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     *
     * @throws \Exception
     */
    private function refreshValidCachedResponseAfterExpiration(Request $request, Response $response): Response
    {
        if (Response::HTTP_OK === $response->getStatusCode()) {
            // Update cache control max-age (thanks to app custom header) and re-validation headers
            $response->setMaxAge((int) $response->headers->get('X-App-Cache-Ttl'));
            $response->headers->addCacheControlDirective('proxy-revalidate');
            $response->headers->set('Age', $response->getAge());
            // Set custom trace header to follow transformed expired but refreshed server response
            if ('dev' === $this->getKernel()->getEnvironment()) {
                $sfTraceHeader = $this->getOptions()['trace_header'];
                $sfCustomTraceHeaderValue = $request->getMethod() . ' ' . $request->getRequestUri() . ': refreshed';
                !$response->headers->has($sfTraceHeader) ?: $response->headers->set($sfTraceHeader, $sfCustomTraceHeaderValue);
            }
            $this->store($request, $response);
        }
        return $response;
    }
}