<?php

namespace App\Repository;

use App\Entity\Economie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class EconomieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Economie::class);
    }

    private function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $qb->join('e.id_annee', 'a')
           ->join('e.id_departement', 'd')
           ->where('a.annee = :annee')
           ->setParameter('annee', $filters['annee'] ?? 2023);

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')
               ->setParameter('dept', $filters['dept']);
        } elseif (!empty($filters['region'])) {
            $qb->join('d.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $filters['region']);
        }

        return $qb;
    }

    public function getAverageChomage(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('e')
            ->select('AVG(e.taux_chomage)');

        $this->applyFilters($qb, $filters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getAveragePauvrete(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('e')->select('AVG(e.taux_pauvrete)');
        
        $this->applyFilters($qb, $filters);
        
        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getCorrelationData(array $filters): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('d.nom_departement as dept, e.taux_pauvrete as x, l.logements_sociaux as y')
            ->join('App\Entity\Logement', 'l', 'WITH', 'l.id_departement = e.id_departement AND l.id_annee = e.id_annee');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }
}