<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ScoreRequest
{
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?int $team1Score = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?int $team2Score = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $version = null;
}
