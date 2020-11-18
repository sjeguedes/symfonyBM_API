<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Partner;
use App\Entity\Phone;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminPhoneController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API phone data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminPhoneController extends AbstractController
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
    public function __construct(ResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
        $this->serializer = $responseBuilder->getSerializationProvider()->getSerializer();
        $this->serializationContext = $responseBuilder->getSerializationProvider()->getSerializationContext();
    }

    /**
     * List all associated phones for a particular authenticated partner
     * with (Doctrine paginated results) or without pagination.
     *
     * @param FilterRequestHandler  $requestHandler,
     * @param Partner               $partner,
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/phones"
     * }, name="list_phones_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhonesPerPartner(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        RepresentationBuilder $representationBuilder,
        Request $request
    ): JsonResponse {
        // TODO: check null result or wrong filters values to throw a custom exception and return an appropriate error response?
        $paginationData = $requestHandler->filterPaginationData($request, FilterRequestHandler::PER_PAGE_LIMIT);
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
            Phone::class,
            $paginationData
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
}
