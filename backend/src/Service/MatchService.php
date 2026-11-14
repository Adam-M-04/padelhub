<?php

namespace App\Service;

use App\Dto\ScoreRequest;
use App\Entity\GameMatch;
use App\Entity\User;
use App\Enum\RoundStatus;
use App\Enum\TournamentStatus;
use App\Exception\ApiException;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

class MatchService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function saveScore(Uuid $id, ScoreRequest $request, User $user): GameMatch
    {
        $match = $this->entityManager->find(GameMatch::class, $id);

        if ($match === null) {
            throw new NotFoundException('Match');
        }

        if (!$user->isAdmin() && !$match->hasPlayer($user)) {
            throw new ApiException('Only players of this match can enter the score', 403, 'FORBIDDEN');
        }

        $round = $match->getRound();

        if ($round->getTournament()->getStatus() !== TournamentStatus::IN_PROGRESS) {
            throw new ConflictException('Tournament is not in progress', 'TOURNAMENT_NOT_IN_PROGRESS');
        }

        if ($round->getStatus() === RoundStatus::CLOSED && !$user->isAdmin()) {
            throw new ConflictException('Round is closed, only an admin can change the score', 'ROUND_CLOSED');
        }

        $pointsPerMatch = $round->getTournament()->getPointsPerMatch();

        if ($request->team1Score + $request->team2Score !== $pointsPerMatch) {
            throw new ValidationException([[
                'field' => 'score',
                'message' => sprintf('The sum of points must be equal to %d.', $pointsPerMatch),
            ]]);
        }

        try {
            $this->entityManager->lock($match, LockMode::OPTIMISTIC, $request->version);
            $match->setScore($request->team1Score, $request->team2Score);
            $this->entityManager->flush();
        } catch (OptimisticLockException) {
            throw new ConflictException('The score was changed by someone else, refresh and try again', 'MATCH_VERSION_CONFLICT');
        }

        return $match;
    }
}
