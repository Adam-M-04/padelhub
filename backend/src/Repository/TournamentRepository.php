<?php

namespace App\Repository;

use App\Entity\Tournament;
use App\Enum\TournamentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.courts', 'c')
            ->addSelect('c')
            ->where('t.status != :draft')
            ->setParameter('draft', TournamentStatus::DRAFT)
            ->orderBy('t.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithCourts(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.courts', 'c')
            ->addSelect('c')
            ->orderBy('t.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
