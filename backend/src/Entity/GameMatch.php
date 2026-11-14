<?php

namespace App\Entity;

use App\Repository\GameMatchRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
#[ORM\Table(name: 'game_matches')]
class GameMatch
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[Groups(['round:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Round $round;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['round:read'])]
    private Court $court;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $team1Player1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $team1Player2;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $team2Player1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $team2Player2;

    #[ORM\Column(nullable: true)]
    #[Groups(['round:read'])]
    private ?int $team1Score = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['round:read'])]
    private ?int $team2Score = null;

    public function __construct(Round $round, Court $court, array $team1, array $team2)
    {
        $this->id = Uuid::v7();
        $this->round = $round;
        $this->court = $court;
        [$this->team1Player1, $this->team1Player2] = $team1;
        [$this->team2Player1, $this->team2Player2] = $team2;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getCourt(): Court
    {
        return $this->court;
    }

    #[Groups(['round:read'])]
    public function getTeam1(): array
    {
        return [$this->team1Player1, $this->team1Player2];
    }

    #[Groups(['round:read'])]
    public function getTeam2(): array
    {
        return [$this->team2Player1, $this->team2Player2];
    }

    public function getPlayers(): array
    {
        return [...$this->getTeam1(), ...$this->getTeam2()];
    }

    public function getTeam1Score(): ?int
    {
        return $this->team1Score;
    }

    public function getTeam2Score(): ?int
    {
        return $this->team2Score;
    }
}
