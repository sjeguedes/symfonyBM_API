<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HTTPCache;
use App\Entity\Phone;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhoneController
 *
 * Manage all requests from a simple partner user (consumer) about his selected phones data.
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
 * @OA\Tag(name="Partner requests to manage his own phone(s) or catalog")
 */
class PhoneController extends AbstractAPIController
{
    /**
     * PhoneController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        parent::__construct($responseBuilder);
    }

    /**
     * List all available phones for a particular authenticated partner or entire catalog
     * with (Doctrine paginated results) or without pagination.
     *
     * All the referenced products can also be listed thanks to catalog filter.
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
     *     description="Get phone list associated to authenticated partner or complete catalog",
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
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="full_list",
     *          description="A full phone list relative to application available for all authenticated partners",
     *          allowEmptyValue=true
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get phone list associated to authenticated partner",
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
     *     "en": "/phones"
     * }, defaults={"entityType"=Phone::class, "isCollection"=true}, name="list_phones", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhones(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $isFullListRequested = $requestHandler->isFullListRequested($request);
        // Get Phone collection depending on authenticated partner
        // Get catalog complete list with filter or partner phone list, with possible paginated results
        $phones = $requestHandler->filterList(
            $this->getUser()->getUuid(),
            $this->getDoctrine()->getRepository(Phone::class),
            $paginationData,
            $isFullListRequested // catalog filter
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

    /**
     * Show details about a particular phone provided by complete available list (catalog).
     *
     * Please note that Symfony custom param converters are used here
     * to retrieve a Phone resource entity and HTTPCache strategy entity.
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
     *     description="Get phone details by uuid as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A phone uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get phone details associated to authenticated partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *              type="object",
     *              ref=@ApiDoc\Model(type=Phone::class, groups={"Default", "Phone_detail"})
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
     *
     * @param Phone     $phone
     * @param HTTPCache $httpCache
     *
     * @ParamConverter("phone", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Phone::class, "isCollection"=false}, name="show_phone", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPhone(Phone $phone, HTTPCache $httpCache): JsonResponse
    {
        // Get serialized phone details (from catalog at this time)
        // Filter results with serialization rules (look at Phone entity)
        $data = $this->serializer->serialize(
            $phone,
            'json',
            $this->serializationContext->setGroups(['Default', 'Phone_detail'])
            // IMPORTANT: Exclude Offer collection for a simple partner
            // since it is not interesting/expected in this case!
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
