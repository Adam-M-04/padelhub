<?php

namespace App\Entity;

use App\Enum\RoundStatus;
use App\Repository\RoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RoundRepository::class)]
#[ORM\Table(name: 'rounds')]
#[ORM\UniqueConstraint(name: 'uniq_rounds_tournament_number', fields: ['tournament', 'number'])]
class Round
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tournament $tournament;

    #[ORM\Column]
    #[Groups(['round:read'])]
    private int $number;

    #[ORM\Column(length: 20, enumType: RoundStatus::class)]
    #[Groups(['round:read'])]
    private RoundStatus $status = RoundStatus::IN_PROGRESS;

    #[ORM\OneToMany(targetEntity: GameMatch::class, mappedBy: 'round', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    #[Groups(['round:read'])]
    private Collection $matches;

    public function __construct(Tournament $tournament, int $number)
    {
        $this->tournament = $tournament;
        $this->number = $number;
        $this->matches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getStatus(): RoundStatus
    {
        return $this->status;
    }

    public function setStatus(RoundStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(GameMatch $match): static
    {
        $this->matches->add($match);

        return $this;
    }
}
