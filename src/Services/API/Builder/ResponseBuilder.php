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
class ResponseBuilder
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
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createJson(
        ?string $data,
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        // Define HAL format added to JSON response
        $headers['Content-Type'] = 'application/hal+json';
        // Get response custom configured data
        if (!\is_null($data) && Response::HTTP_OK !== $statusCode) {
            $data = $this->setCustomJsonData($statusCode, $data);
            $headers['Content-Type'] = 'application/json';
        }
        //return JsonResponse::fromJsonString($data, $statusCode, $headers);
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