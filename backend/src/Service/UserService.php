<?php

namespace App\Service;

use App\Dto\RegisterRequest;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    public function getAll(): array
    {
        return $this->userRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC', 'email' => 'ASC']);
    }

    public function register(RegisterRequest $request): User
    {
        $user = new User();
        $user->setEmail($request->email);
        $user->setFirstName($request->firstName);
        $user->setLastName($request->lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConflictException('Email is already taken', 'EMAIL_TAKEN');
        }

        return $user;
    }
}
