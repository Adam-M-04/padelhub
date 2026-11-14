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
        private RankingCalculator $rankingCalculator,
    ) {
    }

    public function getList(Uuid $tournamentId): array
    {
        return $this->roundRepository->findWithMatches($this->getPublishedTournament($tournamentId));
    }

    public function getRanking(Uuid $tournamentId): array
    {
        $rounds = $this->roundRepository->findWithMatches($this->getPublishedTournament($tournamentId));
        $ranking = $this->rankingCalculator->calculate($this->allMatches($rounds));

        foreach ($ranking as $index => $row) {
            $ranking[$index] = ['position' => $index + 1, ...$row];
        }

        return $ranking;
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

            $rounds = $this->closeCurrentRound($tournament);
            $currentRound = end($rounds);
            $previousMatches = $this->allMatches($rounds);

            $players = [];
            foreach ($currentRound->getMatches() as $match) {
                $players = [...$players, ...$match->getPlayers()];
            }

            $orderedPlayers = match ($tournament->getFormat()) {
                TournamentFormat::AMERICANO => $this->americanoPairing->arrange($players, $previousMatches),
                TournamentFormat::MEXICANO => $this->arrangeMexicano($previousMatches),
            };

            return $this->createRound($tournament, $currentRound->getNumber() + 1, $orderedPlayers);
        });
    }

    public function closeCurrentRound(Tournament $tournament): array
    {
        $rounds = $this->roundRepository->findWithMatches($tournament);
        $currentRound = end($rounds);

        foreach ($currentRound->getMatches() as $match) {
            if (!$match->hasScore()) {
                throw new ConflictException('All matches of the current round must have results', 'ROUND_NOT_FINISHED');
            }
        }

        $currentRound->setStatus(RoundStatus::CLOSED);

        return $rounds;
    }

    private function getPublishedTournament(Uuid $tournamentId): Tournament
    {
        $tournament = $this->entityManager->find(Tournament::class, $tournamentId);

        if ($tournament === null || $tournament->getStatus() === TournamentStatus::DRAFT) {
            throw new NotFoundException('Tournament');
        }

        return $tournament;
    }

    private function allMatches(array $rounds): array
    {
        $matches = [];
        foreach ($rounds as $round) {
            $matches = [...$matches, ...$round->getMatches()->toArray()];
        }

        return $matches;
    }

    private function arrangeMexicano(array $previousMatches): array
    {
        $rankedPlayers = array_column($this->rankingCalculator->calculate($previousMatches), 'player');
        $orderedPlayers = [];

        foreach (array_chunk($rankedPlayers, 4) as [$first, $second, $third, $fourth]) {
            $orderedPlayers = [...$orderedPlayers, $first, $fourth, $second, $third];
        }

        return $orderedPlayers;
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
