<?php

namespace App\DataFixtures;

use App\Entity\Court;
use App\Entity\User;
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

        foreach (self::PLAYERS as $index => [$firstName, $lastName]) {
            $email = sprintf('gracz%d@padelhub.local', $index + 1);
            $manager->persist($this->createUser($email, $firstName, $lastName, $this->playerPassword));
        }

        foreach (self::COURTS as [$name, $active]) {
            $court = new Court();
            $court->setName($name);
            $court->setActive($active);
            $manager->persist($court);
        }

        $manager->flush();
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
