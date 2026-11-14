<?php

namespace App\Entity;

use App\Enum\RegistrationStatus;
use App\Repository\RegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
#[ORM\Table(name: 'registrations')]
#[ORM\UniqueConstraint(name: 'uniq_registrations_tournament_player', fields: ['tournament', 'player'])]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['registration:read'])]
    #[OA\Property(ref: '#/components/schemas/Player')]
    private ?User $player = null;

    #[ORM\Column(length: 20, enumType: RegistrationStatus::class)]
    #[Groups(['registration:read'])]
    private RegistrationStatus $status = RegistrationStatus::REGISTERED;

    #[ORM\Column]
    #[Groups(['registration:read'])]
    private \DateTimeImmutable $registeredAt;

    public function __construct(Tournament $tournament, User $player)
    {
        $this->tournament = $tournament;
        $this->player = $player;
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function getStatus(): RegistrationStatus
    {
        return $this->status;
    }

    public function setStatus(RegistrationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
