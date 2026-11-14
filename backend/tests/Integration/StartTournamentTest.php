<?php

namespace App\Tests\Integration;

use App\Entity\Court;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\TournamentStatus;

class StartTournamentTest extends ApiTestCase
{
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
    }

    public function testAdminCanStartTournament(): void
    {
        $id = (string) $this->createOpenTournament(8)->getId();
        $playerIds = $this->joinPlayers($id, 8);

        $this->start($id);

        $this->assertResponseIsSuccessful();
        $this->assertSame('IN_PROGRESS', $this->responseData()['status']);

        $this->jsonRequest('GET', '/api/tournaments/' . $id . '/rounds');
        $this->assertResponseIsSuccessful();
        $rounds = $this->responseData();
        $this->assertCount(1, $rounds);
        $this->assertSame(1, $rounds[0]['number']);
        $this->assertSame('IN_PROGRESS', $rounds[0]['status']);

        $matches = $rounds[0]['matches'];
        $this->assertCount(2, $matches);
        $this->assertCount(2, array_unique(array_column(array_column($matches, 'court'), 'id')));
        $this->assertNull($matches[0]['team1Score']);
        $this->assertSameIds($playerIds, $this->playersInMatches($matches));
    }

    public function testReservePlayersDoNotPlay(): void
    {
        $id = (string) $this->createOpenTournament(4)->getId();
        $playerIds = $this->joinPlayers($id, 6);

        $this->start($id);

        $this->jsonRequest('GET', '/api/tournaments/' . $id . '/rounds');
        $matches = $this->responseData()[0]['matches'];
        $this->assertSameIds(array_slice($playerIds, 0, 4), $this->playersInMatches($matches));
    }

    public function testPlayerCountMustBeMultipleOfFour(): void
    {
        $id = (string) $this->createOpenTournament(8)->getId();
        $this->joinPlayers($id, 6);

        $this->start($id);

        $this->assertErrorCode(409, 'INVALID_PLAYER_COUNT');
        $this->jsonRequest('GET', '/api/tournaments/' . $id);
        $this->assertSame('OPEN', $this->responseData()['status']);
    }

    public function testTournamentWithoutPlayersCannotStart(): void
    {
        $id = (string) $this->createOpenTournament()->getId();

        $this->start($id);

        $this->assertErrorCode(409, 'INVALID_PLAYER_COUNT');
    }

    public function testTournamentCannotStartTwice(): void
    {
        $id = (string) $this->createOpenTournament()->getId();
        $this->joinPlayers($id, 4);

        $this->start($id);
        $this->start($id);

        $this->assertErrorCode(409, 'TOURNAMENT_NOT_OPEN');
    }

    public function testTournamentCannotStartWithInactiveCourt(): void
    {
        $tournament = $this->createOpenTournament();
        $id = (string) $tournament->getId();
        $this->joinPlayers($id, 4);

        $court = $this->entityManager->find(Court::class, $tournament->getCourts()->first()->getId());
        $court->setActive(false);
        $this->entityManager->flush();

        $this->start($id);

        $this->assertErrorCode(409, 'COURT_INACTIVE');
    }

    public function testRegistrationIsClosedAfterStart(): void
    {
        $id = (string) $this->createOpenTournament(8)->getId();
        $this->joinPlayers($id, 4);
        $this->start($id);

        $this->joinTournament($id, $this->createUser('spozniony@example.com'));

        $this->assertErrorCode(409, 'REGISTRATION_CLOSED');
    }

    public function testPlayerCannotStartTournament(): void
    {
        $id = (string) $this->createOpenTournament()->getId();
        $playerToken = $this->createToken($this->createUser('gracz@example.com'));

        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/start', null, $playerToken);

        $this->assertErrorCode(403, 'FORBIDDEN');
    }

    public function testRoundsOfDraftTournamentAreNotPublic(): void
    {
        $tournament = $this->createOpenTournament();
        $tournament = $this->entityManager->find(Tournament::class, $tournament->getId());
        $tournament->setStatus(TournamentStatus::DRAFT);
        $this->entityManager->flush();

        $this->jsonRequest('GET', '/api/tournaments/' . $tournament->getId() . '/rounds');

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    private function start(string $id): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $id . '/start', null, $this->adminToken);
    }

    private function joinPlayers(string $id, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $player = $this->createUser('gracz' . $i . '@example.com');
            $this->joinTournament($id, $player);
            $ids[] = (string) $player->getId();
        }

        return $ids;
    }

    private function playersInMatches(array $matches): array
    {
        $ids = [];
        foreach ($matches as $match) {
            foreach ([...$match['team1'], ...$match['team2']] as $player) {
                $ids[] = $player['id'];
            }
        }

        return $ids;
    }

    private function assertSameIds(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }
}
