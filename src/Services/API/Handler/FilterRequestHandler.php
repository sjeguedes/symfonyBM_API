<?php

declare(strict_types=1);

namespace App\Services\API\Handler;

use App\Repository\AbstractAPIRepository;
use App\Services\API\Validator\ValidationException;
use Doctrine\Persistence\ObjectRepository;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @param Request  $request
     * @param int|null $perPageLimit
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function filterPaginationData(Request $request, ?int $perPageLimit = self::PER_PAGE_LIMIT): ?array
    {
        if (!$this->isPaginationDefined($request)) {
            return null;
        }
        // Values coherence will be checked in Hateoas RepresentationBuilder class!
        $page = $request->query->get('page');
        // Avoid issue with parentheses when parameter is null or with no value (empty string)!
        $per_page = $request->query->get('per_page');
        $per_page = (null !== $per_page && 0 !== strlen($per_page) ? $per_page : null) ?? $perPageLimit;
        $message = 'Pagination %1$s (%2$s) parameter failure: expected value >= 1';
        if ($page < 1) {
            throw new BadRequestHttpException(sprintf($message, 'number', 'page'));
        }
        if ($per_page < 1) {
            throw new BadRequestHttpException(sprintf($message, 'limit', 'per_page'));
        }
        return [
            'page'     => (int) $page,
            'per_page' => (int) $per_page
        ];
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
        // An admin has access to all existing resources with this role!
        if ($isFullListAllowed) {
            $collection = $repository->findList(
                $partnerUuid->toString(),
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
        $catalogParameter = $request->query->get('catalog');
        $fullListParameter = $request->query->get('full_list');
        $isValidParameter = (null !== $catalogParameter && 0 === strlen($catalogParameter)) ||
                            (null !== $fullListParameter && 0 === strlen($fullListParameter)) ||
                            (null === $catalogParameter && null === $fullListParameter);
        if (!$isValidParameter) {
            throw new BadRequestHttpException('No value expected for \'full_list\' or \'catalog\' query parameter');
        }
        return $isValidParameter;
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
        $page = $request->query->get('page');
        $per_page = $request->query->get('per_page');
        // Check undefined attribute or empty string value
        $isPage = null !== $page && 0 !== strlen($page);
        if (!$isPage && null !== $per_page) {
            $message = 'Pagination %1$s (%2$s) parameter failure: undefined page parameter';
            throw new BadRequestHttpException(sprintf($message,'limit','per_page'));
        }
        $message = 'Pagination %1$s (%2$s) parameter failure: undefined value';
        if (null !== $page && 0 === strlen($page)) {
            throw new BadRequestHttpException(sprintf($message, 'number', 'page'));
        }
        if (null !== $per_page && 0 === strlen($per_page)) {
            throw new BadRequestHttpException(sprintf($message, 'limit', 'per_page'));
        }
        return $isPage;
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