<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClientController.
 *
 * Manage all requests from simple partner user (consumer) about his clients data.
 *
 * @Route("/api/v1")
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
     * @param EntityManagerInterface $entityManager
     * @param RequestStack           $requestStack
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->clientRepository = $entityManager->getRepository(Client::class);
        parent::__construct($entityManager, $requestStack->getCurrentRequest());
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
        // TODO: need to add authenticated user form request (JWT or OAuth)
        // Find a set of Client entities with possible paginated results
        $clients = $this->clientRepository->findList(
            $this->clientRepository->getQueryBuilder(),
            $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
        );
        // TODO: need to call this method when authentication will be performed!
        //$clients = $this->clientRepository->findAllByPartner($partnerUuid, $paginationData);
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $clients,
            'json',
            $this->serializationContext->setGroups(['partner:clients_list:read'])
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }
}
