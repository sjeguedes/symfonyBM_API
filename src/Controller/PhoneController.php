<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Phone;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhoneController
 *
 * Manage all requests simple partner user (consumer) about his selected phones data.
 */
class PhoneController extends AbstractController
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
     * PhoneController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
        $this->serializer = $responseBuilder->getSerializationProvider()->getSerializer();
        $this->serializationContext = $responseBuilder->getSerializationProvider()->getSerializationContext();
    }

    /**
     * List all available phones for a particular authenticated partner
     * or all the referenced products (catalog filter or when request is made by an admin)
     * with (Doctrine paginated results) or without pagination.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones"
     * }, name="list_phones", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhones(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request
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
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular phone provided by complete available list (catalog).
     *
     * Please note that Symfony param converter is used here to retrieve a Phone entity.
     *
     * @param Phone $phone
     *
     * @ParamConverter("phone", converter="DoctrineCacheConverter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Phone::class}, name="show_phone", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPhone(Phone $phone): JsonResponse
    {
        // Get serialized phone details (from catalog at this time)
        // Filter results with serialization rules (look at Phone entity)
        $data = $this->serializer->serialize(
            $phone,
            'json',
            $this->serializationContext->setGroups(['Default', 'Phone_detail'])
            // Exclude Offer collection since it is not interesting as a simple partner!
        );
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }
}
