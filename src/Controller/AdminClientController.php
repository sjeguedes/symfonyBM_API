<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AdminClientController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API client data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminClientController extends AbstractController
{
    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * AdminClientController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(
        ResponseBuilder $responseBuilder
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->serializer = $responseBuilder->getSerializationProvider()->getSerializer();
        $this->serializationContext = $responseBuilder->getSerializationProvider()->getSerializationContext();
    }

    /**
     * List all associated clients for a particular partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony custom param converters are used here
     * to retrieve a Partner resource entity and HTTPCache strategy entity.
     * "Cache" Annotation below is more useful when private cache (e.g. the browser directly) is used
     * instead of proxy cache like Symfony reverse proxy!
     *
     * @Cache(
     *     public=true,
     *     maxage="httpCache.getTtlExpiration()",
     *     lastModified="httpCache.getUpdateDate()",
     *     etag="httpCache.getEtagToken()"
     * )
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Partner               $partner
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * TODO: add Partner custom DoctrineCacheConverter
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
     * }, defaults={"entityType"=Client::class, "isCollection"=true}, name="list_clients_per_partner", methods={"GET"})
     *
     * @throws \Exception
     *
     * TODO: review entityType attribute in DoctrineCacheConverter for multiple cases: here Partner et Client classes must be retrieved!
     */
    public function listClientsPerPartner(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $clientRepository = $this->getDoctrine()->getRepository(Client::class);
        // Find a set of Client entities with possible paginated results
        $clients = $clientRepository->findListByPartner(
            $partner->getUuid()->toString(),
            $paginationData
        );
        // Get a paginated Client collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $clients,
            Client::class
        );
        // Filter results with serialization rules (look at Client entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
           $this->serializationContext->setGroups(['Default', 'Client_list'])
        );
        // Pass JSON data string to response and HTTP cache headers for reverse proxy cache
        return $this->responseBuilder
            ->createJson(
                $data,
                Response::HTTP_OK,
                // Differentiate cached response
                $this->responseBuilder->mergeHttpCacheCustomHeaders($httpCache),
                true,
                HTTPCache::PROXY_CACHE
            )
            // Cache response with expiration/validation strategy
            ->setCache($this->responseBuilder->setHttpCacheStrategyHeaders($httpCache));
    }

    /**
     * Create a new client associated to a particular requested partner "seller".
     *
     * Please note that this administrates a new client to create as a partner sub-resource.
     *
     * @param FilterRequestHandler  $requestHandler,
     * @param Partner               $partner
     * @param Request               $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "partners/{uuid<[\w-]{36}>}/clients"
     * }, name="create_partner_client", methods={"POST"})
     *
     * @throws \Exception
     */
    public function createPartnerClient(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        Request $request,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        // Create a new client resource: invalid JSON is also filtered during (de)serialization.
        $client = $this->serializer->deserialize(
            $request->getContent(), // data as JSON string
            Client::class,
            'json'
        );
        // Validate Client entity (unique email and fields) with validator to return an appropriate error response
        $requestHandler->validateEntity($client);
        // Associate requested partner to new client ans save data
        $partner->setUpdateDate(new \DateTimeImmutable())->addClient($client);
        $this->getDoctrine()->getManager()->flush();
        // Pass custom data to response but response data can be empty!
        return $this->responseBuilder->createJson(
            'Client resource successfully created',
            Response::HTTP_CREATED,
            // headers
            [
                'Location' => $urlGenerator->generate(
                    'show_client',
                    ['uuid' => $client->getUuid()->toString()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ],
            false
        );
    }

    /**
     * Delete a particular client associated to a particular requested partner "seller".
     * An administrator can delete any client for every partners.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner and Client entity.
     *
     * @param Partner $partner
     * @param Client  $client
     *
     * @ParamConverter("partner", options={"mapping": {"pUuid": "uuid"}})
     * @ParamConverter("client", options={"mapping": {"cUuid": "uuid"}})
     *
     * @return Response
     *
     * @Route({
     *     "en": "partners/{pUuid<[\w-]{36}>}/clients/{cUuid<[\w-]{36}>}"
     * }, name="delete_partner_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deletePartnerClient(Partner $partner, Client $client): Response
    {
        // Check coherent pair of associated entities
        if (!$partner->getClients()->contains($client)) {
            throw new BadRequestHttpException('Selected Client to remove not associated to chosen Partner');
        }
        // Get partner to match client to remove and save deletion
        $partner->setUpdateDate(new \DateTimeImmutable())->removeClient($client);
        $this->getDoctrine()->getManager()->flush();
        // Return a simple response without data!
        return $this->responseBuilder->create(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
