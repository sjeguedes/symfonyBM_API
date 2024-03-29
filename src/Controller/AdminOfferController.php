<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HTTPCache;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminOfferController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API offer data management.
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
 * @OA\Tag(name="Administrator requests on offer(s)")
 *
 * @Route({
 *     "en": "/admin"
 * })
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminOfferController extends AbstractAPIController
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
     * List all available offers (relation between a partner and a phone)
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony custom param converters is used here
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
     *     description="Get full offer list",
     *     @OA\Parameter(
     *          in="query",
     *          name="page",
     *          description="A page number to retrieve a particular set of offers",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="per_page",
     *          description="A limit in order to define how many offers to show per page",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get full offer list as administrator",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             ref="#/components/schemas/paginated_offer_collection"
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
     *     "en": "/offers"
     * }, defaults={"entityType"=Offer::class, "isCollection"=true}, name="list_offers", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listOffers(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        /** @var UuidInterface $AuthenticatedAdminPartnerUuid */
        $AuthenticatedAdminPartnerUuid = $this->getUser()->getUuid();
        // Get complete list with possible paginated results
        $offers = $offerRepository->findList(
            $AuthenticatedAdminPartnerUuid->toString(),
            $offerRepository->getQueryBuilder(),
            $paginationData
        );
        // Get a paginated Offer collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $offers,
            Offer::class
        );
        // Filter results with serialization rules (look at Offer entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
            $this->serializationContext->setGroups(['Default', 'Offer_list', 'Partner_list', 'Phone_list'])
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
     * List all available offers (relation between a partner and a phone) for a particular partner
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
     *     description="Get offer list associated to selected partner",
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
     *          description="A page number to retrieve a particular set of offers",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="per_page",
     *          description="A limit in order to define how many offers to show per page",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get offer list associated to selected partner",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             ref="#/components/schemas/paginated_offer_collection"
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
     *     "en": "/partners/{uuid<[\w-]{36}>}/offers"
     * }, defaults={"entityType"=Offer::class, "isCollection"=true}, name="list_offers_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listOffersPerPartner(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        // Find a set of Offer entities with possible paginated results
        $offers = $offerRepository->findListByPartner(
            $partner->getUuid()->toString(),
            $paginationData
        );
        // Get a paginated Offer collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $offers,
            Offer::class
        );
        // Filter results with serialization rules (look at Offer entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
            $this->serializationContext->setGroups(['Default', 'Offer_list', 'Partner_list', 'Phone_list'])
        );
        // Pass JSON data string to response
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
     * List all available offers (relation between a partner and a phone) for a particular phone
     * with (Doctrine paginated results) or without pagination.
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
     *     description="Get offer list associated to selected phone",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A phone uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="page",
     *          description="A page number to retrieve a particular set of offers",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          in="query",
     *          name="per_page",
     *          description="A limit in order to define how many offers to show per page",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get offer list associated to selected phone",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *             ref="#/components/schemas/paginated_offer_collection"
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
     * @param Phone                 $phone
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * @ParamConverter("phone", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}/offers"
     * }, defaults={"entityType"=Offer::class, "isCollection"=true}, name="list_offers_per_phone", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listOffersPerPhone(
        FilterRequestHandler $requestHandler,
        Phone $phone,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $offerRepository = $this->getDoctrine()->getRepository(Offer::class);
        /** @var UuidInterface $AuthenticatedAdminPartnerUuid */
        $AuthenticatedAdminPartnerUuid = $this->getUser()->getUuid();
        // Find a set of Offer entities with possible paginated results
        $offers = $offerRepository->findListByPhone(
            $AuthenticatedAdminPartnerUuid->toString(),
            $phone->getUuid()->toString(),
            $paginationData
        );
        // Get a paginated Offer collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $offers,
            Offer::class
        );
        // Filter results with serialization rules (look at Offer entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
            $this->serializationContext->setGroups(['Default', 'Offer_list', 'Partner_list', 'Phone_list'])
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
     * Show details about a particular offer (relation between a partner and a phone).
     * This request is only reserved to an administrator since it makes no sense for a API final consumer.
     *
     * Please note that Symfony custom param converters are used here
     * to retrieve an Offer resource entity and HTTPCache strategy entity.
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
     *     description="Get offer details by uuid as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="An offer uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get offer details",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *              type="object",
     *              ref=@ApiDoc\Model(type=Offer::class, groups={"Default", "Offer_detail"})
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
     * @param Offer     $offer
     * @param HTTPCache $httpCache
     *
     * @ParamConverter("offer", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/offers/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Offer::class, "isCollection"=false}, name="show_offer", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showOffer(Offer $offer, HTTPCache $httpCache): JsonResponse
    {
        // Filter result with serialization rules (look at Offer entity)
        $data = $this->serializer->serialize(
            $offer,
            'json',
            $this->serializationContext->setGroups(['Default', 'Offer_detail', 'Partner_detail', 'Phone_detail'])
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
