<?php

namespace App\Entity;

use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournaments')]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['tournament:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['tournament:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20, enumType: TournamentFormat::class)]
    #[Groups(['tournament:read'])]
    private ?TournamentFormat $format = null;

    #[ORM\Column]
    #[Groups(['tournament:read'])]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column]
    #[Groups(['tournament:read'])]
    private int $pointsPerMatch = 24;

    #[ORM\Column]
    #[Groups(['tournament:read'])]
    private ?int $maxPlayers = null;

    #[ORM\Column(length: 20, enumType: TournamentStatus::class)]
    #[Groups(['tournament:read'])]
    private TournamentStatus $status = TournamentStatus::DRAFT;

    #[ORM\ManyToMany(targetEntity: Court::class)]
    #[ORM\JoinTable(name: 'tournament_courts')]
    #[ORM\InverseJoinColumn(name: 'court_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    #[Groups(['tournament:read'])]
    private Collection $courts;

    public function __construct()
    {
        $this->courts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getFormat(): ?TournamentFormat
    {
        return $this->format;
    }

    public function setFormat(TournamentFormat $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt->setTimezone(new \DateTimeZone('UTC'));

        return $this;
    }

    public function getPointsPerMatch(): int
    {
        return $this->pointsPerMatch;
    }

    public function setPointsPerMatch(int $pointsPerMatch): static
    {
        $this->pointsPerMatch = $pointsPerMatch;

        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): static
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function getStatus(): TournamentStatus
    {
        return $this->status;
    }

    public function setStatus(TournamentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCourts(): Collection
    {
        return $this->courts;
    }

    public function setCourts(array $courts): static
    {
        $this->courts->clear();

        foreach ($courts as $court) {
            $this->courts->add($court);
        }

        return $this;
    }
}
