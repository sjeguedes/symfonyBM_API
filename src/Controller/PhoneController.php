<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PhoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * PhoneController
 *
 * Manage all requests about phones data.
 *
 * @Route("/{_locale}")
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
     *     "en": "/phones/list"
     * }, name="list_phones", methods={"GET"})
     */
    public function listPhones(): Response
    {
        $phones = $this->phoneRepository->findAll();
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $phones,
            'json',
            ['groups' => ['phone_list_read']]
        );
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
     *     "en": "/phones/paginated/{page<\d+>?1}/{limit<\d+>?10}"
     * }, name="list_paginated_phones", methods={"GET"})
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     */
    public function listPaginatedPhones(Request $request): Response
    {
        $page = (int) $request->attributes->get('page');
        $limit = (int) $request->attributes->get('limit');
        // Find a set of Phone entities thanks to parameters and Doctrine Paginator
        $phones = $this->phoneRepository->findPaginatedOnes($page, $limit);
        // Filter results with serialization group
        $data = $this->serializer->serialize(
            $phones,
            'json',
            ['groups' => ['phone_list_read']]
        );
        // Pass JSON data to response
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Show details about a particular phone.
     *
     * Please note Symfony param converter can be used here to retrieve a Phone entity.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route({
     *     "en": "/phone/{uuid<[\w-]{36}>}"
     * }, name="show_phone", methods={"GET"})
     */
    public function showPhone(Request $request): Response
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
        return new Response($data, Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
    }
}
