<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Phone;
use App\Repository\PhoneRepository;
use App\Services\JMS\Builder\SerializationBuilder;
use App\Services\JMS\ExpressionLanguage\ExpressionLanguage;
use App\Services\JMS\Builder\SerializationBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminPhoneController
 *
 * Manage all requests made by authenticated administrator (special partner) about API phone data management.
 *
 * @Security("is_granted('ROLE_API_ADMIN')")
 */
class AdminPhoneController extends AbstractAPIController
{
    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;

    /**
     * AdminPhoneController constructor.
     *
     * @param ExpressionLanguage            $expressionLanguage
     * @param EntityManagerInterface        $entityManager
     * @param RequestStack                  $requestStack
     * @param SerializationBuilderInterface $serializationBuilder
     */
    public function __construct(
        ExpressionLanguage $expressionLanguage,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        SerializationBuilderInterface $serializationBuilder
    ) {
        // Initialize an expression language evaluator instance
        /** @var SerializationBuilder $serializationBuilder */
        $serializationBuilder->initExpressionLanguageEvaluator($expressionLanguage);
        parent::__construct($entityManager, $requestStack->getCurrentRequest(), $serializationBuilder);
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
        // TODO: check null result to throw a custom exception and return an appropriate error response
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
     * Show details about a particular phone depending on a particular partner "seller".
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{parUuid<[\w-]{36}>}/phones/{phoUuid<[\w-]{36}>}"
     * }, name="show_partner_phone", methods={"GET"})
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function showPartnerPhone(): JsonResponse
    {
        $partnerUuid = $this->request->attributes->get('parUuid');
        $phoneUuid = $this->request->attributes->get('phoUuid');
        // TODO: check null result to throw a custom exception and return an appropriate error response
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
