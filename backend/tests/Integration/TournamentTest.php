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
        $this->assertSame(['Wcześniej', 'Później'], array_column($this->responseData()['items'], 'name'));
    }

    public function testListIsPaginated(): void
    {
        $this->addTournament('Pierwszy', '+1 day', TournamentStatus::OPEN);
        $this->addTournament('Drugi', '+2 days', TournamentStatus::OPEN);
        $this->addTournament('Trzeci', '+3 days', TournamentStatus::OPEN);

        $this->jsonRequest('GET', '/api/tournaments?page=2&limit=2');

        $this->assertResponseIsSuccessful();
        $data = $this->responseData();
        $this->assertSame(['Trzeci'], array_column($data['items'], 'name'));
        $this->assertSame(2, $data['page']);
        $this->assertSame(2, $data['limit']);
        $this->assertSame(3, $data['total']);
        $this->assertSame(2, $data['pages']);
    }

    public function testListCanBeSearchedByName(): void
    {
        $this->addTournament('Poranne Americano', '+1 day', TournamentStatus::OPEN);
        $this->addTournament('Liga Mexicano', '+2 days', TournamentStatus::OPEN);

        $this->jsonRequest('GET', '/api/tournaments?search=poranne');

        $this->assertSame(['Poranne Americano'], array_column($this->responseData()['items'], 'name'));
    }

    public function testListCanBeFilteredByStatusAndFormat(): void
    {
        $this->addTournament('Otwarte Americano', '+1 day', TournamentStatus::OPEN);
        $this->addTournament('Zakończone Americano', '+2 days', TournamentStatus::FINISHED);
        $this->addTournament('Zakończone Mexicano', '+3 days', TournamentStatus::FINISHED, TournamentFormat::MEXICANO);

        $this->jsonRequest('GET', '/api/tournaments?status=FINISHED&format=AMERICANO');

        $this->assertSame(['Zakończone Americano'], array_column($this->responseData()['items'], 'name'));
    }

    public function testInvalidListParametersReturn400(): void
    {
        $this->jsonRequest('GET', '/api/tournaments?limit=100&status=DRAFT');

        $this->assertErrorCode(400, 'VALIDATION_ERROR');
        $this->assertSame(['limit', 'status'], array_column($this->responseData()['error']['details'], 'field'));
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

        $this->assertSame((string) $tournament->getId(), $this->responseData()['items'][0]['id']);
        $this->assertTrue(Uuid::isValid($this->responseData()['items'][0]['id']));
    }

    private function addTournament(string $name, string $startsAt, TournamentStatus $status, TournamentFormat $format = TournamentFormat::AMERICANO): Tournament
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setFormat($format);
        $tournament->setStartsAt(new \DateTimeImmutable($startsAt));
        $tournament->setMaxPlayers(4);
        $tournament->setStatus($status);
        $tournament->setCourts([$this->court]);

        $this->entityManager->persist($tournament);
        $this->entityManager->flush();

        return $tournament;
    }
}
