<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait PaginationDataHelperTrait.
 *
 * Manage API pagination used data.
 */
trait FilteredDataHelperTrait
{
    /**
     * Check if pagination query parameters exist.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isPaginated(Request $request): bool
    {
        return null !== $request->query->get('page');
    }

    /**
     * Get pagination data.
     *
     * @param Request  $request
     * @param int|null $perPageLimit
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function getPaginationData(Request $request, int $perPageLimit = null): ?array
    {
        if (!$this->isPaginated($request)) {
            return null;
        }
        $page = (int) $request->query->get('page');
        $per_page = (int) $request->query->get('per_page') ?? $perPageLimit;
        // Check if a pagination limit is correctly defined.
        if (null === $per_page && null === $perPageLimit) {
            throw new \RuntimeException('A pagination limit must be defined!');
        }
        return ['page' => $page, 'per_page' => $per_page];
    }
}