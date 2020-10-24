<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Partner;
use App\Entity\Phone;
use App\Repository\PhoneRepository;
use App\Services\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhoneController
 *
 * Manage all requests simple partner user (consumer) about his selected phones data.
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
        $this->phoneRepository = $entityManager->getRepository(Phone::class);
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $this->serializer);
    }

    /**
     * List all available phones for a particular authenticated partner
     * or all the referenced products (catalog filter or when request made by an admin)
     * with (Doctrine paginated results) or without pagination.
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones"
     * }, name="list_phones", methods={"GET"})
     *
     * @throws \Exception
     */
    public function listPhones(): JsonResponse
    {
        // Get catalog complete list with filter or when request is made by an admin, with possible paginated results
        // An admin has access to all existing clients with this role!
        if ($this->isFullListRequested($this->request) || $this->isGranted(Partner::API_ADMIN_ROLE)) {
            $phones = $this->phoneRepository->findList(
                $this->phoneRepository->getQueryBuilder(),
                $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
            );
        // Find a set of Phone entities when request is made by a particular partner, with possible paginated results
        } else {
            // Get partner uuid from authenticated user
            /** @var UuidInterface $partnerUuid */
            $partnerUuid = $this->getUser()->getUuid();
            $phones = $this->phoneRepository->findListByPartner(
                $partnerUuid->toString(),
                $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
            );
        }
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $phones,
            'json',
            $this->serializationContext->setGroups(['partner:phones_list:read'])
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular phone.
     *
     * Please note Symfony param converter can be used here to retrieve a Phone entity.
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/phones/{uuid<[\w-]{36}>}"
     * }, name="show_phone", methods={"GET"})
     */
    public function showPhone(): JsonResponse
    {
        $uuid = $this->request->attributes->get('uuid');
        $phone = $this->phoneRepository->findOneBy(['uuid' => $uuid]);
        // Filter result with serialization annotation
        $data = $this->serializer->serialize(
            $phone,
            'json'
            // Exclude Offer collection since it is not interesting for a simple partner!
        );
        // Pass JSON data string to response
        return $this->setJsonResponse($data, Response::HTTP_OK);
    }
}
