<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Entity\Phone;
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
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminPhoneController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API phone data management.
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
 * @OA\Tag(name="Administrator requests on partner phone(s)")
 *
 * @Route({
 *     "en": "/admin"
 * })
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminPhoneController extends AbstractAPIController
{
    /**
     * AdminPhoneController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        parent::__construct($responseBuilder);
    }

    /**
     * List all associated phones for a particular authenticated partner
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
     *     description="Get phone list associated to selected partner",
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
     *          description="A page number to retrieve a particular set of phones",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="per_page",
     *          description="A limit in order to define how many phones to show per page",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get phone list associated to selected partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             ref="#/components/schemas/paginated_phone_collection"
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
     * @param FilterRequestHandler  $requestHandler,
     * @param Partner               $partner,
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
     *     "en": "/partners/{uuid<[\w-]{36}>}/phones"
     * }, defaults={"entityType"=Phone::class, "isCollection"=true}, name="list_phones_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhonesPerPartner(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $phoneRepository = $this->getDoctrine()->getRepository(Phone::class);
        // Find a set of Phone entities with possible paginated results
        $phones = $phoneRepository->findListByPartner(
            $partner->getUuid()->toString(),
            $paginationData
        );
        // Get a paginated Phone collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $phones,
            Phone::class
        );
        // Filter results with serialization rules (look at Phone entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
            $this->serializationContext->setGroups(['Default', 'Phone_list'])
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
}
