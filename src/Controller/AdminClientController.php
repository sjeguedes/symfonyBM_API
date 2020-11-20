<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AdminClientController
 *
 * Manage all requests made by authenticated administrator (special partner account) about API client data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminClientController extends AbstractController
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
     * AdminClientController constructor.
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
     * List all associated clients for a particular partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner entity.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Partner               $partner
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     *
     * @ParamConverter("partner", options={"mapping": {"uuid": "uuid"}})
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
     * }, name="list_clients_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClientsPerPartner(
        FilterRequestHandler $requestHandler,
        Partner $partner,
        RepresentationBuilder $representationBuilder,
        Request $request
    ): JsonResponse {
        // TODO: check null result or wrong filters values to throw a custom exception and return an appropriate error response?
        $paginationData = $requestHandler->filterPaginationData($request, FilterRequestHandler::PER_PAGE_LIMIT);
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
            Client::class,
            $paginationData
        );
        // Filter results with serialization rules (look at Client entity)
        $data = $this->serializer->serialize(
            $paginatedCollection,
            'json',
           $this->serializationContext->setGroups(['Default', 'Client_list'])
        );
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Create a new client associated to a particular requested partner "seller".
     *
     * Please note that this administrates a new client to create as a partner sub-resource.
     *
     * @param FilterRequestHandler  $requestHandler,
     * @param Partner               $partner
     * @param Request               $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "partners/{uuid<[\w-]{36}>}/clients"
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
}
