<?php

namespace App\Tests\Integration;

use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentStatus;

class RankingTest extends ApiTestCase
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
    }

    public function testRankingIsEmptyBeforeStart(): void
    {
        $this->jsonRequest('GET', $this->rankingUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->responseData());
    }

    public function testPlayersHaveZeroPointsBeforeResults(): void
    {
        $this->startTournament();

        $this->jsonRequest('GET', $this->rankingUrl());

        $ranking = $this->responseData();
        $this->assertCount(4, $ranking);
        $this->assertSame([1, 2, 3, 4], array_column($ranking, 'position'));
        $this->assertSame([0, 0, 0, 0], array_column($ranking, 'points'));
    }

    public function testRankingIsSortedByPoints(): void
    {
        $this->startTournament();
        $match = $this->firstMatch();
        $this->jsonRequest('PUT', '/api/matches/' . $match['id'] . '/score', [
            'team1Score' => 9,
            'team2Score' => 15,
            'version' => $match['version'],
        ], $this->adminToken);

        $this->jsonRequest('GET', $this->rankingUrl());

        $ranking = $this->responseData();
        $this->assertSame([15, 15, 9, 9], array_column($ranking, 'points'));
        $this->assertSame([1, 1, 0, 0], array_column($ranking, 'wins'));
        $this->assertSame([1, 1, 1, 1], array_column($ranking, 'matches'));
        $this->assertSame(
            array_column($match['team2'], 'id'),
            array_column(array_column(array_slice($ranking, 0, 2), 'player'), 'id'),
        );
        $this->assertSame(['position', 'player', 'points', 'matches', 'wins'], array_keys($ranking[0]));
        $this->assertSame(['id', 'firstName', 'lastName'], array_keys($ranking[0]['player']));
    }

    public function testDraftTournamentHasNoPublicRanking(): void
    {
        $tournament = $this->entityManager->find(Tournament::class, $this->tournamentId);
        $tournament->setStatus(TournamentStatus::DRAFT);
        $this->entityManager->flush();

        $this->jsonRequest('GET', $this->rankingUrl());

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    private function startTournament(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);
    }

    private function firstMatch(): array
    {
        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/rounds');

        return $this->responseData()[0]['matches'][0];
    }

    private function rankingUrl(): string
    {
        return '/api/tournaments/' . $this->tournamentId . '/ranking';
    }
}
