<?php

namespace App\Tests\Integration;

use App\Entity\Court;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager->createQuery('DELETE FROM App\Entity\Tournament')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Court')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    protected function createUser(string $email, array $roles = [], string $password = 'bardzotajne1'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Jan');
        $user->setLastName('Kowalski');
        $user->setRoles($roles);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function addCourt(string $name, bool $active = true): Court
    {
        $court = new Court();
        $court->setName($name);
        $court->setActive($active);

        $this->entityManager->persist($court);
        $this->entityManager->flush();

        return $court;
    }

    protected function createToken(User $user): string
    {
        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    protected function jsonRequest(string $method, string $url, ?array $data = null, ?string $token = null): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if ($token !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request($method, $url, [], [], $headers, $data === null ? null : json_encode($data));
    }

    protected function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    protected function assertErrorCode(int $statusCode, string $errorCode): void
    {
        $this->assertResponseStatusCodeSame($statusCode);
        $this->assertSame($errorCode, $this->responseData()['error']['code']);
    }
}
