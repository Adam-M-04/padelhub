<?php

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testPlayerCanRegister(): void
    {
        $this->register([
            'email' => 'Jan.Kowalski@Example.com',
            'password' => 'bardzotajne1',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        $this->assertSame('jan.kowalski@example.com', $data['email']);
        $this->assertSame(['ROLE_PLAYER'], $data['roles']);
        $this->assertArrayNotHasKey('password', $data);

        $user = $this->entityManager->getRepository(User::class)->find($data['id']);
        $this->assertNotSame('bardzotajne1', $user->getPassword());
        $this->assertTrue(password_verify('bardzotajne1', $user->getPassword()));
    }

    public function testEmailMustBeUniqueRegardlessOfCase(): void
    {
        $this->register($this->validData('jan@example.com'));
        $this->register($this->validData('JAN@example.com'));

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('EMAIL_TAKEN', $this->responseData()['error']['code']);
    }

    public function testInvalidDataReturnsFieldErrors(): void
    {
        $this->register([
            'email' => 'not-an-email',
            'password' => 'short',
            'firstName' => 'Jan',
            'lastName' => '',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $error = $this->responseData()['error'];
        $this->assertSame('VALIDATION_ERROR', $error['code']);
        $this->assertSame(['email', 'password', 'lastName'], array_column($error['details'], 'field'));
    }

    public function testPlayerCannotSetRoles(): void
    {
        $data = $this->validData('jan@example.com');
        $data['roles'] = ['ROLE_ADMIN'];

        $this->register($data);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('roles', $this->responseData()['error']['details'][0]['field']);
        $this->assertSame(0, $this->entityManager->getRepository(User::class)->count([]));
    }

    public function testInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":');

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('INVALID_JSON', $this->responseData()['error']['code']);
    }

    private function validData(string $email): array
    {
        return [
            'email' => $email,
            'password' => 'bardzotajne1',
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
        ];
    }

    private function register(array $data): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
