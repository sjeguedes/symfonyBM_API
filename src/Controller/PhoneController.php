<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use App\Services\ExpressionLanguage\ApiExpressionLanguage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PhoneController.
 *
 * Manage all requests simple partner user (consumer) about his selected phones data.
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
     * which are the referenced products (catalog)
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
        // TODO: need to add authenticated user form request (JWT or OAuth)
        // Find a set of Phone entities with possible paginated results
        $phones = $this->phoneRepository->findList(
            $this->phoneRepository->getQueryBuilder(),
            $this->filterPaginationData($this->request, self::PER_PAGE_LIMIT)
        );
        // TODO: need to call this method when authentication will be performed!
        // Get catalog or list dedicated to a particular partner
        /*if ($this->isFullListRequested($this->request)) {
            $phones = $this->phoneRepository->findAll();
        } else {
            $phones = $this->phoneRepository->findAllByPartner($partnerUuid, $paginationData);
        }*/
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
