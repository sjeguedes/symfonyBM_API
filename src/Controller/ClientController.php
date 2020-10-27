<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Repository\ClientRepository;
use App\Repository\PartnerRepository;
use App\Services\JMS\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ClientController
 *
 * Manage all requests from simple partner user (consumer) about his clients data.
 */
class ClientController extends AbstractAPIController
{
    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * ClientController constructor.
     *
     * @param ApiExpressionLanguage  $expressionLanguage
     * @param EntityManagerInterface $entityManager
     * @param RequestStack           $requestStack
     * @param UrlGeneratorInterface  $urlGenerator
     */
    public function __construct(
        ApiExpressionLanguage $expressionLanguage,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->serializer = $this->getSerializerBuilder()
            ->setObjectConstructor($this->getDeserializationObjectConstructor())
            ->setExpressionEvaluator($expressionLanguage->getApiExpressionEvaluator())
            ->build();
        $this->clientRepository = $entityManager->getRepository(Client::class);
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $this->serializer);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * List all associated clients for a particular authorized partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="list_clients", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClients(): JsonResponse
    {
        // Get complete list when request is made by an admin, with possible paginated results
        // An admin has access to all existing clients with this role!
        if ($this->isGranted(Partner::API_ADMIN_ROLE)) {
            $clients = $this->clientRepository->findList(
                $this->clientRepository->getQueryBuilder(),
                $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
            );
        // Find a set of Client entities when request is made by a particular partner, with possible paginated results
        } else {
            /** @var UuidInterface $partnerUuid */
            $partnerUuid = $this->getUser()->getUuid();
            $clients = $this->clientRepository->findListByPartner(
                $partnerUuid->toString(),
                $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
            );
        }
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $clients,
            'json',
            $this->serializationContext->setGroups(['partner:clients_list:read'])
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular client.
     *
     * Please note Symfony param converter can be used here to retrieve a Client entity.
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, name="show_client", methods={"GET"})
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function showClient(): JsonResponse
    {
        $uuid = $this->request->attributes->get('uuid');
        // Get details about a selected client when request is made by an admin
        // An admin has access to all existing clients details with this role!
        if ($this->isGranted(Partner::API_ADMIN_ROLE)) {
            $client = $this->clientRepository->findOneBy(['uuid' => $uuid]);
        // Find partner client details
        } else {
            // TODO: check null result to throw a custom exception and return an appropriate error response
            // Get authenticated partner to match client to show
            /** @var UuidInterface $partnerUuid */
            $partnerUuid = $this->getUser()->getUuid();
            $client = $this->clientRepository->findOneByPartner(
                $partnerUuid->toString(),
                $uuid
            );
        }
        // Filter result with serialization annotation
        $data = $this->serializer->serialize(
            $client,
            'json'
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }

    /**
     * Create a new client associated to authenticated partner.
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="create_client", methods={"POST"})
     *
     * @throws \Exception
     */
    public function createClient(): JsonResponse
    {
        // TODO: check body JSON content before with exception listener to return an appropriate error response (Not found))
        // Create a new client resource
        $client = $this->serializer->deserialize(
            $this->request->getContent(), // data as JSON string
            Client::class,
            'json'
        );
        // TODO: validate Client entity with validator to return an appropriate error response
        // Associate authenticated partner to new client ans save data
        /** @var UuidInterface $partnerUuid */
        $partnerUuid = $this->getUser()->getUuid();
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $authenticatedPartner = $partnerRepository->findOneBy(['uuid' => $partnerUuid->toString()]);
        $authenticatedPartner->setUpdateDate(new \DateTimeImmutable())->addClient($client);
        $this->entityManager->flush();
        // Pass custom JSON data to response but response data can be empty!
        return $this->setJsonResponse(
            null,
            Response::HTTP_CREATED,
            // headers
            [
                'Location' => $this->urlGenerator->generate(
                    'show_client',
                    ['uuid' => $client->getUuid()->toString()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ],
            Client::class
        );
    }

    /**
     * Delete a client associated to authenticated partner.
     *
     * @return Response
     *
     * @Route({
     *     "en": "/clients/{uuid<[\w-]{36}>}"
     * }, name="delete_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deleteClient(): Response
    {
        $uuid = $this->request->attributes->get('uuid');
        // TODO: check null result to throw a custom exception and return an appropriate error response (Not found)
        // Get authenticated partner to match client to remove and save deletion
        /** @var UuidInterface $partnerUuid */
        $partnerUuid = $this->getUser()->getUuid();
        // Get requested client to delete
        $client = $this->clientRepository->findOneByPartner(
            $partnerUuid->toString(),
            $uuid
        );
        // Get authenticated partner instance
        $authenticatedPartner = $client->getPartner();
        $authenticatedPartner->setUpdateDate(new \DateTimeImmutable())->removeClient($client);
        $this->entityManager->flush();
        // Response data must be empty in this case!
        return new Response(
            null,
            Response::HTTP_NO_CONTENT
        );
    }
}
