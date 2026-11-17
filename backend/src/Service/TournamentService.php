<?php

namespace App\Service;

use App\Dto\TournamentFilter;
use App\Dto\TournamentRequest;
use App\Entity\Tournament;
use App\Enum\RegistrationStatus;
use App\Enum\TournamentStatus;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\CourtRepository;
use App\Repository\RegistrationRepository;
use App\Repository\TournamentRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class TournamentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentRepository $tournamentRepository,
        private CourtRepository $courtRepository,
        private RegistrationRepository $registrationRepository,
        private RegistrationService $registrationService,
        private RoundService $roundService,
    ) {
    }

    public function getAll(): array
    {
        return $this->tournamentRepository->findAllWithCourts();
    }

    public function getPublished(TournamentFilter $filter): array
    {
        $paginator = $this->tournamentRepository->findPublished($filter);
        $total = count($paginator);

        return [
            'items' => iterator_to_array($paginator),
            'page' => $filter->page,
            'limit' => $filter->limit,
            'total' => $total,
            'pages' => (int) ceil($total / $filter->limit),
        ];
    }

    public function get(Uuid $id): Tournament
    {
        $tournament = $this->tournamentRepository->find($id);

        if ($tournament === null) {
            throw new NotFoundException('Tournament');
        }

        return $tournament;
    }

    public function getPublishedOne(Uuid $id): Tournament
    {
        $tournament = $this->get($id);

        if ($tournament->getStatus() === TournamentStatus::DRAFT) {
            throw new NotFoundException('Tournament');
        }

        return $tournament;
    }

    public function create(TournamentRequest $request): Tournament
    {
        $tournament = new Tournament();
        $this->applyRequest($tournament, $request);

        $this->entityManager->persist($tournament);
        $this->entityManager->flush();

        return $tournament;
    }

    public function update(Uuid $id, TournamentRequest $request): Tournament
    {
        return $this->entityManager->wrapInTransaction(function () use ($id, $request) {
            $tournament = $this->entityManager->find(Tournament::class, $id, LockMode::PESSIMISTIC_WRITE);

            if ($tournament === null) {
                throw new NotFoundException('Tournament');
            }

            if (!in_array($tournament->getStatus(), [TournamentStatus::DRAFT, TournamentStatus::OPEN], true)) {
                throw new ConflictException('Tournament can be edited only before it starts', 'TOURNAMENT_NOT_EDITABLE');
            }

            $registeredCount = $this->registrationRepository->countByStatus($tournament, RegistrationStatus::REGISTERED);

            if ($request->maxPlayers < $registeredCount) {
                throw new ValidationException([[
                    'field' => 'maxPlayers',
                    'message' => sprintf('Max players cannot be lower than the number of registered players (%d).', $registeredCount),
                ]]);
            }

            $this->applyRequest($tournament, $request);
            $this->registrationService->fillFreeSpots($tournament);

            return $tournament;
        });
    }

    public function delete(Uuid $id): void
    {
        $tournament = $this->get($id);

        if ($tournament->getStatus() !== TournamentStatus::DRAFT) {
            throw new ConflictException('Only draft tournaments can be deleted', 'TOURNAMENT_NOT_DRAFT');
        }

        $this->entityManager->remove($tournament);
        $this->entityManager->flush();
    }

    public function open(Uuid $id): Tournament
    {
        $tournament = $this->get($id);

        if ($tournament->getStatus() !== TournamentStatus::DRAFT) {
            throw new ConflictException('Only draft tournaments can be opened', 'TOURNAMENT_NOT_DRAFT');
        }

        $this->checkCourtsAreActive($tournament->getCourts()->toArray());

        $tournament->setStatus(TournamentStatus::OPEN);
        $this->entityManager->flush();

        return $tournament;
    }

    public function start(Uuid $id): Tournament
    {
        return $this->entityManager->wrapInTransaction(function () use ($id) {
            $tournament = $this->entityManager->find(Tournament::class, $id, LockMode::PESSIMISTIC_WRITE);

            if ($tournament === null) {
                throw new NotFoundException('Tournament');
            }

            if ($tournament->getStatus() !== TournamentStatus::OPEN) {
                throw new ConflictException('Only open tournaments can be started', 'TOURNAMENT_NOT_OPEN');
            }

            $this->checkCourtsAreActive($tournament->getCourts()->toArray());

            $registrations = $this->registrationRepository->findByStatus($tournament, RegistrationStatus::REGISTERED);
            $playerCount = count($registrations);

            if ($playerCount === 0 || $playerCount % 4 !== 0) {
                throw new ConflictException(
                    sprintf('Number of players must be a multiple of 4 (currently %d)', $playerCount),
                    'INVALID_PLAYER_COUNT',
                );
            }

            $players = array_map(fn ($registration) => $registration->getPlayer(), $registrations);

            $tournament->setStatus(TournamentStatus::IN_PROGRESS);
            $this->roundService->createFirstRound($tournament, $players);

            return $tournament;
        });
    }

    public function finish(Uuid $id): Tournament
    {
        return $this->entityManager->wrapInTransaction(function () use ($id) {
            $tournament = $this->entityManager->find(Tournament::class, $id, LockMode::PESSIMISTIC_WRITE);

            if ($tournament === null) {
                throw new NotFoundException('Tournament');
            }

            if ($tournament->getStatus() !== TournamentStatus::IN_PROGRESS) {
                throw new ConflictException('Tournament is not in progress', 'TOURNAMENT_NOT_IN_PROGRESS');
            }

            $this->roundService->closeCurrentRound($tournament);
            $tournament->setStatus(TournamentStatus::FINISHED);

            return $tournament;
        });
    }

    private function applyRequest(Tournament $tournament, TournamentRequest $request): void
    {
        $courts = $this->courtRepository->findBy(['id' => $request->courtIds]);

        if (count($courts) !== count($request->courtIds)) {
            throw new ValidationException([['field' => 'courtIds', 'message' => 'One of the courts does not exist.']]);
        }

        $maxAllowed = 4 * count($courts);
        if ($request->maxPlayers > $maxAllowed) {
            throw new ValidationException([[
                'field' => 'maxPlayers',
                'message' => sprintf('Max players cannot be greater than %d (4 per court).', $maxAllowed),
            ]]);
        }

        $this->checkCourtsAreActive($courts);

        $tournament->setName($request->name);
        $tournament->setFormat($request->format);
        $tournament->setStartsAt($request->startsAt);
        $tournament->setPointsPerMatch($request->pointsPerMatch);
        $tournament->setMaxPlayers($request->maxPlayers);
        $tournament->setCourts($courts);
    }

    private function checkCourtsAreActive(array $courts): void
    {
        foreach ($courts as $court) {
            if (!$court->isActive()) {
                throw new ConflictException(sprintf('Court "%s" is inactive', $court->getName()), 'COURT_INACTIVE');
            }
        }
    }
}
