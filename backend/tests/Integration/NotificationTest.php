<?php

namespace App\Tests\Integration;

use App\Entity\Registration;
use App\Entity\Round;
use App\Entity\User;
use App\Message\PlayerPromoted;
use App\Message\RoundCreated;
use App\MessageHandler\PlayerPromotedHandler;
use App\MessageHandler\RoundCreatedHandler;
use Symfony\Component\Mime\Email;

class NotificationTest extends ApiTestCase
{
    private string $adminToken;
    private string $tournamentId;
    private array $players = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->createToken($this->createUser('admin@example.com', [User::ROLE_ADMIN]));
        $this->tournamentId = (string) $this->createOpenTournament()->getId();

        for ($i = 1; $i <= 5; $i++) {
            $player = $this->createUser('gracz' . $i . '@example.com');
            $player->setFirstName('Gracz' . $i);
            $this->entityManager->flush();
            $this->players[$i] = $player;
            $this->joinTournament($this->tournamentId, $player);
        }
    }

    public function testStartSendsRoundCreatedMessage(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(RoundCreated::class, $messages[0]);
        $this->assertSame($this->findRound()->getId(), $messages[0]->roundId);
    }

    public function testWithdrawSendsPlayerPromotedMessage(): void
    {
        $this->jsonRequest('DELETE', '/api/tournaments/' . $this->tournamentId . '/registration', null, $this->createToken($this->players[1]));

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(PlayerPromoted::class, $messages[0]);
        $this->assertSame($this->findRegistration($this->players[5])->getId(), $messages[0]->registrationId);
    }

    public function testRoundEmailContainsCourtPartnerAndOpponents(): void
    {
        $this->jsonRequest('POST', '/api/admin/tournaments/' . $this->tournamentId . '/start', null, $this->adminToken);
        $round = $this->findRound();
        $match = $round->getMatches()->first();
        [$player, $partner] = $match->getTeam1();
        [$opponent1, $opponent2] = $match->getTeam2();

        static::getContainer()->get(RoundCreatedHandler::class)(new RoundCreated($round->getId()));

        $this->assertEmailCount(4);
        $email = $this->emailTo($player->getEmail());
        $this->assertSame('Runda 1 – Americano', $email->getSubject());
        $this->assertEmailTextBodyContains($email, 'Kort: ' . $match->getCourt()->getName());
        $this->assertEmailTextBodyContains($email, 'Partner: ' . $partner->getFirstName());
        $this->assertEmailTextBodyContains($email, $opponent1->getFirstName());
        $this->assertEmailTextBodyContains($email, $opponent2->getFirstName());
    }

    public function testPromotedPlayerGetsEmail(): void
    {
        $this->jsonRequest('DELETE', '/api/tournaments/' . $this->tournamentId . '/registration', null, $this->createToken($this->players[1]));
        $registration = $this->findRegistration($this->players[5]);

        static::getContainer()->get(PlayerPromotedHandler::class)(new PlayerPromoted($registration->getId()));

        $this->assertEmailCount(1);
        $email = $this->emailTo('gracz5@example.com');
        $this->assertSame('Masz miejsce w turnieju Americano', $email->getSubject());
        $this->assertEmailTextBodyContains($email, 'Cześć Gracz5!');
    }

    public function testNoEmailWhenPromotedPlayerAlreadyWithdrew(): void
    {
        $this->jsonRequest('DELETE', '/api/tournaments/' . $this->tournamentId . '/registration', null, $this->createToken($this->players[1]));
        $this->jsonRequest('DELETE', '/api/tournaments/' . $this->tournamentId . '/registration', null, $this->createToken($this->players[5]));
        $registration = $this->findRegistration($this->players[5]);

        static::getContainer()->get(PlayerPromotedHandler::class)(new PlayerPromoted($registration->getId()));

        $this->assertEmailCount(0);
    }

    private function sentMessages(): array
    {
        $envelopes = static::getContainer()->get('messenger.transport.async')->getSent();

        return array_map(fn ($envelope) => $envelope->getMessage(), $envelopes);
    }

    private function findRound(): Round
    {
        return $this->entityManager->getRepository(Round::class)->findOneBy(['tournament' => $this->tournamentId]);
    }

    private function findRegistration(User $player): Registration
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(Registration::class)->findOneBy([
            'tournament' => $this->tournamentId,
            'player' => $player->getId(),
        ]);
    }

    private function emailTo(string $address): Email
    {
        foreach ($this->getMailerMessages() as $email) {
            if ($email->getTo()[0]->getAddress() === $address) {
                return $email;
            }
        }

        $this->fail('No email sent to ' . $address);
    }
}
