<?php

namespace App\Repository;

use App\Dto\TournamentFilter;
use App\Entity\Tournament;
use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function findPublished(TournamentFilter $filter): Paginator
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.courts', 'c')
            ->addSelect('c')
            ->where('t.status != :draft')
            ->setParameter('draft', TournamentStatus::DRAFT)
            ->orderBy('t.startsAt', 'ASC')
            ->setFirstResult(($filter->page - 1) * $filter->limit)
            ->setMaxResults($filter->limit);

        if ($filter->search !== null && trim($filter->search) !== '') {
            $query->andWhere('t.name LIKE :search')
                ->setParameter('search', '%' . trim($filter->search) . '%');
        }

        if ($filter->status !== null) {
            $query->andWhere('t.status = :status')
                ->setParameter('status', TournamentStatus::from($filter->status));
        }

        if ($filter->format !== null) {
            $query->andWhere('t.format = :format')
                ->setParameter('format', TournamentFormat::from($filter->format));
        }

        return new Paginator($query);
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
