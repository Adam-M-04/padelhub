<?php

namespace App\Tests\Integration;

use App\Entity\User;

class MatchScoreTest extends ApiTestCase
{
    private string $adminToken;
    private string $tournamentId;
    private array $players = [];
    private array $match;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $this->tournamentId = (string) $this->createOpenTournament()->getId();

        for ($i = 1; $i <= 4; $i++) {
            $player = $this->createUser('gracz' . $i . '@example.com');
            $this->players[(string) $player->getId()] = $player;
            $this->joinTournament($this->tournamentId, $player);
        }

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);
        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');
        $this->match = $this->responseData()[0]['matches'][0];
    }

    public function testPlayerOfMatchCanEnterScore(): void
    {
        $this->saveScore($this->playerToken(0), 15, 9, 1);

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame(15, $data['team1Score']);
        $this->assertSame(9, $data['team2Score']);
        $this->assertSame(2, $data['version']);
    }

    public function testSumMustBeEqualToPointsPerMatch(): void
    {
        $this->saveScore($this->playerToken(0), 15, 10, 1);

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame('score', $this->responseData()['error']['details'][0]['field']);
    }

    public function testScoreFieldsAreRequired(): void
    {
        $this->jsonRequest('PUT', $this->scoreUrl(), ['team1Score' => -1], $this->playerToken(0));

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $fields = array_column($this->responseData()['error']['details'], 'field');
        $this->assertSame(['team1Score', 'team2Score', 'version'], $fields);
    }

    public function testOtherPlayerCannotEnterScore(): void
    {
        $outsider = $this->createToken($this->createUser('obcy@example.com'));

        $this->saveScore($outsider, 12, 12, 1);

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    public function testAdminCanEnterScore(): void
    {
        $this->saveScore($this->adminToken, 12, 12, 1);

        $this->assertResponseIsSuccessful();
    }

    public function testOutdatedVersionReturnsConflict(): void
    {
        $this->saveScore($this->playerToken(0), 15, 9, 1);
        $this->saveScore($this->playerToken(2), 9, 15, 1);

        $this->assertErrorCode(409, 'MATCH_VERSION_CONFLICT');

        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');
        $this->assertSame(15, $this->responseData()[0]['matches'][0]['team1Score']);
    }

    public function testOnlyAdminCanChangeScoreAfterRoundIsClosed(): void
    {
        $this->saveScore($this->playerToken(0), 15, 9, 1);
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/rounds', null, $this->adminToken);

        $this->saveScore($this->playerToken(0), 16, 8, 2);
        $this->assertErrorCode(409, 'ROUND_CLOSED');

        $this->saveScore($this->adminToken, 16, 8, 2);
        $this->assertResponseIsSuccessful();
        $this->assertSame(16, $this->responseData()['team1Score']);
    }

    public function testScoreRequiresLogin(): void
    {
        $this->jsonRequest('PUT', $this->scoreUrl(), ['team1Score' => 12, 'team2Score' => 12, 'version' => 1]);

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    public function testUnknownMatchReturns404(): void
    {
        $this->jsonRequest('PUT', '/api/matches/019a6b2e-7c4d-7e2f-9a1b-3c5d7e9f1a2b/score', [
            'team1Score' => 12,
            'team2Score' => 12,
            'version' => 1,
        ], $this->adminToken);

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    private function saveScore(string $token, int $team1Score, int $team2Score, int $version): void
    {
        $this->jsonRequest('PUT', $this->scoreUrl(), [
            'team1Score' => $team1Score,
            'team2Score' => $team2Score,
            'version' => $version,
        ], $token);
    }

    private function scoreUrl(): string
    {
        return '/api/matches/' . $this->match['id'] . '/score';
    }

    private function playerToken(int $index): string
    {
        $players = [...$this->match['team1'], ...$this->match['team2']];

        return $this->createToken($this->players[$players[$index]['id']]);
    }
}
