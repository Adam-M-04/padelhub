<?php

namespace App\Tests\Integration;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class LoginTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser('jan@example.com');
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

        $this->assertErrorCode(400, 'BAD_REQUEST');
    }

    public function testMeReturnsLoggedUser(): void
    {
        $this->login('jan@example.com', 'bardzotajne1');
        $token = $this->responseData()['token'];

        $this->jsonRequest('GET', '/api/auth/me', null, $token);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame('jan@example.com', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testMeWithoutToken(): void
    {
        $this->jsonRequest('GET', '/api/auth/me');

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    public function testMeWithBasicAuthorizationHeader(): void
    {
        $this->client->request('GET', '/api/auth/me', [], [], ['HTTP_AUTHORIZATION' => 'Basic amFuOmhhc2xv']);

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    public function testMeWithModifiedToken(): void
    {
        $this->login('jan@example.com', 'bardzotajne1');
        [$header, $payload, $signature] = explode('.', $this->responseData()['token']);
        $data = json_decode(base64_decode($payload), true);
        $data['roles'] = ['ROLE_ADMIN'];
        $modifiedPayload = rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        $this->jsonRequest('GET', '/api/auth/me', null, $header . '.' . $modifiedPayload . '.' . $signature);

        $this->assertErrorCode(401, 'INVALID_TOKEN');
    }

    public function testMeWithExpiredToken(): void
    {
        $token = static::getContainer()->get(JWTEncoderInterface::class)->encode([
            'email' => 'jan@example.com',
            'roles' => ['ROLE_PLAYER'],
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ]);

        $this->jsonRequest('GET', '/api/auth/me', null, $token);

        $this->assertErrorCode(401, 'TOKEN_EXPIRED');
    }

    private function login(string $email, string $password): void
    {
        $this->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => $password]);
    }
}
