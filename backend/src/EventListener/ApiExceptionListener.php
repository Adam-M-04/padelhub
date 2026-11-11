<?php

namespace App\EventListener;

use App\Exception\ApiException;
use App\Http\ApiErrorResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $response = new ApiErrorResponse(
                $exception->getStatusCode(),
                $exception->getErrorCode(),
                $exception->getMessage(),
                $exception->getDetails(),
            );
        } elseif ($exception instanceof ExtraAttributesException) {
            $details = [];
            foreach ($exception->getExtraAttributes() as $field) {
                $details[] = ['field' => $field, 'message' => 'This field is not allowed.'];
            }

            $response = new ApiErrorResponse(400, 'VALIDATION_ERROR', 'Validation failed', $details);
        } elseif ($exception instanceof HttpExceptionInterface) {
            $response = $this->createResponseForHttpException($exception);
        } else {
            $response = new ApiErrorResponse(500, 'INTERNAL_ERROR', 'Unexpected error');
        }

        $event->setResponse($response);
    }

    private function createResponseForHttpException(HttpExceptionInterface $exception): ApiErrorResponse
    {
        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $details = [];
            foreach ($previous->getViolations() as $violation) {
                $details[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return new ApiErrorResponse(400, 'VALIDATION_ERROR', 'Validation failed', $details);
        }

        if ($previous instanceof NotEncodableValueException) {
            return new ApiErrorResponse(400, 'INVALID_JSON', 'Request body is not valid JSON');
        }

        $statusCode = $exception->getStatusCode();
        $errorCode = match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            415 => 'UNSUPPORTED_MEDIA_TYPE',
            default => 'HTTP_ERROR',
        };
        $message = Response::$statusTexts[$statusCode] ?? 'HTTP error';

        $response = new ApiErrorResponse($statusCode, $errorCode, $message);
        $response->headers->add($exception->getHeaders());

        return $response;
    }
}
