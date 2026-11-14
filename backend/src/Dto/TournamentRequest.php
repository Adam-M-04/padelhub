<?php

namespace App\Dto;

use App\Enum\TournamentFormat;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class TournamentRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $name = '';

    #[Assert\NotNull]
    public ?TournamentFormat $format = null;

    #[Assert\NotNull]
    #[Assert\GreaterThan('now', message: 'Tournament must start in the future.')]
    public ?\DateTimeImmutable $startsAt = null;

    #[Assert\Range(min: 1, max: 100)]
    public int $pointsPerMatch = 24;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(4)]
    public ?int $maxPlayers = null;

    #[Assert\Count(min: 1, minMessage: 'Select at least one court.')]
    #[Assert\Unique]
    #[Assert\All([new Assert\Type('integer')])]
    #[OA\Property(type: 'array', items: new OA\Items(type: 'integer', example: 1))]
    public array $courtIds = [];
}
