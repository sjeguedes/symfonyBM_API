<?php

declare(strict_types=1);

namespace App\Services\API\Builder;

use App\Services\API\Provider\JMSSerializationProvider;
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
     * @var JMSSerializationProvider
     */
    private $serializationProvider;

    /**
     * ResponseBuilder constructor.
     *
     * @param JMSSerializationProvider $serializationProvider
     */
    public function __construct(JMSSerializationProvider $serializationProvider)
    {
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
     * @param string|null $data       a JSON response string or custom message
     * @param int         $statusCode a response status code
     * @param array       $headers    an array of response headers
     * @param bool        $isAlreadyJson
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createJson(
        ?string $data,
        int $statusCode = 200,
        array $headers = [],
        bool $isAlreadyJson = true
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
        // Return standard JSON response for all cases
        return new JsonResponse($data, $statusCode, $headers, true);
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