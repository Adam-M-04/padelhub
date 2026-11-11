<?php

namespace App\Tests\Integration;

use App\Entity\User;

class RegisterTest extends ApiTestCase
{
    public function testPlayerCanRegister(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
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
        $this->jsonRequest('POST', '/api/auth/register', $this->validData('jan@example.com'));
        $this->jsonRequest('POST', '/api/auth/register', $this->validData('JAN@example.com'));

        $this->assertErrorCode(409, 'EMAIL_TAKEN');
    }

    public function testInvalidDataReturnsFieldErrors(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'short',
            'firstName' => 'Jan',
            'lastName' => '',
        ]);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $fields = array_column($this->responseData()['error']['details'], 'field');
        $this->assertSame(['email', 'password', 'lastName'], $fields);
    }

    public function testPlayerCannotSetRoles(): void
    {
        $data = $this->validData('jan@example.com');
        $data['roles'] = ['ROLE_ADMIN'];

        $this->jsonRequest('POST', '/api/auth/register', $data);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('roles', $this->responseData()['error']['details'][0]['field']);
        $this->assertSame(0, $this->entityManager->getRepository(User::class)->count([]));
    }

    public function testInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], '{"email":');

        $this->assertErrorCode(400, 'INVALID_JSON');
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
}
