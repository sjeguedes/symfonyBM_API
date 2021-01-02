<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\HTTPCache;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Security\PartnerVoter;
use Nelmio\ApiDocBundle\Annotation as ApiDoc;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PartnerController
 *
 * Manage all requests made by a partner user (consumer) about his own data.
 *
 * Please note that an administrator can access any partner data.
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
 * @OA\Tag(name="Partner requests to manage his own data")
 *
 * @see https://symfony.com/doc/current/controller/forwarding.html
 * @see https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#creating-a-converter
 */
class PartnerController extends AbstractAPIController
{
    /**
     * PartnerController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        parent::__construct($responseBuilder);
    }

    /**
     * Show details about a particular partner.
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
     *     description="Get partner details by uuid as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="uuid",
     *          description="A partner uuid",
     *          @OA\Schema(
     *              pattern="[\w-]{36}",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get current authenticated partner details",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *              type="object",
     *              ref=@ApiDoc\Model(type=Partner::class, groups={"Default", "Partner_detail"})
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
     * @param Partner   $partner
     * @param HTTPCache $httpCache
     *
     * @ParamConverter("partner", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Partner::class, "isCollection"=false}, name="show_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPartner(Partner $partner, HTTPCache $httpCache): JsonResponse
    {
        // Find partner details
        // An admin has access to all existing partners (including himself) details with this permission!
        $this->denyAccessUnlessGranted(
            PartnerVoter::CAN_VIEW,
            $partner,
            'Partner resource view action not allowed'
        );
        // Filter result with serialization rules (look at Partner entity)
        $data = $this->serializer->serialize(
            $partner,
            'json',
            $this->serializationContext->setGroups(['Default', 'Partner_detail'])
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

    /**
     * Show details about a particular partner.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner entity.
     * A custom param converter could also ease email format check in this case.
     * No cache is used here due to email attribute which could be treated as a particular case
     * with API custom DoctrineCacheConverter and HTTPCacheConverter.
     *
     * @Cache(
     *     public=true,
     *     maxage="httpCache.getTtlExpiration()",
     *     lastModified="httpCache.getUpdateDate()",
     *     etag="httpCache.getEtagToken()"
     * )
     *
     * @OA\Get(
     *     description="Get partner details by email as path attribute",
     *     @OA\Parameter(
     *          in="path",
     *          name="email",
     *          description="A partner unique email",
     *          @OA\Schema(
     *              pattern="[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+",
     *              type="string"
     *          )
     *     )
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Get current authenticated partner details",
     *     @OA\MediaType(
     *          mediaType="application/vnd.hal+json",
     *          schema=@OA\Schema(
     *              type="object",
     *              ref=@ApiDoc\Model(type=Partner::class, groups={"Default", "Partner_detail"})
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
     *     )
     * )
     *
     * @param Partner   $partner
     * @param HTTPCache $httpCache
     *
     * @ParamConverter("partner", converter="doctrine.cache.custom_converter")
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{email<[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+>}"
     * }, defaults={"entityType"=Partner::class, "isCollection"=false}, name="show_partner_by_email", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPartnerByEmail(Partner $partner, HTTPCache $httpCache): Response
    {
        // Return the same response as "showPartner" method with forwarding shortcut
        return $this->forward(self::class . '::showPartner', ['partner' => $partner, 'httpCache' => $httpCache]);
    }
}
