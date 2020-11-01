<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AbstractAPIRepository;
use App\Services\JMS\Builder\SerializationBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
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
     * @param EntityManagerInterface        $entityManager
     * @param Request                       $request
     * @param SerializationBuilderInterface $serializationBuilder
     * @param bool                          $isSerializationContextNeeded
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Request $request,
        SerializationBuilderInterface $serializationBuilder,
        bool $isSerializationContextNeeded = true
    ) {
        $this->entityManager = $entityManager;
        $this->request = $request;
        $this->serializer = $serializationBuilder->build();
        $this->serializationContext = $isSerializationContextNeeded ? $serializationBuilder->getSerializationContext() : null;
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
        return null !== $request->query->get('full_list') || null !== $request->query->get('catalog');
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
            Response::HTTP_CREATED    => "{$entityShortName} resource successfully created", // creation
            Response::HTTP_NO_CONTENT => "{$entityShortName} resource successfully deleted" // deletion
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