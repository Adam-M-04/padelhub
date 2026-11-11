<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\LoginTimingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginTimingListenerTest extends TestCase
{
    public function testPasswordIsHashedWhenUserDoesNotExist(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->once())->method('hashPassword');

        $exception = new BadCredentialsException('Bad credentials.', 0, new UserNotFoundException());
        (new LoginTimingListener($passwordHasher))($this->createEvent($exception));
    }

    public function testNothingHappensWhenPasswordIsWrong(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->expects($this->never())->method('hashPassword');

        (new LoginTimingListener($passwordHasher))($this->createEvent(new BadCredentialsException()));
    }

    private function createEvent(AuthenticationException $exception): LoginFailureEvent
    {
        $passport = new Passport(new UserBadge('jan@example.com'), new PasswordCredentials('zlehaslo123'));

        return new LoginFailureEvent(
            $exception,
            $this->createStub(AuthenticatorInterface::class),
            new Request(),
            null,
            'login',
            $passport,
        );
    }
}
