<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PhoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * PhoneController
 *
 * Manage all requests about phones data.
 *
 * @Route({
 *     "en": "/{_locale}/phones"
 * })
 */
class PhoneController extends AbstractController
{
    /**
     * @var PhoneRepository
     */
    private $phoneRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * PhoneController constructor.
     *
     * @param PhoneRepository     $phoneRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(PhoneRepository $phoneRepository, SerializerInterface $serializer)
    {
        $this->phoneRepository = $phoneRepository;
        $this->serializer = $serializer;
    }

    /**
     * List all available phones which are the referenced products
     * without pagination.
     *
     * @return Response
     *
     * @Route({
     *     "en": "/list"
     * }, name="list_phones", methods={"GET"})
     */
    public function listPhones(): Response
    {
        $phones = $this->phoneRepository->findAll();
        // Filter results with serialization group
        $data = $this->serializer->serialize($phones, 'json', ['groups' => ['phone_list_read']]);
        // Pass JSON data to response
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * List all available phones which are the referenced products
     * with Doctrine paginated results.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route({
     *     "en": "/paginated/{page<\d+>?1}/{limit<\d+>?10}"
     * }, name="list_paginated_phones", methods={"GET"})
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     */
    public function listPaginatedPhones(Request $request): Response
    {
        $page = $request->attributes->get('page');
        $limit =  $request->attributes->get('limit');
        // Find a set of Phone entities thanks to parameters and Doctrine Paginator
        $phones = $this->phoneRepository->findPaginatedOnes((int) $page, (int) $limit);
        // Filter results with serialization group
        $data = $this->serializer->serialize($phones, 'json', ['groups' => ['phone_list_read']]);
        // Pass JSON data to response
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }
}
