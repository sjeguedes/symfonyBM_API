<?php

declare(strict_types=1);

namespace App\Controller;

use App\Services\API\Builder\ResponseBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AbstractAPIController
 *
 * Manage all requests about API data management.
 */
abstract class AbstractAPIController extends AbstractController
{
    /**
     * @var ResponseBuilder
     */
    protected $responseBuilder;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var SerializationContext
     */
    protected $serializationContext;

    /**
     * AbstractAPIController constructor.
     *
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(ResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
        $this->serializer = $responseBuilder->getSerializationProvider()->getSerializer();
        $this->serializationContext = $responseBuilder->getSerializationProvider()->getSerializationContext();
    }
}
