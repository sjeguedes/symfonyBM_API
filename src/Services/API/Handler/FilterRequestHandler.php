<?php

declare(strict_types=1);

namespace App\Services\API\Handler;

use App\Repository\AbstractAPIRepository;
use App\Services\API\Validator\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FilterRequestHandler
 *
 * Centralize API common necessary instances and methods.
 */
final class FilterRequestHandler
{
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
     * @param int|null $perPageLimit
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function filterPaginationData(Request $request, int $perPageLimit = null): ?array
    {
        if (!$this->isPaginated($request)) {
            return null;
        }
        $page = (int)$request->query->get('page');
        $per_page = (int)$request->query->get('per_page') ?? $perPageLimit;
        // Check if a pagination limit is correctly defined.
        if (null === $per_page && null === $perPageLimit) {
            throw new \RuntimeException('A pagination limit must be defined!');
        }
        return ['page' => $page, 'per_page' => $per_page];
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
    private function isPaginated(Request $request): bool
    {
        return null !== $request->query->get('page');
    }

    /**
     * Check a valid JSON string.
     *
     * @param string $json
     *
     * @return bool
     */
    public function isValidJson(string $json): bool
    {
        // Compare "No error" code: JSON_ERROR_NONE error code is "0".
        json_decode($json);
        return 0 === json_last_error();
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