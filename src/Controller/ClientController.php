<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Repository\ClientRepository;
use App\Services\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * ClientController constructor.
     *
     * @param ApiExpressionLanguage  $expressionLanguage
     * @param EntityManagerInterface $entityManager
     * @param RequestStack           $requestStack
     */
    public function __construct(
        ApiExpressionLanguage $expressionLanguage,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->serializer = $this->getSerializerBuilder()
            ->setExpressionEvaluator($expressionLanguage->getApiExpressionEvaluator())
            ->build();
        $this->clientRepository = $entityManager->getRepository(Client::class);
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $this->serializer);
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
            $partnerUuid = $this->getUser()->getUuid();
            $clients = $this->clientRepository->findListByPartner(
                $partnerUuid,
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
            $partnerUuid = $this->getUser()->getUuid();
            $client = $this->clientRepository->findOneByPartner(
                $partnerUuid,
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
}
