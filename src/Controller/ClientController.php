<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Partner;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
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
        // Get complete list when request is made by an admin, with possible paginated results
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
}
