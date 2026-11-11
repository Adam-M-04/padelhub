<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener]
class LoginTimingListener
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function __invoke(LoginFailureEvent $event): void
    {
        if (!$event->getException()->getPrevious() instanceof UserNotFoundException) {
            return;
        }

        $credentials = $event->getPassport()?->getBadge(PasswordCredentials::class);

        if ($credentials instanceof PasswordCredentials) {
            $this->passwordHasher->hashPassword(new User(), $credentials->getPassword());
        }
    }
}
