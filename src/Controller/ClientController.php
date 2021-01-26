<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\API\Security\ClientVoter;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ClientController
 *
 * Manage all requests from simple partner user (consumer) about his clients data.
 *
 * @OA\Response(
 *     response=400,
 *     ref="#/components/responses/bad_request"
 * )
 * @OA\Response(
 *     response=401,
 *     ref="#/components/responses/unauthorized"
 * )
 * @OA\Response(
 *     response=404,
 *     ref="#/components/responses/not_found"
 * )
 * @OA\Response(
 *     response=500,
 *     ref="#/components/responses/internal"
 * )
 *
 * @OA\Tag(name="Partner requests to manage his own client(s)")
 */
class ClientController extends AbstractAPIController
{
    /**
     * ClientController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        parent::__construct($responseBuilder);
    }

    /**
     * List all associated clients for a particular authorized partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony custom param converter is used here
     * to retrieve a HTTPCache strategy entity.
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
     * @OA\Get(
     *     description="Get client list associated to authenticated partner",
     *     @OA\Parameter(
     *          in="query",
     *          name="page",
     *          description="A page number to retrieve a particular set of clients",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="per_page",
     *          description="A limit in order to define how many clients to show per page",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="full_list",
     *          description="A full client list relative to application available for administrator only",
     *          allowEmptyValue=true
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get client list associated to authenticated partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             ref="#/components/schemas/paginated_client_collection"
     *          )
     *     ),
      *    @OA\Header(
     *          header="Content-Type",
     *          ref="#/components/headers/content_type"
     *     ),
     *     @OA\Header(
     *          header="Cache-Control",
     *          ref="#/components/headers/cache_control"
     *     ),
     *     @OA\Header(
     *          header="Etag",
     *          ref="#/components/headers/etag"
     *     ),
     *     @OA\Header(
     *          header="Last-Modified",
     *          ref="#/components/headers/last_modified"
     *     ),
     *     @OA\Header(
     *          header="X-App-Cache-Id",
     *          ref="#/components/headers/x_cache_id"
     *     ),
     *     @OA\Header(
     *          header="X-App-Cache-Ttl",
     *          ref="#/components/headers/x_cache_ttl"
     *     ),
     *     @OA\Header(
     *          header="Vary",
     *          ref="#/components/headers/vary"
     *    )
     * )
     * @OA\Response(
     *     response=403,
     *     ref="#/components/responses/forbidden"
     * )
     *
     * @param FilterRequestHandler  $requestHandler
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, defaults={"entityType"=Client::class, "isCollection"=true}, name="list_clients", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClients(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $isFullListRequested = $requestHandler->isFullListRequested($request);
        $isAdminRole = $this->isGranted(Partner::API_ADMIN_ROLE);
        // Get Client collection depending on authenticated partner
        $clients = $requestHandler->filterList(
            $this->getUser()->getUuid(),
            $this->getDoctrine()->getRepository(Client::class),
            $paginationData,
            $isAdminRole && $isFullListRequested
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
     * Show details about a particular client.
     *
     * Please note that Symfony custom param converters are used here
     * to retrieve a Client resource entity and HTTPCache strategy entity.
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
     * @OA\Get(
     *     description="Get client details by uuid as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A client uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get client details associated to authenticated partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *              type="object",
     *              ref=@ApiDoc\Model(type=Client::class, groups={"Default", "Client_detail"})
     *          )
     *     ),
     *     @OA\Header(
     *          header="Content-Type",
     *          ref="#/components/headers/content_type"
     *     ),
     *     @OA\Header(
     *          header="Cache-Control",
     *          ref="#/components/headers/cache_control"
     *     ),
     *     @OA\Header(
     *          header="Etag",
     *          ref="#/components/headers/etag"
     *     ),
     *     @OA\Header(
     *          header="Last-Modified",
     *          ref="#/components/headers/last_modified"
     *     ),
     *     @OA\Header(
     *          header="X-App-Cache-Id",
     *          ref="#/components/headers/x_cache_id"
     *     ),
     *     @OA\Header(
     *          header="X-App-Cache-Ttl",
     *          ref="#/components/headers/x_cache_ttl"
     *     ),
     *     @OA\Header(
     *          header="Vary",
     *          ref="#/components/headers/vary"
     *    )
     * )
     * @OA\Response(
     *     response=403,
     *     ref="#/components/responses/forbidden"
     * )
     *
     * @param Client    $client
     * @param HTTPCache $httpCache
     *
     * @ParamConverter("client", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Client::class, "isCollection"=false}, name="show_client", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showClient(Client $client, HTTPCache $httpCache): JsonResponse
    {
        // Check view permission (Requested client must be associated to authenticated partner.)
        $this->denyAccessUnlessGranted(
            ClientVoter::CAN_VIEW,
            $client,
            'Client resource view action not allowed'
        );
        // Filter result with serialization rules (look at Client entity)
        $data = $this->serializer->serialize(
            $client,
            'json',
            $this->serializationContext->setGroups(['Default', 'Client_detail'])
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
     * Create a new client associated to authenticated partner.
     *
     * @OA\Post(
     *     description="Create a new client entry associated to authenticated partner"
     * )
     *
     * @OA\RequestBody(
     *     description="Client creation expected data",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(property="name", type="string", example="Dupont S.A.S"),
     *          @OA\Property(property="type", type="string", example="Professionnel"),
     *          @OA\Property(property="email", type="string", example="prestataire-2510@dupont-sas.com")
     *    )
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Create a new client associated to selected partner, and link resource with Location header and no resource response content",
     *     @OA\MediaType(
     *          mediaType="text/plain",
     *          schema=@OA\Schema(
     *              type="string",
     *              default="",
     *              example="Empty response returned"
     *          )
     *     ),
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          schema=@OA\Schema(
     *              type="object",
     *              @OA\Property(property="code", type="integer", example="201"),
     *              @OA\Property(property="message", type="string", example="Client resource successfully created")
     *          )
     *     ),
     *     @OA\Header(
     *          header="Location",
     *          ref="#/components/headers/client_creation_location"
     *    )
     * )
     * @OA\Response(
     *     response="400 (validation)",
     *     description="Invalidate data due to request body JSON content with wrong properties values",
     *     @OA\MediaType(
     *          mediaType="application/problem+json",
     *          schema=@OA\Schema(
     *              @OA\Property(property="code", type="integer", example=400),
     *              @OA\Property(property="message", type="string", example="Client data validation failure: 3 error(s)"),
     *              @OA\Property(
     *                  property="errors",
     *                  type="object",
     *                  properties={
     *                      @OA\Property(property="name", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="email", type="string")
     *                  },
     *                  example={
     *                      "name"="This value should not be blank.",
     *                      "type"="The value you selected is not a valid choice.",
     *                      "email"="This value is not a valid email address."
     *                  }
     *              )
     *         )
     *     )
     * )
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Request               $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="create_client", methods={"POST"})
     *
     * @throws \Exception
     */
    public function createClient(
        FilterRequestHandler $requestHandler,
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
        // Associate authenticated partner to new client ans save data
        /** @var Partner $authenticatedPartner */
        $authenticatedPartner = $this->getUser();
        $authenticatedPartner->setUpdateDate(new \DateTimeImmutable())->addClient($client);
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
     * Delete a client associated to authenticated partner.
     * An administrator can delete any client.
     *
     * Please note that Symfony param converter is used here to retrieve a Client entity.
     *
     * @OA\Delete(
     *     description="Delete a client entry associated to authenticated partner by uuid as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A client uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=204,
     *     description="Delete a client with no response content",
     *     @OA\MediaType(
     *          mediaType="text/plain",
     *          schema=@OA\Schema(
     *              type="string",
     *              default="",
     *              example="Empty response returned"
     *          )
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     ref="#/components/responses/forbidden"
     * )
     *
     * @param Client $client
     *
     * @ParamConverter("client", converter="doctrine.cache.custom_converter")
     *
     * @return Response
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, name="delete_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deleteClient(Client $client): Response
    {
        // Check deletion permission (Requested client must be associated to authenticated partner.)
        $this->denyAccessUnlessGranted(
            ClientVoter::CAN_DELETE,
            $client,
            'Client resource deletion action not allowed'
        );
        // Get authenticated partner to match client to remove and save deletion
        /** @var Partner $authenticatedPartner */
        $authenticatedPartner = $this->getUser();
        // Particular case: remove client depending on partner owner if authenticated partner has admin role!
        if ($this->isGranted(Partner::API_ADMIN_ROLE)) {
            // Possibly use a fake authenticated partner if admin is not the client owner!
            $authenticatedPartner = $this->getClientAssociatedPartnerOwner($authenticatedPartner, $client);
        }
        $authenticatedPartner->setUpdateDate(new \DateTimeImmutable())->removeClient($client);
        $this->getDoctrine()->getManager()->flush();
        // Return a simple response without data!
        return $this->responseBuilder->create(
            null,
            Response::HTTP_NO_CONTENT
        );
    }

    /**
     * Get Client entity corresponding (associated) Partner owner.
     *
     * @param Partner $authenticatedPartner
     * @param Client  $client
     *
     * @return Partner|UserInterface
     */
    private function getClientAssociatedPartnerOwner(Partner $authenticatedPartner, Client $client): Partner
    {
        $authenticatedPartnerUuid = $authenticatedPartner->getUuid();
        $clientPartnerUuid = $client->getPartner()->getUuid();
        if ($authenticatedPartnerUuid->toString() !== $clientPartnerUuid->toString()) {
            return $client->getPartner();
        }
        return $authenticatedPartner;
    }
}
