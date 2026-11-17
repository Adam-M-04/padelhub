<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TournamentFilter
{
    #[Assert\Positive]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 50)]
    public int $limit = 10;

    #[Assert\Length(max: 100)]
    public ?string $search = null;

    #[Assert\Choice(choices: ['OPEN', 'IN_PROGRESS', 'FINISHED'])]
    public ?string $status = null;

    #[Assert\Choice(choices: ['AMERICANO', 'MEXICANO'])]
    public ?string $format = null;
}
