<?php

namespace App\Tests\Integration;

use App\Entity\User;

class FinishTournamentTest extends ApiTestCase
{
    private string $adminToken;
    private string $tournamentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $this->tournamentId = (string) $this->createOpenTournament()->getId();

        for ($i = 1; $i <= 4; $i++) {
            $this->joinTournament($this->tournamentId, $this->createUser('gracz' . $i . '@example.com'));
        }

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);
    }

    public function testTournamentCannotFinishWithoutResults(): void
    {
        $this->finish();

        $this->assertErrorCode(409, 'ROUND_NOT_FINISHED');
    }

    public function testAdminCanFinishTournament(): void
    {
        $this->saveScore(14, 10);

        $this->finish();

        $this->assertResponseIsSuccessful();
        $this->assertSame('FINISHED', $this->responseData()['status']);

        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');
        $this->assertSame(['CLOSED'], array_column($this->responseData(), 'status'));
    }

    public function testFinishedTournamentIsLocked(): void
    {
        $this->saveScore(14, 10);
        $this->finish();

        $this->finish();
        $this->assertErrorCode(409, 'TOURNAMENT_NOT_IN_PROGRESS');

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/rounds', null, $this->adminToken);
        $this->assertErrorCode(409, 'TOURNAMENT_NOT_IN_PROGRESS');

        $this->saveScore(10, 14);
        $this->assertErrorCode(409, 'TOURNAMENT_NOT_IN_PROGRESS');
    }

    public function testPlayerCannotFinishTournament(): void
    {
        $playerToken = $this->createToken($this->createUser('gracz@example.com'));

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/finish', null, $playerToken);

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    private function finish(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/finish', null, $this->adminToken);
    }

    private function saveScore(int $team1Score, int $team2Score): void
    {
        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');
        $match = $this->responseData()[0]['matches'][0];

        $this->jsonRequest('PUT', '/api/matches/' . $match['id'] . '/score', [
            'team1Score' => $team1Score,
            'team2Score' => $team2Score,
            'version' => $match['version'],
        ], $this->adminToken);
    }
}
