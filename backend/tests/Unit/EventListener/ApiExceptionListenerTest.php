<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ApiExceptionListenerTest extends TestCase
{
    public function testConflictException(): void
    {
        $response = $this->handle(new ConflictException('Email is already taken', 'EMAIL_TAKEN'));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            ['error' => ['code' => 'EMAIL_TAKEN', 'message' => 'Email is already taken']],
            $this->decode($response),
        );
    }

    public function testNotFoundException(): void
    {
        $response = $this->handle(new NotFoundException('Court'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(
            ['error' => ['code' => 'NOT_FOUND', 'message' => 'Court not found']],
            $this->decode($response),
        );
    }

    public function testValidationExceptionHasDetails(): void
    {
        $details = [['field' => 'team1Score', 'message' => 'Sum of points must be 24']];
        $response = $this->handle(new ValidationException($details));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('VALIDATION_ERROR', $this->decode($response)['error']['code']);
        $this->assertSame($details, $this->decode($response)['error']['details']);
    }

    public function testFailedPayloadValidationReturns400WithFields(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should not be blank.', null, [], null, 'email', ''),
            new ConstraintViolation('This value is too short.', null, [], null, 'password', 'abc'),
        ]);
        $exception = new HttpException(422, 'Validation failed', new ValidationFailedException(null, $violations));

        $response = $this->handle($exception);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            [
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => [
                        ['field' => 'email', 'message' => 'This value should not be blank.'],
                        ['field' => 'password', 'message' => 'This value is too short.'],
                    ],
                ],
            ],
            $this->decode($response),
        );
    }

    public function testUnknownFieldsReturn400(): void
    {
        $response = $this->handle(new ExtraAttributesException(['roles']));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            [['field' => 'roles', 'message' => 'This field is not allowed.']],
            $this->decode($response)['error']['details'],
        );
    }

    public function testInvalidJson(): void
    {
        $exception = new BadRequestHttpException('Request payload contains invalid "json" data.', new NotEncodableValueException());

        $response = $this->handle($exception);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('INVALID_JSON', $this->decode($response)['error']['code']);
    }

    public function testAccessDenied(): void
    {
        $response = $this->handle(new AccessDeniedHttpException('Access Denied.'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            ['error' => ['code' => 'FORBIDDEN', 'message' => 'Forbidden']],
            $this->decode($response),
        );
    }

    public function testMethodNotAllowedKeepsAllowHeader(): void
    {
        $response = $this->handle(new MethodNotAllowedHttpException(['GET', 'POST']));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->headers->get('Allow'));
    }

    public function testUnexpectedErrorDoesNotShowItsMessage(): void
    {
        $response = $this->handle(new \RuntimeException('SQLSTATE[HY000] secret details'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            ['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Unexpected error']],
            $this->decode($response),
        );
    }

    public function testRequestsOutsideApiAreSkipped(): void
    {
        $event = $this->createEvent(new \RuntimeException('Error'), '/');

        (new ApiExceptionListener())($event);

        $this->assertNull($event->getResponse());
    }

    private function handle(\Throwable $exception): Response
    {
        $event = $this->createEvent($exception, '/api/test');

        (new ApiExceptionListener())($event);

        return $event->getResponse();
    }

    private function createEvent(\Throwable $exception, string $path): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    private function decode(Response $response): array
    {
        return json_decode($response->getContent(), true);
    }
}
