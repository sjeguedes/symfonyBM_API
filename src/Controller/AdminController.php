<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Phone;
use App\Repository\ClientRepository;
use App\Repository\PhoneRepository;
use App\Services\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 *
 * Manage all requests from authenticated administrator about API data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
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
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $this->serializer);
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
     * List all associated phones for a particular authenticated partner
     * with (Doctrine paginated results) or without pagination.
     *
     * Please note using Symfony param converter is not really efficient here!
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}/phones"
     * }, name="list_phones_per_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhonesPerPartner(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('uuid');
        /** @var PhoneRepository $phoneRepository */
        $phoneRepository = $this->entityManager->getRepository(Phone::class);
        // Find a set of Phone entities with possible paginated results
        $phones = $phoneRepository->findListByPartner(
            $partnerUuid,
            $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
        );
        // Filter results with serialization groups annotations with exclusion if necessary
        $data = $this->serializer->serialize(
            $phones,
            'json',
            $this->serializationContext->setGroups(['partner:phones_list:read'])
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
     */
    public function showPartnerClient(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('parUuid');
        $clientUuid = $this->request->attributes->get('cliUuid');
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
     * Show details about a particular phone depending on a particular partner "seller".
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{parUuid<[\w-]{36}>}/phones/{phoUuid<[\w-]{36}>}"
     * }, name="show_partner_phone", methods={"GET"})
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function showPartnerPhone(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('parUuid');
        $phoneUuid = $this->request->attributes->get('phoUuid');
        /** @var PhoneRepository $phoneRepository */
        $phoneRepository = $this->entityManager->getRepository(Phone::class);
        // Get details about a selected phone by precising a specific partner seller
        $phone = $phoneRepository->findOneByPartner(
            $partnerUuid,
            $phoneUuid
        );
        // Filter result with serialization annotations if necessary
        $data = $this->serializer->serialize(
            $phone,
            'json'
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }
}
