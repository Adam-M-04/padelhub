<?php

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\Round;
use App\Entity\Tournament;
use App\Enum\RoundStatus;
use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Repository\RoundRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class RoundService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoundRepository $roundRepository,
        private AmericanoPairing $americanoPairing,
    ) {
    }

    public function getList(Uuid $tournamentId): array
    {
        $tournament = $this->entityManager->find(Tournament::class, $tournamentId);

        if ($tournament === null || $tournament->getStatus() === TournamentStatus::DRAFT) {
            throw new NotFoundException('Tournament');
        }

        return $this->roundRepository->findWithMatches($tournament);
    }

    public function createFirstRound(Tournament $tournament, array $players): Round
    {
        shuffle($players);

        return $this->createRound($tournament, 1, $players);
    }

    public function createNextRound(Uuid $tournamentId): Round
    {
        return $this->entityManager->wrapInTransaction(function () use ($tournamentId) {
            $tournament = $this->entityManager->find(Tournament::class, $tournamentId, LockMode::PESSIMISTIC_WRITE);

            if ($tournament === null) {
                throw new NotFoundException('Tournament');
            }

            if ($tournament->getStatus() !== TournamentStatus::IN_PROGRESS) {
                throw new ConflictException('Tournament is not in progress', 'TOURNAMENT_NOT_IN_PROGRESS');
            }

            $rounds = $this->roundRepository->findWithMatches($tournament);
            $currentRound = end($rounds);

            foreach ($currentRound->getMatches() as $match) {
                if (!$match->hasScore()) {
                    throw new ConflictException('All matches of the current round must have results', 'ROUND_NOT_FINISHED');
                }
            }

            $previousMatches = [];
            foreach ($rounds as $round) {
                $previousMatches = [...$previousMatches, ...$round->getMatches()->toArray()];
            }

            $players = [];
            foreach ($currentRound->getMatches() as $match) {
                $players = [...$players, ...$match->getPlayers()];
            }

            if ($tournament->getFormat() !== TournamentFormat::AMERICANO) {
                throw new ConflictException('Next rounds are available only for Americano for now', 'NOT_SUPPORTED');
            }

            $currentRound->setStatus(RoundStatus::CLOSED);
            $orderedPlayers = $this->americanoPairing->arrange($players, $previousMatches);

            return $this->createRound($tournament, $currentRound->getNumber() + 1, $orderedPlayers);
        });
    }

    private function createRound(Tournament $tournament, int $number, array $players): Round
    {
        $round = new Round($tournament, $number);
        $courts = $tournament->getCourts()->toArray();

        foreach (array_chunk($players, 4) as $index => $group) {
            $round->addMatch(new GameMatch($round, $courts[$index], [$group[0], $group[1]], [$group[2], $group[3]]));
        }

        $this->entityManager->persist($round);

        return $round;
    }
}
