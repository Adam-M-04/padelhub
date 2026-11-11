<?php

namespace App\Tests\Integration;

use App\Entity\Court;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentStatus;

class AdminTournamentTest extends ApiTestCase
{
    private string $adminToken;
    private Court $court1;
    private Court $court2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $this->court1 = $this->addCourt('Kort 1');
        $this->court2 = $this->addCourt('Kort 2');
    }

    public function testAdminCanCreateTournament(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments', $this->validData(), $this->adminToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        $this->assertSame('Americano piątkowe', $data['name']);
        $this->assertSame('AMERICANO', $data['format']);
        $this->assertSame('DRAFT', $data['status']);
        $this->assertSame(24, $data['pointsPerMatch']);
        $this->assertSame(['Kort 1', 'Kort 2'], array_column($data['courts'], 'name'));
    }

    public function testStartDateIsSavedInUtc(): void
    {
        $data = $this->validData();
        $data['startsAt'] = '2030-06-01T18:00:00+02:00';

        $this->jsonRequest('POST', '/api/admin/tournaments', $data, $this->adminToken);

        $this->assertSame('2030-06-01T16:00:00+00:00', $this->responseData()['startsAt']);
    }

    public function testUnknownFormatIsRejected(): void
    {
        $data = $this->validData();
        $data['format'] = 'PADEL';

        $this->jsonRequest('POST', '/api/admin/tournaments', $data, $this->adminToken);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('format', $this->responseData()['error']['details'][0]['field']);
    }

    public function testValidationOfRequiredFields(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments', [
            'name' => '',
            'format' => 'MEXICANO',
            'startsAt' => '2020-01-01T10:00:00+00:00',
            'maxPlayers' => 2,
            'courtIds' => [],
        ], $this->adminToken);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $fields = array_column($this->responseData()['error']['details'], 'field');
        $this->assertSame(['name', 'startsAt', 'maxPlayers', 'courtIds'], $fields);
    }

    public function testMaxPlayersCannotExceedFourPerCourt(): void
    {
        $data = $this->validData();
        $data['maxPlayers'] = 12;

        $this->jsonRequest('POST', '/api/admin/tournaments', $data, $this->adminToken);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('maxPlayers', $this->responseData()['error']['details'][0]['field']);
    }

    public function testInactiveCourtCannotBeAssigned(): void
    {
        $inactiveCourt = $this->addCourt('Kort 3', false);
        $data = $this->validData();
        $data['courtIds'] = [$this->court1->getId(), $inactiveCourt->getId()];

        $this->jsonRequest('POST', '/api/admin/tournaments', $data, $this->adminToken);

        $this->assertErrorCode(409, 'COURT_INACTIVE');
    }

    public function testUnknownCourtReturnsValidationError(): void
    {
        $data = $this->validData();
        $data['courtIds'] = [$this->court1->getId(), 999999];

        $this->jsonRequest('POST', '/api/admin/tournaments', $data, $this->adminToken);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('courtIds', $this->responseData()['error']['details'][0]['field']);
    }

    public function testAdminCanUpdateTournament(): void
    {
        $id = $this->createTournament();
        $data = $this->validData();
        $data['name'] = 'Mexicano sobotnie';
        $data['format'] = 'MEXICANO';
        $data['maxPlayers'] = 4;
        $data['courtIds'] = [$this->court2->getId()];

        $this->jsonRequest('PUT', '/api/admin/tournaments/' . $id, $data, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $response = $this->responseData();
        $this->assertSame('Mexicano sobotnie', $response['name']);
        $this->assertSame('MEXICANO', $response['format']);
        $this->assertSame(['Kort 2'], array_column($response['courts'], 'name'));
    }

    public function testStartedTournamentCannotBeEdited(): void
    {
        $id = $this->createTournament();
        $this->changeStatus($id, TournamentStatus::IN_PROGRESS);

        $this->jsonRequest('PUT', '/api/admin/tournaments/' . $id, $this->validData(), $this->adminToken);

        $this->assertErrorCode(409, 'TOURNAMENT_NOT_EDITABLE');
    }

    public function testAdminCanOpenDraftTournament(): void
    {
        $id = $this->createTournament();

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/open', null, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertSame('OPEN', $this->responseData()['status']);

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/open', null, $this->adminToken);
        $this->assertErrorCode(409, 'TOURNAMENT_NOT_DRAFT');
    }

    public function testTournamentCannotBeOpenedWhenCourtWasDeactivated(): void
    {
        $id = $this->createTournament();
        $this->court2->setActive(false);
        $this->entityManager->flush();

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/open', null, $this->adminToken);

        $this->assertErrorCode(409, 'COURT_INACTIVE');
    }

    public function testOnlyDraftTournamentCanBeDeleted(): void
    {
        $draftId = $this->createTournament();
        $openId = $this->createTournament();
        $this->changeStatus($openId, TournamentStatus::OPEN);

        $this->jsonRequest('DELETE', '/api/admin/tournaments/' . $draftId, null, $this->adminToken);
        $this->assertResponseStatusCodeSame(204);

        $this->jsonRequest('DELETE', '/api/admin/tournaments/' . $openId, null, $this->adminToken);
        $this->assertErrorCode(409, 'TOURNAMENT_NOT_DRAFT');
    }

    public function testAdminListContainsDrafts(): void
    {
        $this->createTournament();

        $this->jsonRequest('GET', '/api/admin/tournaments', null, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $this->responseData());
    }

    public function testPlayerCannotManageTournaments(): void
    {
        $playerToken = $this->createToken($this->createUser('gracz@example.com'));

        $this->jsonRequest('POST', '/api/admin/tournaments', $this->validData(), $playerToken);

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    private function validData(): array
    {
        return [
            'name' => 'Americano piątkowe',
            'format' => 'AMERICANO',
            'startsAt' => (new \DateTimeImmutable('+7 days'))->format(DATE_ATOM),
            'maxPlayers' => 8,
            'courtIds' => [$this->court1->getId(), $this->court2->getId()],
        ];
    }

    private function createTournament(): int
    {
        $this->jsonRequest('POST', '/api/admin/tournaments', $this->validData(), $this->adminToken);

        return $this->responseData()['id'];
    }

    private function changeStatus(int $id, TournamentStatus $status): void
    {
        $tournament = $this->entityManager->find(Tournament::class, $id);
        $tournament->setStatus($status);
        $this->entityManager->flush();
    }
}
