<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Repository\ClientRepository;
use App\Repository\PartnerRepository;
use App\Services\JMS\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AdminClientController
 *
 * Manage all requests made by authenticated administrator (special partner) about API client data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminClientController extends AbstractAPIController
{
    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * AdminClientController constructor.
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
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $this->serializer);
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * List all associated clients for a particular partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
     * }, name="list_clients_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClientsPerPartner(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('uuid');
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->entityManager->getRepository(Client::class);
        // Find a set of Client entities with possible paginated results
        $clients = $clientRepository->findListByPartner(
            $partnerUuid,
            $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
        );
        // Filter results with serialization groups annotations with exclusion if necessary
        $data = $this->serializer->serialize(
            $clients,
            'json',
           $this->serializationContext->setGroups(['partner:clients_list:read'])
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular client depending on a particular partner "seller".
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{parUuid<[\w-]{36}>}/clients/{cliUuid<[\w-]{36}>}"
     * }, name="show_partner_client", methods={"GET"})
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function showPartnerClient(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('parUuid');
        $clientUuid = $this->request->attributes->get('cliUuid');
        // TODO: check null result to throw a custom exception and return an appropriate error response
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->entityManager->getRepository(Client::class);
        // Get details about a selected client by precising a specific partner
        $client = $clientRepository->findOneByPartner(
            $partnerUuid,
            $clientUuid
        );
        // Filter result with serialization annotations if necessary
        $data = $this->serializer->serialize(
            $client,
            'json'
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }

    /**
     * Create a new client associated to a particular requested partner "seller".
     *
     * Please note this administrates client to create as a partner sub-resource.
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "partners/{uuid<[\w-]{36}>}/clients"
     * }, name="create_partner_client", methods={"POST"})
     *
     * @throws \Exception
     */
    public function createPartnerClient(): JsonResponse
    {
        // TODO: check body JSON content before with exception listener to return an appropriate error response
        // Create a new client resource
        $client = $this->serializer->deserialize(
            $this->request->getContent(), // data as JSON string
            Client::class,
            'json'
        );
        // TODO: validate Client entity with validator to return an appropriate error response
        // Associate requested partner to new client ans save data
        $partnerUuid = $this->request->attributes->get('uuid');
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $requestedPartner = $partnerRepository->findOneBy(['uuid' => $partnerUuid]);
        $requestedPartner->setUpdateDate(new \DateTimeImmutable())->addClient($client);
        $this->entityManager->flush();
        // Pass custom JSON data to response but response data can be empty!
        return $this->setJsonResponse(
            null,
            Response::HTTP_CREATED,
            // headers
            [
                'Location' => $this->urlGenerator->generate(
                    'show_partner_client',
                    ['parUuid' => $partnerUuid, 'cliUuid' => $client->getUuid()->toString()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ],
            Client::class
        );
    }

    /**
     * Delete a requested client associated to a particular requested partner "seller".
     *
     * @return Response
     *
     * @Route({
     *     "en": "/partners/{parUuid<[\w-]{36}>}/clients/{cliUuid<[\w-]{36}>}"
     * }, name="delete_partner_client", methods={"DELETE"})
     *
     * @throws \Exception
     */
    public function deletePartnerClient(): Response
    {
        $partnerUuid = $this->request->attributes->get('parUuid');
        $clientUuid = $this->request->attributes->get('cliUuid');
        // TODO: check null result to throw a custom exception and return an appropriate error response (Not found)
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->entityManager->getRepository(Client::class);
        // Get requested client to delete
        $client = $clientRepository->findOneByPartner(
            $partnerUuid,
            $clientUuid
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
