<?php

namespace App\Repository;

use App\Entity\Round;
use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

class RoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Round::class);
    }

    public function findWithMatches(Tournament $tournament): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.matches', 'm')
            ->leftJoin('m.court', 'c')
            ->leftJoin('m.team1Player1', 'p1')
            ->leftJoin('m.team1Player2', 'p2')
            ->leftJoin('m.team2Player1', 'p3')
            ->leftJoin('m.team2Player2', 'p4')
            ->addSelect('m', 'c', 'p1', 'p2', 'p3', 'p4')
            ->where('r.tournament = :tournament')
            ->setParameter('tournament', $tournament->getId(), UuidType::NAME)
            ->orderBy('r.number', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
