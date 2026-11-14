<?php

namespace App\Tests\Integration;

use App\Entity\Tournament;
use App\Enum\TournamentStatus;

class RegistrationTest extends ApiTestCase
{
    private string $tournamentId;
    private array $players = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->tournamentId = (string) $this->createOpenTournament(4)->getId();

        for ($i = 1; $i <= 6; $i++) {
            $this->players[$i] = $this->createUser('gracz' . $i . '@example.com');
        }
    }

    public function testPlayerCanRegister(): void
    {
        $this->joinTournament($this->tournamentId, $this->players[1]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        $this->assertSame('REGISTERED', $data['status']);
        $this->assertSame((string) $this->players[1]->getId(), $data['player']['id']);
        $this->assertArrayNotHasKey('email', $data['player']);
    }

    public function testRegistrationRequiresLogin(): void
    {
        $this->jsonRequest('POST', '/api/tournaments/' . $this->tournamentId . '/registration');

        $this->assertErrorCode(401, 'UNAUTHORIZED');
    }

    public function testPlayerAboveLimitGoesToReserve(): void
    {
        $this->joinPlayers(1, 5);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('RESERVE', $this->responseData()['status']);
        $this->assertSame([1, 2, 3, 4], $this->registeredPlayers());
        $this->assertSame([5], $this->reservePlayers());
    }

    public function testPlayerCannotRegisterTwice(): void
    {
        $this->joinTournament($this->tournamentId, $this->players[1]);
        $this->joinTournament($this->tournamentId, $this->players[1]);

        $this->assertErrorCode(409, 'ALREADY_REGISTERED');
    }

    public function testWithdrawPromotesFirstReserve(): void
    {
        $this->joinPlayers(1, 6);

        $this->withdraw(2);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame([1, 3, 4, 5], $this->registeredPlayers());
        $this->assertSame([6], $this->reservePlayers());
    }

    public function testReserveWithdrawDoesNotPromoteAnyone(): void
    {
        $this->joinPlayers(1, 6);

        $this->withdraw(5);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame([1, 2, 3, 4], $this->registeredPlayers());
        $this->assertSame([6], $this->reservePlayers());
    }

    public function testWithdrawWithoutRegistrationReturns404(): void
    {
        $this->withdraw(1);

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testPlayerRegisteringAgainGoesToEndOfQueue(): void
    {
        $this->joinPlayers(1, 5);
        $this->withdraw(1);

        $this->joinTournament($this->tournamentId, $this->players[1]);

        $this->assertSame('RESERVE', $this->responseData()['status']);
        $this->assertSame([2, 3, 4, 5], $this->registeredPlayers());
        $this->assertSame([1], $this->reservePlayers());
    }

    public function testRegistrationIsClosedAfterStart(): void
    {
        $this->joinTournament($this->tournamentId, $this->players[1]);
        $this->changeStatus(TournamentStatus::IN_PROGRESS);

        $this->joinTournament($this->tournamentId, $this->players[2]);
        $this->assertErrorCode(409, 'REGISTRATION_CLOSED');

        $this->withdraw(1);
        $this->assertErrorCode(409, 'REGISTRATION_CLOSED');
    }

    public function testDraftTournamentIsNotAvailable(): void
    {
        $this->changeStatus(TournamentStatus::DRAFT);

        $this->joinTournament($this->tournamentId, $this->players[1]);
        $this->assertErrorCode(404, 'NOT_FOUND');

        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/registrations');
        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testPublicListHidesEmailsAndWithdrawnPlayers(): void
    {
        $this->joinPlayers(1, 2);
        $this->withdraw(1);

        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/registrations');

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertCount(1, $data['registered']);
        $this->assertSame(['id', 'firstName', 'lastName'], array_keys($data['registered'][0]['player']));
        $this->assertSame([], $data['reserve']);
    }

    private function joinPlayers(int $from, int $to): void
    {
        for ($i = $from; $i <= $to; $i++) {
            $this->joinTournament($this->tournamentId, $this->players[$i]);
        }
    }

    private function withdraw(int $player): void
    {
        $token = $this->createToken($this->players[$player]);
        $this->jsonRequest('DELETE', '/api/tournaments/' . $this->tournamentId . '/registration', null, $token);
    }

    private function changeStatus(TournamentStatus $status): void
    {
        $tournament = $this->entityManager->find(Tournament::class, $this->tournamentId);
        $tournament->setStatus($status);
        $this->entityManager->flush();
    }

    private function registeredPlayers(): array
    {
        return $this->playerNumbers('registered');
    }

    private function reservePlayers(): array
    {
        return $this->playerNumbers('reserve');
    }

    private function playerNumbers(string $list): array
    {
        $this->jsonRequest('GET', '/api/tournaments/' . $this->tournamentId . '/registrations');

        $numbers = [];
        foreach ($this->responseData()[$list] as $registration) {
            foreach ($this->players as $number => $player) {
                if ((string) $player->getId() === $registration['player']['id']) {
                    $numbers[] = $number;
                }
            }
        }

        return $numbers;
    }
}
