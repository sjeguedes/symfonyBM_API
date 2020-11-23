<?php

declare(strict_types=1);

namespace App\Services\API\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ValidationException
 *
 * Define a constraints violation list exception if any violation exist.
 */
final class ValidationException extends \RuntimeException
{
    /**
     * @var ConstraintViolationListInterface
     */
    private $violationList;

    /**
     * ValidationException constructor.
     *
     * @param ConstraintViolationListInterface $violationList
     * @param string|null                      $message
     * @param int                              $code
     * @param \Exception|null                  $previous
     */
    public function __construct(
        ConstraintViolationListInterface $violationList,
        string $message = null,
        int $code = 0,
        \Exception $previous = null
    ) {
        /** @var ConstraintViolation $firstViolation */
        $firstViolation = $violationList[0];
        // Get root class short name  based on FQCN without reflection
        preg_match('/\\\(\w+)$/', \get_class($firstViolation->getRoot()), $matches);
        // Define default message
        if (\is_null($message)) {
            $message = sprintf(
                '%s data validation failure: %d error(s)',
                $matches[1],
                $violationList->count()
            );
        }
        parent::__construct($message, $code, $previous);
        $this->violationList = $violationList;
    }

    /**
     * Get constraints violation list.
     *
     * @return ConstraintViolationListInterface
     */
    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->violationList;
    }
}