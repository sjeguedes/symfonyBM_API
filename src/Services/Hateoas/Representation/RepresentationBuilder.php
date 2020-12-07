<?php

declare(strict_types=1);

namespace App\Services\Hateoas\Representation;

use App\Entity\Client;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Phone;
use App\Services\API\Cache\DoctrineCacheResultListIterator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     *
     * @return PaginatedRepresentation
     *
     * @throws \Exception
     */
    public function createPaginatedCollection(
        Request $request,
        \IteratorAggregate $collectionResults,
        string $collectionClassName
    ): PaginatedRepresentation {
        // Get collection items (full list) total count
        $totalCount = $collectionResults->count();
        $resultsRange = $this->getFirstAndMaxResultsWithIterator($collectionResults, $totalCount);
        $firstResult = $resultsRange['firstResult'];
        $perPageLimit = $resultsRange['maxResults'];
        // Retrieve current page number
        $currentPageNumber = ($firstResult + $perPageLimit) / $perPageLimit;
        // Get total page count
        $pageTotalCount = (int) ceil($totalCount / $perPageLimit);
        $itemsLabel = self::ITEMS_LABELS[$collectionClassName];
        // Get collection representation
        $collectionRepresentation = new CollectionResourceRepresentation($collectionResults, $itemsLabel);
        // Return paginated representation
        return $paginatedCollection = new PaginatedRepresentation(
            $collectionRepresentation,
            $request->attributes->get('_route'), // route name
            $request->attributes->get('_route_params'), // route parameters
            $currentPageNumber, // current page number or 1 for full list without pagination
            $perPageLimit, // limit
            $pageTotalCount, // total pages
            'page', // page route parameter name, optional, defaults to 'page'
            'per_page', // limit route parameter name, optional, defaults to 'limit'
            true, // generate relative URIs, optional, defaults to `false`
            $totalCount // total collection size, optional, defaults to `null`
        );
    }

    /**
     * Get first and max results parameters depending of expected iterator.
     *
     * @param \IteratorAggregate $collectionResults the chosen iterator
     * @param int                $totalCount        collection items (full list) total count
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getFirstAndMaxResultsWithIterator(\IteratorAggregate $collectionResults, int $totalCount): array
    {
        if (!$collectionResults instanceof DoctrineCacheResultListIterator && !$collectionResults instanceof Paginator) {
            throw new \RuntimeException("A wrong iterator type is used for representation!");
        }
        // Define pagination parameters based on query
        if ($collectionResults instanceof Paginator) {
            /** @var Query $query */
            $query = $collectionResults->getQuery();
            $firstResult = $query->getFirstResult() ?? 0;
            $perPageLimit = $query->getMaxResults() ?? $totalCount;
        } else {
            $firstResult = $collectionResults->getFirstResult();
            $perPageLimit = $collectionResults->getMaxResults();
        }
        return ['firstResult' => $firstResult, 'maxResults' => $perPageLimit];
    }
}