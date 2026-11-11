<?php

namespace App\EventListener;

use App\Http\ApiErrorResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class JwtFailureListener
{
    #[AsEventListener(event: Events::AUTHENTICATION_FAILURE)]
    public function onLoginFailure(AuthenticationFailureEvent $event): void
    {
        $event->setResponse(new ApiErrorResponse(401, 'INVALID_CREDENTIALS', 'Invalid email or password'));
    }

    #[AsEventListener(event: Events::JWT_NOT_FOUND)]
    public function onTokenNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse(new ApiErrorResponse(401, 'UNAUTHORIZED', 'Missing Bearer token'));
    }

    #[AsEventListener(event: Events::JWT_INVALID)]
    public function onTokenInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse(new ApiErrorResponse(401, 'INVALID_TOKEN', 'Invalid token'));
    }

    #[AsEventListener(event: Events::JWT_EXPIRED)]
    public function onTokenExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse(new ApiErrorResponse(401, 'TOKEN_EXPIRED', 'Token expired'));
    }
}
