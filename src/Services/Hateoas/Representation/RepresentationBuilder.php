<?php

declare(strict_types=1);

namespace App\Services\Hateoas\Representation;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Services\API\Handler\FilterRequestHandler;
use Hateoas\Representation\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CollectionRepresentationBuilder
 *
 * Build API "HATEOAS" resource collection representation.
 */
final class RepresentationBuilder
{
    /**
     * Define collection representation custom items labels.
     */
    const ITEMS_LABELS = [
        Client::class  => 'clients',
        Offer::class   => 'offers',
        Partner::class => 'partners',
        Phone::class   => 'phones'
    ];

    /**
     * Create a Hateoas paginated collection representation.
     *
     * @param Request            $request
     * @param \IteratorAggregate $collectionResults
     * @param string             $collectionClassName
     * @param array              $paginationData
     *
     * @return PaginatedRepresentation
     */
    public function createPaginatedCollection(
        Request $request,
        \IteratorAggregate $collectionResults,
        string $collectionClassName,
        ?array $paginationData
    ): PaginatedRepresentation {
        // Define pagination parameters
        $totalCount = $collectionResults->count();
        $pageNumber = $paginationData['page'] ?? 1;
        $perPageLimit = $paginationData['per_page'] ?? FilterRequestHandler::PER_PAGE_LIMIT;
        $pageTotalCount = (int) ceil($totalCount / $perPageLimit);
        $itemsLabel = self::ITEMS_LABELS[$collectionClassName];
        // Get collection representation
        $collectionRepresentation = new CollectionResourceRepresentation($collectionResults, $itemsLabel);
        // Return paginated representation
        return $paginatedCollection = new PaginatedRepresentation(
            $collectionRepresentation,
            $request->attributes->get('_route'), // route name
            $request->attributes->get('_route_params'), // route parameters
            $pageNumber, // page number
            $perPageLimit, // limit
            $pageTotalCount, // total pages
            'page', // page route parameter name, optional, defaults to 'page'
            'per_page', // limit route parameter name, optional, defaults to 'limit'
            true, // generate relative URIs, optional, defaults to `false`
            $totalCount // total collection size, optional, defaults to `null`
        );
    }
}