<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * ClientController
 *
 * Manage all requests about clients data.
 *
 * @Route("/{_locale}")
 */
class ClientController extends AbstractController
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ClientController constructor.
     *
     * @param ClientRepository     $clientRepository
     * @param SerializerInterface  $serializer
     */
    public function __construct(ClientRepository $clientRepository, SerializerInterface $serializer)
    {
        $this->clientRepository = $clientRepository;
        $this->serializer = $serializer;
    }

    /**
     * List all associated clients for a particular partner
     * without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route({
     *     "en": "/partner/{uuid<[\w-]{36}>}/clients/list"
     * }, name="list_clients_per_partner", methods={"GET"})
     */
    public function listClientsPerPartner(Request $request): Response
    {
        $partnerUuid = $request->attributes->get('uuid');
        $phones = $this->clientRepository->findAllByPartner($partnerUuid);
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $phones,
            'json',
            ['groups' => ['client_list_read']]
        );
        // Pass JSON data to response
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * List all associated clients for a particular partner
     * with Doctrine paginated results.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route({
     *     "en": "/partner/{uuid<[\w-]{36}>}/clients/paginated/{page<\d+>?1}/{limit<\d+>?10}"
     * }, name="list_paginated_clients_per_partner", methods={"GET"})
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     */
    public function listPaginatedClientsByPartner(Request $request): Response
    {
        $partnerUuid = $request->attributes->get('uuid');
        $page = (int) $request->attributes->get('page');
        $limit = (int) $request->attributes->get('limit');
        // Find a set of Client entities thanks to partner uuid, parameters and Doctrine Paginator
        $clients = $this->clientRepository->findPaginatedOnesByPartner($partnerUuid, $page, $limit);
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $clients,
            'json',
            ['groups' => ['client_list_read']]
        );
        // Pass JSON data to response
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }
}
