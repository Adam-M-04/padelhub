<?php

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\Round;
use App\Entity\Tournament;
use App\Enum\TournamentStatus;
use App\Exception\NotFoundException;
use App\Repository\RoundRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class RoundService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoundRepository $roundRepository,
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
