<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class AdminController.
 *
 * Manage all requests from authenticated administrator about API data management.
 *
 * @Route("/api/v1")
 */
class AdminController extends AbstractAPIController
{
    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;

    /**
     * AdminController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        parent::__construct($entityManager, $serializer);
    }

    /**
     * List all associated clients for a particular partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/clients"
     * }, name="list_clients_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listClientsPerPartner(Request $request): JsonResponse
    {
        $partnerUuid = $request->attributes->get('uuid');
        $clientRepository = $this->entityManager->getRepository(Client::class);
        // Find a set of Client entities thanks to partner uuid, parameters and Doctrine Paginator
        if (null !== $paginationData = $this->getPaginationData($request, self::PER_PAGE_LIMIT)) {
            $clients = $clientRepository->findAllByPartner($partnerUuid, $paginationData);
        // No pagination
        } else {
            $clients = $clientRepository->findAllByPartner($partnerUuid);
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

    /**
     * List all associated phones for a particular authenticated partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/phones"
     * }, name="list_phones_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhonesPerPartner(Request $request): JsonResponse
    {
        $partnerUuid = $request->attributes->get('uuid');
        $phoneRepository = $this->entityManager->getRepository(Phone::class);
        // Find a set of Phone entities thanks to partner uuid, parameters and Doctrine Paginator
        if (null !== $paginationData = $this->getPaginationData($request, self::PER_PAGE_LIMIT)) {
            $phones = $phoneRepository->findAllByPartner($partnerUuid, $paginationData);
        // No pagination
        } else {
            $phones = $phoneRepository->findAllByPartner($partnerUuid);
        }
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $phones,
            'json',
            ['groups' => ['phone_list_read']]
        );
        // Pass JSON data to response
        return $this->json($data, Response::HTTP_OK);
    }
}
