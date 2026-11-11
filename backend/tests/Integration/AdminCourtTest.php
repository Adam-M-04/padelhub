<?php

namespace App\Tests\Integration;

use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentFormat;

class AdminCourtTest extends ApiTestCase
{
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
    }

    public function testAdminCanCreateCourt(): void
    {
        $this->jsonRequest('POST', '/api/admin/courts', ['name' => ' Kort 1 '], $this->adminToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        $this->assertSame('Kort 1', $data['name']);
        $this->assertTrue($data['active']);
    }

    public function testCourtNameIsRequired(): void
    {
        $this->jsonRequest('POST', '/api/admin/courts', ['name' => ''], $this->adminToken);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('name', $this->responseData()['error']['details'][0]['field']);
    }

    public function testCourtNameMustBeUnique(): void
    {
        $this->createCourt('Kort 1');

        $this->jsonRequest('POST', '/api/admin/courts', ['name' => 'kort 1'], $this->adminToken);

        $this->assertErrorCode(409, 'COURT_NAME_TAKEN');
    }

    public function testListIsSortedByName(): void
    {
        $this->createCourt('Kort B');
        $this->createCourt('Kort A');

        $this->jsonRequest('GET', '/api/admin/courts', null, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Kort A', 'Kort B'], array_column($this->responseData(), 'name'));
    }

    public function testAdminCanDeactivateCourt(): void
    {
        $id = $this->createCourt('Kort 1');

        $this->jsonRequest('PUT', '/api/admin/courts/' . $id, ['name' => 'Kort 1', 'active' => false], $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->responseData()['active']);

        $this->jsonRequest('GET', '/api/admin/courts/' . $id, null, $this->adminToken);
        $this->assertFalse($this->responseData()['active']);
    }

    public function testAdminCanDeleteCourt(): void
    {
        $id = $this->createCourt('Kort 1');

        $this->jsonRequest('DELETE', '/api/admin/courts/' . $id, null, $this->adminToken);
        $this->assertResponseStatusCodeSame(204);

        $this->jsonRequest('GET', '/api/admin/courts/' . $id, null, $this->adminToken);
        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testCourtUsedInTournamentCannotBeDeleted(): void
    {
        $court = $this->addCourt('Kort 1');
        $tournament = new Tournament();
        $tournament->setName('Americano');
        $tournament->setFormat(TournamentFormat::AMERICANO);
        $tournament->setStartsAt(new \DateTimeImmutable('+7 days'));
        $tournament->setMaxPlayers(4);
        $tournament->setCourts([$court]);
        $this->entityManager->persist($tournament);
        $this->entityManager->flush();

        $this->jsonRequest('DELETE', '/api/admin/courts/' . $court->getId(), null, $this->adminToken);

        $this->assertErrorCode(409, 'COURT_IN_USE');
    }

    public function testUnknownCourtReturns404(): void
    {
        $this->jsonRequest('PUT', '/api/admin/courts/999999', ['name' => 'Kort 1'], $this->adminToken);

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testPlayerCannotManageCourts(): void
    {
        $playerToken = $this->createToken($this->createUser('gracz@example.com'));

        $this->jsonRequest('GET', '/api/admin/courts', null, $playerToken);
        $this->assertErrorCode(403, 'FORBIDDEN');

        $this->jsonRequest('POST', '/api/admin/courts', ['name' => 'Kort 1'], $playerToken);
        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    public function testCourtsRequireLogin(): void
    {
        $this->jsonRequest('GET', '/api/admin/courts');

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    private function createCourt(string $name): int
    {
        $this->jsonRequest('POST', '/api/admin/courts', ['name' => $name], $this->adminToken);

        return $this->responseData()['id'];
    }
}
