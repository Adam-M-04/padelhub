<?php

namespace App\Tests\Integration;

use App\Entity\Court;
use App\Entity\Tournament;
use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use Symfony\Component\Uid\Uuid;

class TournamentTest extends ApiTestCase
{
    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();
        $this->court = $this->addCourt('Kort 1');
    }

    public function testPublicListHidesDraftsAndIsSortedByDate(): void
    {
        $this->addTournament('Szkic', '+1 day', TournamentStatus::DRAFT);
        $this->addTournament('Później', '+14 days', TournamentStatus::OPEN);
        $this->addTournament('Wcześniej', '+7 days', TournamentStatus::OPEN);

        $this->jsonRequest('GET', '/api/tournaments');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Wcześniej', 'Później'], array_column($this->responseData(), 'name'));
    }

    public function testPublicDetailsWithCourts(): void
    {
        $tournament = $this->addTournament('Americano', '+7 days', TournamentStatus::OPEN);

        $this->jsonRequest('GET', '/api/tournaments/' . $tournament->getId());

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame('Americano', $data['name']);
        $this->assertSame(['Kort 1'], array_column($data['courts'], 'name'));
    }

    public function testDraftDetailsAreNotPublic(): void
    {
        $tournament = $this->addTournament('Szkic', '+7 days', TournamentStatus::DRAFT);

        $this->jsonRequest('GET', '/api/tournaments/' . $tournament->getId());

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testUnknownTournamentReturns404(): void
    {
        $this->jsonRequest('GET', '/api/tournaments/019a6b2e-7c4d-7e2f-9a1b-3c5d7e9f1a2b');

        $this->assertErrorCode(404, 'NOT_FOUND');
        $this->assertSame('Tournament not found', $this->responseData()['error']['message']);
    }

    public function testNumericIdIsNotAccepted(): void
    {
        $this->jsonRequest('GET', '/api/tournaments/1');

        $this->assertErrorCode(404, 'NOT_FOUND');
    }

    public function testIdIsUuid(): void
    {
        $tournament = $this->addTournament('Americano', '+7 days', TournamentStatus::OPEN);

        $this->jsonRequest('GET', '/api/tournaments');

        $this->assertSame((string) $tournament->getId(), $this->responseData()[0]['id']);
        $this->assertTrue(Uuid::isValid($this->responseData()[0]['id']));
    }

    private function addTournament(string $name, string $startsAt, TournamentStatus $status): Tournament
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setFormat(TournamentFormat::AMERICANO);
        $tournament->setStartsAt(new \DateTimeImmutable($startsAt));
        $tournament->setMaxPlayers(4);
        $tournament->setStatus($status);
        $tournament->setCourts([$this->court]);

        $this->entityManager->persist($tournament);
        $this->entityManager->flush();

        return $tournament;
    }
}
