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
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\UuidInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminOfferController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API offer data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminOfferController extends AbstractController
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
     * AdminPhoneController constructor.
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
     * List all available offers (relation between a partner and a phone)
     * with (Doctrine paginated results) or without pagination.
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
        // Get complete list with possible paginated results
        $offers = $offerRepository->findList(
            $this->getUser()->getUuid(),
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
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * List all available offers (relation between a partner and a phone) for a particular partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner entity.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Partner               $partner
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/offers"
     * }, defaults={"entityType"=Offer::class, "isCollection"=true}, name="list_offers_per_partner", methods={"GET"})
     *
     * @throws \Exception
     *
     * TODO: review entityType attribute in DoctrineCacheConverter for multiple cases: here Partner et Offer classes must be retrieved!
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
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * List all available offers (relation between a partner and a phone) for a particular phone
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony param converter is used here to retrieve a Phone entity.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Phone                 $phone
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     * @param HTTPCache             $httpCache
     *
     * @ParamConverter("httpCache", converter="http.cache.custom_converter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}/offers"
     * }, defaults={"entityType"=Offer::class, "isCollection"=true}, name="list_offers_per_phone", methods={"GET"})
     *
     * @throws \Exception
     *
     * TODO: review entityType attribute in DoctrineCacheConverter for multiple cases: here Phone et Offer classes must be retrieved!
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
        /** @var UuidInterface $adminUserUuid */
        $adminUserUuid = $this->getUser()->getUuid();
        // Find a set of Offer entities with possible paginated results
        $offers = $offerRepository->findListByPhone(
            $adminUserUuid->toString(),
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
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular offer (relation between a partner and a phone).
     * This request is only reserved to an administrator since it makes no sense for a API final consumer.
     *
     * Please note that Symfony param converter is used here to retrieve an Offer entity.
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
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }
}
