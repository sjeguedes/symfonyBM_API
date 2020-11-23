<?php

declare(strict_types=1);

namespace App\Services\API\Handler;

use App\Repository\AbstractAPIRepository;
use App\Services\API\Validator\ValidationException;
use Doctrine\Persistence\ObjectRepository;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FilterRequestHandler
 *
 * Centralize API common necessary instances and methods.
 */
final class FilterRequestHandler
{
    /**
    * Define managed query parameters as filters.
    */
    const AVAILABLE_FILTERS = [
        'page',
        'per_page',
        'full_list',
        'catalog'
    ];

    /**
     * Define a pagination per page limit.
     */
    const PER_PAGE_LIMIT = 10;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * FilterRequestHandler constructor.
     *
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Filter pagination data.
     *
     * @param Request $request
     * @param int     $perPageLimit
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function filterPaginationData(Request $request, int $perPageLimit = self::PER_PAGE_LIMIT): ?array
    {
        if (!$this->isPaginationDefined($request)) {
            return null;
        }
        // Values validity will be checked in Hateoas RepresentationBuilder class!
        $page = (int) $request->query->get('page');
        // Avoid issue with parentheses when parameter is null!
        $per_page = (int) ($request->query->get('per_page') ?? $perPageLimit);
        return ['page' => $page, 'per_page' => $per_page];
    }

    /**
     * Filter an entity collection based on request pagination data and particular entity
     * depending on authenticated partner uuid.
     *
     * @param UuidInterface    $partnerUuid
     * @param ObjectRepository $repository
     * @param array            $paginationData
     * @param bool             $isFullListAllowed
     *
     * @return \IteratorAggregate
     *
     * @throws \Exception
     */
    public function filterList(
        UuidInterface $partnerUuid,
        ObjectRepository $repository,
        ?array $paginationData,
        bool $isFullListAllowed = false
    ): \IteratorAggregate {
        // Get complete list when request is made by an admin, with possible paginated results
        // An admin has access to all existing clients with this role!
        if ($isFullListAllowed) {
            $collection = $repository->findList(
                $repository->getQueryBuilder(),
                $paginationData
            );
        // Find a set of entities when request is made by a particular partner, with possible paginated results
        } else {
            $collection = $repository->findListByPartner(
                $partnerUuid->toString(),
                $paginationData
            );
        }
        return $collection;
    }

    /**
     * Check if full list query parameter exists.
     *
     * Please note that this can be used to get phone catalog for instance or other complete list.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isFullListRequested(Request $request): bool
    {
        return null !== $request->query->get('full_list') || null !== $request->query->get('catalog');
    }

    /**
     * Check if pagination query parameters exist.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isPaginationDefined(Request $request): bool
    {
        return null !== $request->query->get('page');
    }

    /**
     * Validate an entity against constraints as defined property metadata.
     *
     * @param object $entity
     *
     * @return void
     */
    public function validateEntity(object $entity): void
    {
        $allowedEntitiesClasses = AbstractAPIRepository::DATABASE_ENTITIES_ALIASES;
        if (!\array_key_exists(\get_class($entity), $allowedEntitiesClasses)) {
            throw new \RuntimeException('Entity class name is unknown!');
        }
        /** @var ConstraintViolationList $violationList */
        $violationList = $this->validator->validate($entity);
        if (0 !== $violationList->count()) {
            // Throw a custom validation exception to handle custom JSON error response
            throw new ValidationException($violationList);
        }
    }
}