<?php

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        $user = new User();
        $user->setEmail('jan@example.com');
        $user->setFirstName('Jan');
        $user->setLastName('Kowalski');
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, 'bardzotajne1'));
        $entityManager->persist($user);
        $entityManager->flush();
    }

    public function testLoginReturnsTokenWithEmailAndRoles(): void
    {
        $this->login('JAN@example.com', 'bardzotajne1');

        $this->assertResponseIsSuccessful();
        $token = $this->responseData()['token'];
        $payload = static::getContainer()->get(JWTEncoderInterface::class)->decode($token);
        $this->assertSame('jan@example.com', $payload['email']);
        $this->assertSame(['ROLE_PLAYER'], $payload['roles']);
        $this->assertGreaterThan($payload['iat'], $payload['exp']);
    }

    public function testWrongPasswordAndUnknownEmailGiveTheSameResponse(): void
    {
        $this->login('jan@example.com', 'zlehaslo123');
        $wrongPassword = $this->client->getResponse();

        $this->login('nieznany@example.com', 'bardzotajne1');
        $unknownEmail = $this->client->getResponse();

        $this->assertSame(401, $wrongPassword->getStatusCode());
        $this->assertSame(401, $unknownEmail->getStatusCode());
        $this->assertSame($wrongPassword->getContent(), $unknownEmail->getContent());
        $this->assertSame('INVALID_CREDENTIALS', $this->responseData()['error']['code']);
    }

    public function testLoginWithoutJsonBody(): void
    {
        $this->client->request('POST', '/api/auth/login', ['email' => 'jan@example.com', 'password' => 'bardzotajne1']);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('BAD_REQUEST', $this->responseData()['error']['code']);
    }

    public function testMeReturnsLoggedUser(): void
    {
        $this->login('jan@example.com', 'bardzotajne1');
        $token = $this->responseData()['token'];

        $this->client->request('GET', '/api/auth/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame('jan@example.com', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testMeWithoutToken(): void
    {
        $this->client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('UNAUTHORIZED', $this->responseData()['error']['code']);
    }

    public function testMeWithBasicAuthorizationHeader(): void
    {
        $this->client->request('GET', '/api/auth/me', [], [], ['HTTP_AUTHORIZATION' => 'Basic amFuOmhhc2xv']);

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('UNAUTHORIZED', $this->responseData()['error']['code']);
    }

    public function testMeWithModifiedToken(): void
    {
        $this->login('jan@example.com', 'bardzotajne1');
        [$header, $payload, $signature] = explode('.', $this->responseData()['token']);
        $data = json_decode(base64_decode($payload), true);
        $data['roles'] = ['ROLE_ADMIN'];
        $modifiedPayload = rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        $this->client->request('GET', '/api/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $header . '.' . $modifiedPayload . '.' . $signature,
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('INVALID_TOKEN', $this->responseData()['error']['code']);
    }

    public function testMeWithExpiredToken(): void
    {
        $token = static::getContainer()->get(JWTEncoderInterface::class)->encode([
            'email' => 'jan@example.com',
            'roles' => ['ROLE_PLAYER'],
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ]);

        $this->client->request('GET', '/api/auth/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertSame('TOKEN_EXPIRED', $this->responseData()['error']['code']);
    }

    private function login(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]),
        );
    }

    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
