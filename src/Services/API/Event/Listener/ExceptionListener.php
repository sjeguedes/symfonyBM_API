<?php

declare(strict_types=1);

namespace App\Services\API\Event\Listener;

use App\Services\API\Builder\ResponseBuilder;
use App\Services\API\Validator\ValidationException;
use JMS\Serializer\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ExceptionListener
 *
 * Manage API error responses.
 */
final class ExceptionListener
{
    /**
     * Define managed error or exception class names.
     */
    const SELECTED_THROWABLE_CLASSES = [
        \Error::class,
        \Exception::class,
        HttpException::class,
        ValidationException::class
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * ExceptionListener constructor.
     *
     * @param LoggerInterface $logger
     * @param ResponseBuilder $responseBuilder
     */
    public function __construct(LoggerInterface $logger, ResponseBuilder $responseBuilder)
    {
        $this->logger = $logger;
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Prepare custom error and HTTP exception response data.
     *
     * @param \Throwable $exception
     *
     * @return array
     */
    private function handleErrorAndHttpException(\Throwable $exception): array
    {
        // Define status code to 500 by default or get real status code for HTTP exception
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        // Adapt response message

        switch ($statusCode) {
            case Response::HTTP_BAD_REQUEST: // 400
            case Response::HTTP_FORBIDDEN: // 403
                $message = $exception->getMessage(); // keep original message
                break;
            case Response::HTTP_NOT_FOUND: // 404
                $message = 'Requested URI not found: please check path and parameters type or value.';
                break;
            case Response::HTTP_INTERNAL_SERVER_ERROR: // 500
                $message = 'Technical error: please contact us if necessary!';
                // Allow JMS (de)serialization exception messages
                if ($exception instanceof RuntimeException) {
                    $statusCode = Response::HTTP_BAD_REQUEST; // redefine status code to 400
                    $message = $exception->getMessage(); // keep original message
                }
                break;
            default:
                dump($exception);
                $message = 'Unknown or unexpected error: please contact us if necessary!';
        }
        return [
            'statusCode' => $statusCode,
            'message'    => $message // simple string to JSON encode
        ];
    }

    /**
     * Prepare custom validation exception response data.
     *
     * @param \Throwable $exception
     *
     * @return array
     */
    private function handleValidationException(\Throwable $exception): array
    {
        $errors = $this->listValidationErrors($exception->getConstraintViolationList());
        $statusCode = Response::HTTP_BAD_REQUEST;
        // Format validation errors into response data JSON message
        $message = json_encode(
            [
                'code'    => $statusCode,
                'message' => $exception->getMessage(),
                'errors'  => $errors
            ]
        );
        // Return response data
        return [
            'statusCode' => $statusCode,
            'message'    => $message // data already JSON encoded
        ];
    }

    /**
     * Normalize validation constraint violations by listing them with an array of errors.
     *
     * Please note normalizer is not a JMS serializer feature. It uses handler instead.
     *
     * @param ConstraintViolationListInterface $violationList
     *
     * @return array
     */
    private function listValidationErrors(ConstraintViolationListInterface $violationList): array
    {
        $errors = [];
        /** @var ConstraintViolationList $violationList */
        foreach ($violationList as $violation) {
            $propertyPath = $violation->getPropertyPath();
            // Prepare sub-property when property is another object with only 1 depth level
            if (preg_match('/^(\w+)\.(\w+)$/', $propertyPath, $matches)) {
                // CAUTION: this is probably a quite weak script part!
                $snakeCasedProperty = $this->responseBuilder->makeSnakeCasedPropertyName($matches[1]);
                $snakeCasedSubProperty = $this->responseBuilder->makeSnakeCasedPropertyName($matches[2]);
                $errors[$snakeCasedProperty][$snakeCasedSubProperty] = $violation->getMessage();
            // Prepare simple property
            } else {
                $camelCasedProperty = $this->responseBuilder->makeSnakeCasedPropertyName($propertyPath);
                $errors[$camelCasedProperty] = $violation->getMessage();
            }
        }
        return $errors;
    }

    /**
     * Call kernel exception handler to filter several HTTP exceptions
     * in order to return a JSON response instead.
     *
     * @param ExceptionEvent $event
     *
     * @return void
     *
     * @throws \Exception
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        // Get all classes associated to current exception
        $classes = class_parents($exception);
        array_unshift($classes, \get_class($exception));
        // Filter Symfony HTTP exceptions, errors or custom validation exception only
        if (empty(array_intersect($classes, self::SELECTED_THROWABLE_CLASSES))) {
           return;
        }
        // Check validation exception and others and precise if data are already JSON encoded
        if ($exception instanceof ValidationException) {
            $data = $this->handleValidationException($exception);
            $isJsonData = true;
        } else {
            $data = $this->handleErrorAndHttpException($exception);
            $isJsonData = false;
        }
        // Content-Type header is automatically changed to mention an API problem!
        $response = $this->responseBuilder->createJson($data['message'], $data['statusCode'], [], $isJsonData);
        $event->setResponse($response);
        // Log exception to get more details.
        $this->log($exception);
    }

    /**
     * Log exception in parallel.
     *
     * @param \Throwable $exception any type of thrown exception
     *
     * @return void
     */
    private function log(\Throwable $exception): void
    {
        $log = [
            'code'     => $exception->getCode(),
            'message'  => $exception->getMessage(),
            'called'   => [
                'file' => $exception->getTrace()[0]['file'],
                'line' => $exception->getTrace()[0]['line'],
            ],
            'occurred' => [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]
        ];
        // Add previous exception if needed.
        if (null !== $exception->getPrevious()) {
            $log += [
                'previous' => [
                    'message'   => $exception->getPrevious()->getMessage(),
                    'exception' => \get_class($exception->getPrevious()),
                    'file'      => $exception->getPrevious()->getFile(),
                    'line'      => $exception->getPrevious()->getLine(),
                ]
            ];
        }
        // Log exception as JSON data.
        $this->logger->error(json_encode($log));
    }
}