<?php

declare(strict_types=1);

namespace App\Controller;

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
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminPartnerController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API partner data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminPartnerController extends AbstractController
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
     * AdminPartnerController constructor.
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
     * List all available partners with (Doctrine paginated results) or without pagination.
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
     *     "en": "/partners"
     * }, defaults={"isCollection"=true}, name="list_partners", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPartners(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request,
        HTTPCache $httpCache
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $partnerRepository = $this->getDoctrine()->getRepository(Partner::class);
        // Get complete list with possible paginated results
        $partners = $partnerRepository->findList(
            $this->getUser()->getUuid(),
            $partnerRepository->getQueryBuilder(),
            $paginationData,
            true
        );
        // Get a paginated Partner collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $partners,
            Partner::class
        );
        // Filter results with serialization rules (look at Partner entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
            $this->serializationContext->setGroups(['Default', 'Partner_list'])
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
