<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
 * @OA\Response(
 *     response=400,
 *     ref="#/components/responses/bad_request"
 * )
 * @OA\Response(
 *     response=401,
 *     ref="#/components/responses/unauthorized"
 * )
 * @OA\Response(
 *     response=403,
 *     ref="#/components/responses/forbidden"
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
 * @OA\Tag(name="Administrator requests on partner client(s)")
 *
 * @Route({
 *     "en": "/admin"
 * })
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminClientController extends AbstractAPIController
{
    /**
     * AdminClientController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        parent::__construct($responseBuilder);
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
     * @OA\Get(
     *     description="Get client list associated to selected partner",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A partner uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     ),
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
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get client list associated to selected partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             type="array",
     *             items=@OA\Items(ref=@ApiDoc\Model(type=Client::class, groups={"Default", "Client_list"}))
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
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Partner               $partner
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * @ParamConverter("partner", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
     * }, defaults={"entityType"=Partner::class, "isCollection"=true}, name="list_clients_per_partner", methods={"GET"})
     *
     * @throws \Exception
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
     *     description="Create a new client associated to selected partner, and link resource with Location header and resource no response content",
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
     * @param FilterRequestHandler  $requestHandler,
     * @param Partner               $partner
     * @param Request               $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
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
     * @OA\Delete(
     *     description="Delete a client entry associated to selected partner by uuid as path attributes",
     *      @OA\Parameter(
     *          in="path",
     *          name="pUuid",
     *          description="A partner uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="path",
     *          name="cUuid",
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
     *     description="Delete a client associated to selected partner with no response content",
     *     @OA\MediaType(
     *          mediaType="text/plain",
     *          schema=@OA\Schema(
     *              type="string",
     *              default="",
     *              example="Empty response returned"
     *          )
     *     )
     * )
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
     *     "en": "/partners/{pUuid<[\w-]{36}>}/clients/{cUuid<[\w-]{36}>}"
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
