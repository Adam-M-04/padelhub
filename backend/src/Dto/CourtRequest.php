<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CourtRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    public bool $active = true;
}
