<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AbstractAPIRepository;
use App\Services\JMS\ObjectConstructor;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractAPIController
 *
 * Centralize API common necessary instances and methods.
 */
abstract class AbstractAPIController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var SerializationContext|null
     */
    protected $serializationContext;

    /**
     * AbstractAPIController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Request                $request
     * @param SerializerInterface    $serializer
     * @param bool                   $isSerializationContextNeeded
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Request $request,
        SerializerInterface $serializer = null,
        bool $isSerializationContextNeeded = true
    ) {
        $this->entityManager = $entityManager;
        $this->request = $request;
        $this->serializer = $serializer ?? $this->getSerializer();
        $this->serializationContext = $isSerializationContextNeeded ? $this->getSerializationContext() : null;
    }

    /**
     * Filter pagination data.
     *
     * @param Request  $request
     * @param int|null $perPageLimit
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function filterPaginationData(Request $request, int $perPageLimit = null): ?array
    {
        if (!$this->isPaginated($request)) {
            return null;
        }
        $page = (int) $request->query->get('page');
        $per_page = (int) $request->query->get('per_page') ?? $perPageLimit;
        // Check if a pagination limit is correctly defined.
        if (null === $per_page && null === $perPageLimit) {
            throw new \RuntimeException('A pagination limit must be defined!');
        }
        return ['page' => $page, 'per_page' => $per_page];
    }

    /**
     * Get a JMS Deserialization Object constructor instance.
     *
     * @return ObjectConstructorInterface
     */
    protected function getDeserializationObjectConstructor(): ObjectConstructorInterface
    {
        return new ObjectConstructor();
    }

    /**
     * Get a JMS SerializationContext instance.
     *
     * @return SerializationContext
     */
    protected function getSerializationContext(): SerializationContext
    {
        return SerializationContext::create();
    }

    /**
     * Get a JMS Serializer instance.
     *
     * @return SerializerInterface
     */
    protected function getSerializer(): SerializerInterface
    {
        return $this->getSerializerBuilder()->build();
    }

    /**
     * Get a JMS SerializerBuilder instance.
     *
     * @return SerializerBuilder
     */
    protected function getSerializerBuilder(): SerializerBuilder
    {
        return SerializerBuilder::create();
    }

    /**
     * Check if full list query parameter exists.
     *
     * Please note this can be used to get phone catalog for instance or other complete list.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isFullListRequested(Request $request): bool
    {
        return null !== $request->query->get('from_full_list') || null !== $request->query->get('list');
    }

    /**
     * Check if pagination query parameters exist.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isPaginated(Request $request): bool
    {
        return null !== $request->query->get('page');
    }

    /**
     * Set custom json string response data.
     *
     * @param int    $statusCode
     * @param string $resourceClassName
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function setCustomJsonData(int $statusCode, string $resourceClassName): string
    {
        if (!\array_key_exists($resourceClassName, AbstractAPIRepository::DATABASE_ENTITIES_ALIASES)) {
            throw new \InvalidArgumentException('API Resource class name is unknown!');
        }
        // Get entity class short name
        preg_match('/\\\([^\\\]+)$/', $resourceClassName, $matches);
        $entityShortName = $matches[1];
        $messages = [
            Response::HTTP_CREATED => "{$entityShortName} resource successfully created" // creation
        ];
        if (!\array_key_exists($statusCode, $messages)) {
            throw new \InvalidArgumentException('HTTP status code is not associated to a custom message!');
        }
        return json_encode(['code' => $statusCode, 'message' => $messages[$statusCode]]);
    }

    /**
     * Return a JSON response instance.
     *
     * @param string|null $data              a JSON response string
     * @param int         $statusCode        a response status code
     * @param array       $headers           an array of response headers
     * @param string|null $resourceClassName a fully qualified resource class name
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    protected function setJsonResponse(
        string $data = null,
        int $statusCode = 200,
        array $headers = [],
        string $resourceClassName = null
    ): JsonResponse {
        // Get response custom configured data
        if (\is_null($data) && !\is_null($resourceClassName)) {
            $data = $this->setCustomJsonData($statusCode, $resourceClassName);
        }
        return JsonResponse::fromJsonString($data, $statusCode, $headers);
    }
}