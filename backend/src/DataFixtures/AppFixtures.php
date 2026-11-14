<?php

namespace App\DataFixtures;

use App\Entity\Court;
use App\Entity\GameMatch;
use App\Entity\Registration;
use App\Entity\Round;
use App\Entity\Tournament;
use App\Entity\User;
use App\Enum\RegistrationStatus;
use App\Enum\RoundStatus;
use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const COURTS = [
        ['Kort 1', true],
        ['Kort 2', true],
        ['Kort 3', true],
        ['Kort 4', false],
    ];

    private const PLAYERS = [
        ['Jan', 'Kowalski'],
        ['Anna', 'Nowak'],
        ['Piotr', 'Wiśniewski'],
        ['Katarzyna', 'Wójcik'],
        ['Tomasz', 'Kamiński'],
        ['Magdalena', 'Lewandowska'],
        ['Michał', 'Zieliński'],
        ['Agnieszka', 'Szymańska'],
    ];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire(env: 'FIXTURES_ADMIN_PASSWORD')]
        private string $adminPassword,
        #[Autowire(env: 'FIXTURES_PLAYER_PASSWORD')]
        private string $playerPassword,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->adminPassword === '' || $this->playerPassword === '') {
            throw new \RuntimeException('Set FIXTURES_ADMIN_PASSWORD and FIXTURES_PLAYER_PASSWORD in .env.local');
        }

        $admin = $this->createUser('admin@padelhub.local', 'Admin', 'PadelHub', $this->adminPassword);
        $admin->setRoles([User::ROLE_ADMIN]);
        $manager->persist($admin);

        $players = [];
        foreach (self::PLAYERS as $index => [$firstName, $lastName]) {
            $email = sprintf('gracz%d@padelhub.local', $index + 1);
            $players[] = $this->createUser($email, $firstName, $lastName, $this->playerPassword);
            $manager->persist(end($players));
        }

        $courts = [];
        foreach (self::COURTS as [$name, $active]) {
            $court = new Court();
            $court->setName($name);
            $court->setActive($active);
            $manager->persist($court);
            $courts[] = $court;
        }

        $this->loadTournaments($manager, $players, $courts);

        $manager->flush();
    }

    private function loadTournaments(ObjectManager $manager, array $players, array $courts): void
    {
        $this->createTournament($manager, 'Mexicano dla wszystkich', TournamentFormat::MEXICANO, '+14 days 10:00', TournamentStatus::OPEN, [$courts[0], $courts[1]], []);

        $this->createTournament($manager, 'Wieczorne Americano', TournamentFormat::AMERICANO, '+3 days 18:00', TournamentStatus::OPEN, [$courts[0], $courts[1]], array_slice($players, 1));

        $tournament = $this->createTournament($manager, 'Poranne Americano', TournamentFormat::AMERICANO, 'today 08:00', TournamentStatus::IN_PROGRESS, [$courts[0], $courts[1]], $players);
        $this->createRound($manager, $tournament, 1, RoundStatus::CLOSED, [
            [[$players[0], $players[1]], [$players[2], $players[3]], [15, 9]],
            [[$players[4], $players[5]], [$players[6], $players[7]], [11, 13]],
        ]);
        $this->createRound($manager, $tournament, 2, RoundStatus::IN_PROGRESS, [
            [[$players[0], $players[2]], [$players[4], $players[6]], null],
            [[$players[1], $players[3]], [$players[5], $players[7]], [14, 10]],
        ]);

        $tournament = $this->createTournament($manager, 'Liga Mexicano', TournamentFormat::MEXICANO, 'yesterday 18:00', TournamentStatus::IN_PROGRESS, [$courts[2]], array_slice($players, 4));
        $this->createRound($manager, $tournament, 1, RoundStatus::CLOSED, [
            [[$players[4], $players[5]], [$players[6], $players[7]], [16, 8]],
        ]);
        $this->createRound($manager, $tournament, 2, RoundStatus::IN_PROGRESS, [
            [[$players[4], $players[7]], [$players[5], $players[6]], null],
        ]);

        $tournament = $this->createTournament($manager, 'Americano klubowe', TournamentFormat::AMERICANO, '-30 days 18:00', TournamentStatus::FINISHED, [$courts[0], $courts[1]], $players);
        $this->createRound($manager, $tournament, 1, RoundStatus::CLOSED, [
            [[$players[0], $players[1]], [$players[2], $players[3]], [12, 12]],
            [[$players[4], $players[5]], [$players[6], $players[7]], [17, 7]],
        ]);
        $this->createRound($manager, $tournament, 2, RoundStatus::CLOSED, [
            [[$players[0], $players[2]], [$players[4], $players[6]], [10, 14]],
            [[$players[1], $players[3]], [$players[5], $players[7]], [15, 9]],
        ]);
        $this->createRound($manager, $tournament, 3, RoundStatus::CLOSED, [
            [[$players[0], $players[3]], [$players[4], $players[7]], [13, 11]],
            [[$players[1], $players[2]], [$players[5], $players[6]], [8, 16]],
        ]);

        $tournament = $this->createTournament($manager, 'Mexicano dla zaawansowanych', TournamentFormat::MEXICANO, '-14 days 10:00', TournamentStatus::FINISHED, [$courts[2]], array_slice($players, 0, 4));
        $this->createRound($manager, $tournament, 1, RoundStatus::CLOSED, [
            [[$players[0], $players[1]], [$players[2], $players[3]], [15, 9]],
        ]);
        $this->createRound($manager, $tournament, 2, RoundStatus::CLOSED, [
            [[$players[0], $players[3]], [$players[1], $players[2]], [13, 11]],
        ]);
        $this->createRound($manager, $tournament, 3, RoundStatus::CLOSED, [
            [[$players[0], $players[2]], [$players[1], $players[3]], [10, 14]],
        ]);
    }

    private function createTournament(ObjectManager $manager, string $name, TournamentFormat $format, string $startsAt, TournamentStatus $status, array $courts, array $players): Tournament
    {
        $tournament = new Tournament();
        $tournament->setName($name);
        $tournament->setFormat($format);
        $tournament->setStartsAt(new \DateTimeImmutable($startsAt));
        $tournament->setMaxPlayers(4 * count($courts));
        $tournament->setStatus($status);
        $tournament->setCourts($courts);
        $manager->persist($tournament);

        foreach ($players as $player) {
            $registration = new Registration($tournament, $player);
            $registration->setStatus(RegistrationStatus::REGISTERED);
            $manager->persist($registration);
        }

        return $tournament;
    }

    private function createRound(ObjectManager $manager, Tournament $tournament, int $number, RoundStatus $status, array $matches): void
    {
        $round = new Round($tournament, $number);
        $round->setStatus($status);
        $courts = $tournament->getCourts()->toArray();

        foreach ($matches as $index => [$team1, $team2, $score]) {
            $match = new GameMatch($round, $courts[$index], $team1, $team2);
            if ($score !== null) {
                $match->setScore($score[0], $score[1]);
            }
            $round->addMatch($match);
        }

        $manager->persist($round);
    }

    private function createUser(string $email, string $firstName, string $lastName, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        return $user;
    }
}
