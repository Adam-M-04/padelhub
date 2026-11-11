<?php

namespace App\Service;

use App\Dto\CourtRequest;
use App\Entity\Court;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Repository\CourtRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CourtService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourtRepository $courtRepository,
    ) {
    }

    public function getAll(): array
    {
        return $this->courtRepository->findBy([], ['name' => 'ASC']);
    }

    public function get(int $id): Court
    {
        $court = $this->courtRepository->find($id);

        if ($court === null) {
            throw new NotFoundException('Court');
        }

        return $court;
    }

    public function create(CourtRequest $request): Court
    {
        $court = new Court();
        $court->setName($request->name);
        $court->setActive($request->active);

        $this->entityManager->persist($court);
        $this->save();

        return $court;
    }

    public function update(int $id, CourtRequest $request): Court
    {
        $court = $this->get($id);
        $court->setName($request->name);
        $court->setActive($request->active);

        $this->save();

        return $court;
    }

    public function delete(int $id): void
    {
        $court = $this->get($id);
        $this->entityManager->remove($court);

        try {
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            throw new ConflictException('Court is used in tournaments, deactivate it instead', 'COURT_IN_USE');
        }
    }

    private function save(): void
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ConflictException('Court with this name already exists', 'COURT_NAME_TAKEN');
        }
    }
}
