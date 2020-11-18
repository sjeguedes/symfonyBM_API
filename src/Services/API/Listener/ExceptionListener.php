<?php

declare(strict_types=1);

namespace App\Services\API\Listener;

use App\Services\API\Builder\ResponseBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ExceptionListener
 *
 * Manage API error responses.
 */
class ExceptionListener
{
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
        // Filter Symfony HTTP exceptions or errors only
        if (!$exception instanceof HttpException && !$exception instanceof \Error) {
           return;
        }
        // Define status code to 500 in case of error or get real status code for HTTP exception
        $statusCode = $exception instanceof \Error ? Response::HTTP_INTERNAL_SERVER_ERROR : $exception->getStatusCode();
        // Adapt response message
        switch ($statusCode) {
            case Response::HTTP_BAD_REQUEST: // 400
            case Response::HTTP_FORBIDDEN: // 403
                $message = $exception->getMessage();
                break;
            case Response::HTTP_NOT_FOUND: // 404
                $message = 'Request URI not found: please check route and parameters type.';
                break;
            case Response::HTTP_INTERNAL_SERVER_ERROR: // 500
                $message = 'Technical error: please contact us if necessary!';
                break;
            default:
                $message = 'Unknown or unexpected error: please contact us if necessary!';
        }
        // Content-Type header is automatically changed to mention an API problem!
        $response = $this->responseBuilder->createJson($message, $statusCode);
        $event->setResponse($response);
        // Log exception to get more details.
        $this->log($exception);
    }

    /**
     * Log exception in parallel.
     *
     * @param \Throwable $exception an exception with Symfony HttpException or \Error type
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
        if ($exception->getPrevious() !== null) {
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