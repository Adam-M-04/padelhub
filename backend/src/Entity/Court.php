<?php

namespace App\Entity;

use App\Repository\CourtRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CourtRepository::class)]
#[ORM\Table(name: 'courts')]
#[ORM\UniqueConstraint(name: 'uniq_courts_name', fields: ['name'])]
class Court
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['court:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['court:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['court:read'])]
    private bool $active = true;

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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
