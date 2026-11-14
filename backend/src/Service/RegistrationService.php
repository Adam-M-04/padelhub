<?php

namespace App\Service;

use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\RegistrationStatus;
use App\Enum\TournamentStatus;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Message\PlayerPromoted;
use App\Repository\RegistrationRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RegistrationRepository $registrationRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function getList(Uuid $tournamentId): array
    {
        $tournament = $this->entityManager->find(Tournament::class, $tournamentId);

        if ($tournament === null || $tournament->getStatus() === TournamentStatus::DRAFT) {
            throw new NotFoundException('Tournament');
        }

        return [
            'registered' => $this->registrationRepository->findByStatus($tournament, RegistrationStatus::REGISTERED),
            'reserve' => $this->registrationRepository->findByStatus($tournament, RegistrationStatus::RESERVE),
        ];
    }

    public function register(Uuid $tournamentId, User $player): Registration
    {
        return $this->entityManager->wrapInTransaction(function () use ($tournamentId, $player) {
            $tournament = $this->lockOpenTournament($tournamentId);
            $oldRegistration = $this->registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player]);

            if ($oldRegistration !== null && $oldRegistration->getStatus() !== RegistrationStatus::WITHDRAWN) {
                throw new ConflictException('You are already registered for this tournament', 'ALREADY_REGISTERED');
            }

            if ($oldRegistration !== null) {
                $this->entityManager->remove($oldRegistration);
                $this->entityManager->flush();
            }

            $registration = new Registration($tournament, $player);
            $this->entityManager->persist($registration);

            $registeredCount = $this->registrationRepository->countByStatus($tournament, RegistrationStatus::REGISTERED);

            if ($registeredCount < $tournament->getMaxPlayers()) {
                $registration->setStatus(RegistrationStatus::REGISTERED);
            } else {
                $registration->setStatus(RegistrationStatus::RESERVE);
            }

            return $registration;
        });
    }

    public function withdraw(Uuid $tournamentId, User $player): void
    {
        $this->entityManager->wrapInTransaction(function () use ($tournamentId, $player) {
            $tournament = $this->lockOpenTournament($tournamentId);
            $registration = $this->registrationRepository->findOneBy(['tournament' => $tournament, 'player' => $player]);

            if ($registration === null || $registration->getStatus() === RegistrationStatus::WITHDRAWN) {
                throw new NotFoundException('Registration');
            }

            $registration->setStatus(RegistrationStatus::WITHDRAWN);
            $this->entityManager->flush();

            $this->fillFreeSpots($tournament);
        });
    }

    public function fillFreeSpots(Tournament $tournament): void
    {
        $registeredCount = $this->registrationRepository->countByStatus($tournament, RegistrationStatus::REGISTERED);
        $freeSpots = $tournament->getMaxPlayers() - $registeredCount;

        if ($freeSpots <= 0) {
            return;
        }

        $reserves = $this->registrationRepository->findByStatus($tournament, RegistrationStatus::RESERVE);
        $promoted = array_slice($reserves, 0, $freeSpots);

        foreach ($promoted as $registration) {
            $registration->setStatus(RegistrationStatus::REGISTERED);
            $this->messageBus->dispatch(new PlayerPromoted($registration->getId()));
        }
    }

    private function lockOpenTournament(Uuid $tournamentId): Tournament
    {
        $tournament = $this->entityManager->find(Tournament::class, $tournamentId, LockMode::PESSIMISTIC_WRITE);

        if ($tournament === null || $tournament->getStatus() === TournamentStatus::DRAFT) {
            throw new NotFoundException('Tournament');
        }

        if ($tournament->getStatus() !== TournamentStatus::OPEN) {
            throw new ConflictException('Registration for this tournament is closed', 'REGISTRATION_CLOSED');
        }

        return $tournament;
    }
}
