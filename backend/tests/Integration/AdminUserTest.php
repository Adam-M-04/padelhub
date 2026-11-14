<?php

namespace App\Tests\Integration;

use App\Entity\User;

class AdminUserTest extends ApiTestCase
{
    public function testAdminSeesUsersSortedByName(): void
    {
        $admin = $this->createNamedUser('admin@example.com', 'Adam', 'Zawadzki', [User::ROLE_ADMIN]);
        $this->createNamedUser('ola@example.com', 'Ola', 'Nowak');
        $this->createNamedUser('anna@example.com', 'Anna', 'Nowak');

        $this->jsonRequest('GET', '/api/admin/users', null, $this->createToken($admin));

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame(['anna@example.com', 'ola@example.com', 'admin@example.com'], array_column($data, 'email'));
        $this->assertSame(['ROLE_ADMIN', 'ROLE_PLAYER'], $data[2]['roles']);
        $this->assertSame(['id', 'email', 'firstName', 'lastName', 'roles'], array_keys($data[0]));
    }

    public function testPlayerCannotSeeUsers(): void
    {
        $player = $this->createUser('gracz@example.com');

        $this->jsonRequest('GET', '/api/admin/users', null, $this->createToken($player));

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    public function testUsersListRequiresLogin(): void
    {
        $this->jsonRequest('GET', '/api/admin/users');

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    private function createNamedUser(string $email, string $firstName, string $lastName, array $roles = []): User
    {
        $user = $this->createUser($email, $roles);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $this->entityManager->flush();

        return $user;
    }
}
