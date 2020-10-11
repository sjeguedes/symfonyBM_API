<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class PhoneController.
 *
 * Manage all requests from partner user about his selected phones data.
 *
 * @Route("/api/v1")
 */
class PhoneController extends AbstractAPIController
{
    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;

    /**
     * @var PhoneRepository
     */
    private $phoneRepository;

    /**
     * PhoneController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        parent::__construct($entityManager, $serializer);
        $this->phoneRepository = $entityManager->getRepository(Phone::class);
    }

    /**
     * List all available phones for a particular authenticated partner
     * which are the referenced products (catalog)
     * with (Doctrine paginated results) or without pagination.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones"
     * }, name="list_phones", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhones(Request $request): JsonResponse
    {
        // TODO: need to add authenticated user form request (JWT or OAuth)
        // Find a set of Phone entities thanks to parameters and Doctrine Paginator
        if (null !== $paginationData = $this->getPaginationData($request, self::PER_PAGE_LIMIT)) {
            $phones = $this->phoneRepository->findPaginatedOnes(
                $this->phoneRepository->getQueryBuilder(),
                $paginationData['page'],
                $paginationData['per_page']
            );
            //$phones = $this->phoneRepository->findAllByPartner($partnerUuid, $paginationData);
        // No pagination
        } else {
            // TODO: add catalog route and particular method!
            $phones = $this->phoneRepository->findAll();
            // TODO: need to call this method when authentication will be performed!
           //$phones = $this->phoneRepository->findAllByPartner($partnerUuid);
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

    /**
     * Show details about a particular phone.
     *
     * Please note Symfony param converter can be used here to retrieve a Phone entity.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}"
     * }, name="show_phone", methods={"GET"})
     */
    public function showPhone(Request $request): JsonResponse
    {
        $uuid = $request->attributes->get('uuid');
        $phone = $this->phoneRepository->findOneBy(['uuid' => $uuid]);
        // Filter result with serialization group
        $data = $this->serializer->serialize(
            $phone,
            'json',
            // Exclude Offer collection since it is not expected at this time in app.
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['offers']]
        );
        // Pass JSON data to response
        return $this->json($data, Response::HTTP_OK);
    }
}
