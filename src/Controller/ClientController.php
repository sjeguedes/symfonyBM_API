<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Handler\FilterRequestHandler;
use App\Services\API\Security\ClientVoter;
use App\Services\Hateoas\Representation\RepresentationBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ClientController
 *
 * Manage all requests from simple partner user (consumer) about his clients data.
 */
class ClientController extends AbstractController
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
     * ClientController constructor.
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
     * List all associated clients for a particular authorized partner
     * with (Doctrine paginated results) or without pagination.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param RepresentationBuilder $representationBuilder
     * @param Request               $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="list_clients", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClients(
        FilterRequestHandler $requestHandler,
        RepresentationBuilder $representationBuilder,
        Request $request
    ): JsonResponse {
        $paginationData = $requestHandler->filterPaginationData($request);
        $isFullListRequested = $requestHandler->isFullListRequested($request);
        $isAdminRole = $this->isGranted(Partner::API_ADMIN_ROLE);
        // Get Client collection depending on authenticated partner
        $clients = $requestHandler->filterList(
            $this->getUser()->getUuid(),
            $this->getDoctrine()->getRepository(Client::class),
            $paginationData,
            $isAdminRole && $isFullListRequested
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
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular client.
     *
     * Please note that Symfony param converter is used here to retrieve a Client entity.
     *
     * @param Client $client
     *
     * @ParamConverter("client", converter="DoctrineCacheConverter")
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Client::class}, name="show_client", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showClient(Client $client): JsonResponse
    {
        // Find partner client details
        // An admin has access to all existing clients details with this permission!
        $this->denyAccessUnlessGranted(
            ClientVoter::CAN_VIEW,
            $client,
            'Client resource view action not allowed'
        );
        // Filter result with serialization rules (look at Client entity)
        $data = $this->serializer->serialize(
            $client,
            'json',
            $this->serializationContext->setGroups(['Default', 'Client_detail'])
        );
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Create a new client associated to authenticated partner.
     *
     * @param FilterRequestHandler  $requestHandler
     * @param Request               $request
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="create_client", methods={"POST"})
     *
     * @throws \Exception
     */
    public function createClient(
        FilterRequestHandler $requestHandler,
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
        // Associate authenticated partner to new client ans save data
        /** @var Partner $authenticatedPartner */
        $authenticatedPartner = $this->getUser();
        $authenticatedPartner->setUpdateDate(new \DateTimeImmutable())->addClient($client);
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
     * Delete a client associated to authenticated partner.
     * An administrator can delete any client.
     *
     * Please note that Symfony param converter is used here to retrieve a Client entity.
     *
     * @param Client $client
     *
     * @ParamConverter("client", converter="DoctrineCacheConverter")
     *
     * @return Response
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, defaults={"entityType"=Client::class}, name="delete_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deleteClient(Client $client): Response
    {
        // An admin has access to all existing clients details with this permission!
        $this->denyAccessUnlessGranted(
            ClientVoter::CAN_DELETE,
            $client,
            'Client resource deletion action not allowed'
        );
        // An admin has access to all existing clients details with this role!
        $partner = $client->getPartner();
        // A simple partner can only delete his clients!
        if (!$this->isGranted(Partner::API_ADMIN_ROLE)) {
            // Get authenticated partner to match client to remove and save deletion
            /** @var Partner $partner */
            $partner = $this->getUser();
        }
        $partner->setUpdateDate(new \DateTimeImmutable())->removeClient($client);
        $this->getDoctrine()->getManager()->flush();
        // Return a simple response without data!
        return $this->responseBuilder->create(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
