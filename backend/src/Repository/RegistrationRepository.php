<?php

namespace App\Repository;

use App\Entity\Registration;
use App\Entity\Tournament;
use App\Enum\RegistrationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    public function findByStatus(Tournament $tournament, RegistrationStatus $status): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.player', 'p')
            ->addSelect('p')
            ->where('r.tournament = :tournament')
            ->andWhere('r.status = :status')
            ->setParameter('tournament', $tournament->getId(), UuidType::NAME)
            ->setParameter('status', $status)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(Tournament $tournament, RegistrationStatus $status): int
    {
        return $this->count(['tournament' => $tournament, 'status' => $status]);
    }
}
