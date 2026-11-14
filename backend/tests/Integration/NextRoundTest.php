<?php

namespace App\Tests\Integration;

use App\Entity\User;

class NextRoundTest extends ApiTestCase
{
    private string $adminToken;
    private string $tournamentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $this->tournamentId = (string) $this->createOpenTournament(8)->getId();

        for ($i = 1; $i <= 8; $i++) {
            $this->joinTournament($this->tournamentId, $this->createUser('gracz' . $i . '@example.com'));
        }
    }

    public function testNextRoundRequiresAllResults(): void
    {
        $this->startTournament();
        $this->setAllScores();
        $this->entityManager->getConnection()->executeStatement('UPDATE game_matches SET team1_score = NULL, team2_score = NULL LIMIT 1');

        $this->createNextRound();

        $this->assertErrorCode(409, 'ROUND_NOT_FINISHED');
    }

    public function testAdminCanCreateNextAmericanoRound(): void
    {
        $this->startTournament();
        $this->setAllScores();

        $this->createNextRound();

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(2, $this->responseData()['number']);

        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');
        $rounds = $this->responseData();
        $this->assertSame(['CLOSED', 'IN_PROGRESS'], array_column($rounds, 'status'));
        $this->assertCount(2, $rounds[1]['matches']);
        $this->assertSame($this->playerIds($rounds[0]), $this->playerIds($rounds[1]));
        $this->assertSame([], array_intersect($this->pairs($rounds[0]), $this->pairs($rounds[1])));
    }

    public function testTournamentMustBeInProgress(): void
    {
        $this->createNextRound();

        $this->assertErrorCode(409, 'TOURNAMENT_NOT_IN_PROGRESS');
    }

    public function testPlayerCannotCreateRound(): void
    {
        $this->startTournament();
        $playerToken = $this->createToken($this->createUser('gracz@example.com'));

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/rounds', null, $playerToken);

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    private function startTournament(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);
    }

    private function createNextRound(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/rounds', null, $this->adminToken);
    }

    private function setAllScores(): void
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE game_matches SET team1_score = 14, team2_score = 10');
    }

    private function playerIds(array $round): array
    {
        $ids = [];
        foreach ($round['matches'] as $match) {
            foreach ([...$match['team1'], ...$match['team2']] as $player) {
                $ids[] = $player['id'];
            }
        }
        sort($ids);

        return $ids;
    }

    private function pairs(array $round): array
    {
        $pairs = [];
        foreach ($round['matches'] as $match) {
            foreach ([$match['team1'], $match['team2']] as $team) {
                $ids = array_column($team, 'id');
                sort($ids);
                $pairs[] = implode('|', $ids);
            }
        }

        return $pairs;
    }
}
