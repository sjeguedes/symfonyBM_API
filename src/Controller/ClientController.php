<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ClientController.
 *
 * Manage all requests from partner user about his clients data.
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
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        parent::__construct($entityManager, $serializer);
        $this->clientRepository = $entityManager->getRepository(Client::class);
    }

    /**
     * List all associated clients for a particular authorized partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/clients"
     * }, name="list_clients", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClients(Request $request): JsonResponse
    {
        // TODO: need to add authenticated user form request (JWT or OAuth)
        // Find a set of Client entities thanks to partner uuid, parameters and Doctrine Paginator
        if (null !== $paginationData = $this->getPaginationData($request, self::PER_PAGE_LIMIT)) {
            $clients = $this->clientRepository->findPaginatedOnes(
                $this->clientRepository->getQueryBuilder(),
                $paginationData['page'],
                $paginationData['per_page']
            );
            //$clients = $this->clientRepository->findAllByPartner($partnerUuid, $paginationData);
        // No pagination
        } else {
            // TODO: need to call this method when authentication will be performed!
            $clients = $this->clientRepository->findAll();
            //$clients = $this->clientRepository->findBy(['partner', $partnerUuid]);
        }
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $clients,
            'json',
            ['groups' => ['client_list_read']]
        );
        // Pass JSON data to response
        return $this->json($data, Response::HTTP_OK);
    }
}
