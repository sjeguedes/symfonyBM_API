<?php

declare(strict_types=1);

namespace App\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Trait FilteredQueryHelperTrait.
 *
 * Manage API Doctrine repositories filtered results.
 */
trait FilteredQueryHelperTrait
{
    /**
     * Find a set of entities paginated results depending on a particular Doctrine query builder,
     * offset and limit integers parameters
     * using Doctrine paginator.
     *
     * @param QueryBuilder $queryBuilder
     * @param int          $page
     * @param int          $limit
     *
     * @return \IteratorAggregate|Paginator
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/tutorials/pagination.html
     */
    public function filterWithPagination(QueryBuilder $queryBuilder, int $page, int $limit): \IteratorAggregate
    {
        $query = $queryBuilder->getQuery()
            // Define offset value
            ->setFirstResult(($page - 1) * $limit)
            // Pass limit value
            ->setMaxResults($limit);
        // Paginator is returned since it is an IteratorAggregate implementation.
        // An array can also be returned with "iterator_to_array(new Paginator($query));".
        return new Paginator($query);
    }
}