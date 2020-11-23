<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Partner;
use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Security\PartnerVoter;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PartnerController
 *
 * Manage all requests made by a partner user (consumer) about his own data.
 *
 * Please note that an administrator can access any partner data.
 *
 * @see https://symfony.com/doc/current/controller/forwarding.html
 * @see https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#creating-a-converter
 */
class PartnerController extends AbstractController
{
    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * PartnerController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(
        ResponseBuilder $responseBuilder
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->serializer = $responseBuilder->getSerializationProvider()->getSerializer();
        $this->serializationContext = $responseBuilder->getSerializationProvider()->getSerializationContext();
    }

    /**
     * Show details about a particular partner.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner entity.
     *
     * @param Partner $partner
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{uuid<[\w-]{36}>}"
     * }, name="show_partner", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPartner(Partner $partner): JsonResponse
    {
        // Find partner details
        // An admin has access to all existing partners (including himself) details with this permission!
        $this->denyAccessUnlessGranted(
            PartnerVoter::CAN_VIEW,
            $partner,
            'Partner resource view action not allowed'
        );
        // Filter result with serialization rules (look at Partner entity)
        $data = $this->serializer->serialize(
            $partner,
            'json',
            $this->serializationContext->setGroups(['Default', 'Partner_detail'])
            // Exclude Offer collection for a simple partner, since it is not expected in this case!
        );
        // Pass JSON data string to response
        return $this->responseBuilder->createJson($data, Response::HTTP_OK);
    }

    /**
     * Show details about a particular partner.
     *
     * Please note that Symfony param converter is used here to retrieve a Partner entity.
     * A custom param converter could also ease email format check in this case.
     *
     * @param Partner $partner
     *
     * @return JsonResponse
     *
     * @Route({
     *     "en": "/partners/{email<[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+>}"
     * }, name="show_partner_by_email", methods={"GET"})
     *
     * @throws \Exception
     */
    public function showPartnerByEmail(Partner $partner): Response
    {
        // Return the same response as "showPartner" method with forwarding shortcut
        return $this->forward(self::class . '::showPartner', ['partner' => $partner]);
    }
}
