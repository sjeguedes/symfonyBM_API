<?php

declare(strict_types=1);

namespace App\Services\Hateoas\Representation;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use Hateoas\Representation\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $perPageLimit = $paginationData['per_page'] ?? 1;
        $message = 'Pagination %1$s (%2$s) parameter failure: expected value >= 1';
        if ($perPageLimit < 1 || $perPageLimit > $totalCount) {
            $message .= $totalCount > 1 ? sprintf(' or value <= %1$d', $totalCount) : '';
            throw new BadRequestHttpException(sprintf($message, 'limit', 'per_page'));
        }
        // Get total page count
        $pageTotalCount = (int) ceil($totalCount / $perPageLimit);
        if ($pageNumber < 1 || $pageNumber > $pageTotalCount) {
            $message .= $pageTotalCount > 1 ? sprintf(' or value <= %1$d', $pageTotalCount) : '';
            throw new BadRequestHttpException(sprintf($message, 'number', 'page'));
        }
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