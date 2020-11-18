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
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        $paginationData = $requestHandler->filterPaginationData($request, FilterRequestHandler::PER_PAGE_LIMIT);
        $clientRepository = $this->getDoctrine()->getRepository(Client::class);
        // TODO: refactor this conditional part in RequestHandler method filterList(...)
        // Get complete list when request is made by an admin, with possible paginated results
        // An admin has access to all existing clients with this role!
        if ($this->isGranted(Partner::API_ADMIN_ROLE)) {
            $clients = $clientRepository->findList(
                $clientRepository->getQueryBuilder(),
                $paginationData
            );
        // Find a set of Client entities when request is made by a particular partner, with possible paginated results
        } else {
            /** @var UuidInterface $partnerUuid */
            $partnerUuid = $this->getUser()->getUuid();
            $clients = $clientRepository->findListByPartner(
                $partnerUuid->toString(),
                $paginationData
            );
        }
        // Get a paginated Client collection representation
        $paginatedCollection = $representationBuilder->createPaginatedCollection(
            $request,
            $clients,
            Client::class,
            $paginationData
        );
        // Filter results with serialization group
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
     * @param Client  $client
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, name="show_client", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showClient(Client $client): JsonResponse
    {
        // Find partner client details
        // An admin has access to all existing clients details with this role!
        if (!$this->isGranted(Partner::API_ADMIN_ROLE)) {
            // TODO: check false result to throw a custom exception and return an appropriate error response
            // TODO: Make a Voter service instead
            // Get authenticated partner to match client to show
            /** @var Partner $authenticatedPartner */
            $authenticatedPartner = $this->getUser();
            // Check partner and client relation with extra lazy fetch
            if (!$authenticatedPartner->getClients()->contains($client)) {
                // do stuff to return custom error response caught by kernel listener
                throw new AccessDeniedException('Show action on client resource not allowed');
            }
        }
        // Filter result with serialization annotation
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
        // TODO: check body JSON content before with exception listener to return an appropriate error response (Bad request)
        $requestedContent = $request->getContent();
        if (!$requestHandler->isValidJson($requestedContent)) {
            // do stuff to return custom error response caught by kernel listener
            throw new BadRequestHttpException('Invalid requested JSON');
        }
        // Create a new client resource
        $client = $this->serializer->deserialize(
            $requestedContent, // data as JSON string
            Client::class,
            'json'
        );
        // TODO: validate Client entity (unique email and validity on fields) with validator to return an appropriate error response
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
            ]
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
     * @return Response
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, name="delete_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deleteClient(Client $client): Response
    {
        // An admin has access to all existing clients details with this role!
        $partner = $client->getPartner();
        // A simple partner can only delete his clients only
        if (!$this->isGranted(Partner::API_ADMIN_ROLE)) {
            // TODO: check false result to throw a custom exception and return an appropriate error response
            // TODO: Make a Voter service instead
            // Get authenticated partner to match client to remove and save deletion
            /** @var Partner $authenticatedPartner */
            $authenticatedPartner = $this->getUser();
            // Check partner and client relation with extra lazy fetch
            if (!$authenticatedPartner->getClients()->contains($client)) {
                // do stuff to return custom error response caught by kernel listener
                throw new AccessDeniedException('Deletion action on client resource not allowed');
            }
            $partner = $authenticatedPartner;
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
