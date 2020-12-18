<?php

declare(strict_types=1);

namespace App\Services\API\Builder;

use App\Entity\HTTPCache;
use App\Services\API\Provider\JMSSerializationProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseBuilder
 *
 * Manage API response.
 */
final class ResponseBuilder
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JMSSerializationProvider
     */
    private $serializationProvider;

    /**
     * ResponseBuilder constructor.
     *
     * @param EntityManagerInterface   $entityManager
     * @param JMSSerializationProvider $serializationProvider
     */
    public function __construct(EntityManagerInterface $entityManager, JMSSerializationProvider $serializationProvider)
    {
        $this->entityManager = $entityManager;
        $this->serializationProvider = $serializationProvider;
    }

    /**
     * Return a simple response instance.
     *
     * @param string|null $data       a JSON response string
     * @param int         $statusCode a response status code
     * @param array       $headers    an array of response headers
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function create(
        string $data = null,
        int $statusCode = 200,
        array $headers = []
    ): Response {
        return new Response($data, $statusCode, $headers);
    }

    /**
     * Return a JSON response instance.
     *
     * @param string|null $data            a JSON response string or custom message
     * @param int         $statusCode      a response status code
     * @param array       $headers         an array of response headers
     * @param bool        $isAlreadyJson   define if data is JSON formatted
     * @param int         $httpCacheConfig a kind of cache is used (e.g. Symfony reverse proxy, private browser cache)
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createJson(
        ?string $data,
        int $statusCode = 200,
        array $headers = [],
        bool $isAlreadyJson = true,
        int $httpCacheConfig = HTTPCache::NONE
    ): JsonResponse {
        // Define HAL format added to JSON response by default
        $headers['Content-Type'] = 'application/hal+json';
        // Get response custom configured data
        if (!\is_null($data) && Response::HTTP_OK !== $statusCode) {
            // Get exception or error correct Content-Type header
            $headers['Content-Type'] = $statusCode >= Response::HTTP_BAD_REQUEST
                ? 'application/problem+json' : 'application/json';
            // Pass directly JSON data or encode data to JSON if needed
            $data = !$isAlreadyJson ? $this->setCustomJsonData($statusCode, $data) : $data;
        }
        $response = new JsonResponse($data, $statusCode, $headers, true);
        // Force re-validation in case of cache expiration
        if (HTTPCache::NONE !== $httpCacheConfig) {
            $response->headers->addCacheControlDirective(
                HTTPCache::PROXY_CACHE === $httpCacheConfig ? 'proxy-revalidate' : 'must-revalidate'
            );
        }
        // Return standard JSON response for all cases
        return $response;
    }

    /**
     * Get JMS serialization provider which allow access to serializer and serialization context instances.
     *
     * @return JMSSerializationProvider
     */
    public function getSerializationProvider(): JMSSerializationProvider
    {
        return $this->serializationProvider;
    }

    /**
     * Make snake cased string format based on property path.
     *
     * This is useful for validation errors for instance.
     *
     * @param string $propertyName
     *
     * @return string
     */
    public function makeSnakeCasedPropertyName(string $propertyName): string
    {
        $snakeCasedProperty = strtolower(
            preg_replace('/(?<=\w)([A-Z])/', '_$1', $propertyName)
        );
        // Return possibly changed string to format to snake case format
        return $snakeCasedProperty;
    }

    /**
     * Merge custom cache headers with other headers
     *
     * Please note this is used to differentiate HTTP cache data storage per request.
     *
     * @param HTTPCache $httpCache     custom HTTPCache entity instance
     * @param array     $customHeaders additional particular custom cache headers per response
     * @param array     $headers       any common headers
     *
     * @return array
     */
    public function mergeHttpCacheCustomHeaders(HTTPCache $httpCache, array $customHeaders = [], array $headers = []): array
    {
        $defaultCustomCacheHeaders = [
            'X-App-Cache-Ttl' => $httpCache->getTtlExpiration(),
            'X-App-Cache-Id'  => $httpCache->getUuid()->toString(),
            // CAUTION: "Authorization" header must (not sure about this!) be precised to keep stateless process active!
            'Vary'            => ['Authorization', 'X-App-Cache']
        ];
        // Merge and make final array with unique entries
        return array_unique(array_merge($defaultCustomCacheHeaders, $customHeaders, $headers), SORT_REGULAR);
    }

    /**
     * Save each new HTTPCache and prepare cache strategy necessary headers data for response.
     *
     * @param HTTPCache $httpCache
     *
     * @return array
     *
     * @see Response::setCache()
     *
     * @throws \Exception
     */
    public function setHttpCacheStrategyHeaders(HTTPCache $httpCache): array
    {
        // Persist and save each new HTTPCache instance if necessary (avoid useless database entries).
        // Otherwise, this means this instance already exists in database.
        if (!$this->entityManager->contains($httpCache)) {
            $this->entityManager->persist($httpCache);
            $this->entityManager->flush();
        }
        // Generate data in order to create cache strategy headers
        return $httpCache->generateHeadersOptions();
    }

    /**
     * Set custom json string response data.
     *
     * @param int    $statusCode
     * @param string $message    a custom message to inform API consumer
     *
     * @return string
     *
     * @throws \Exception
     */
    private function setCustomJsonData(int $statusCode, string $message): string
    {
        // Return a uniform JSON string
        return json_encode(['code' => $statusCode, 'message' => $message]);
    }
}