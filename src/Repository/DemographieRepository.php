<?php

namespace App\Repository;

use App\Entity\Demographie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class DemographieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demographie::class);
    }

    /**
     * Centralisation du filtrage pour la démographie
     */
    private function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $qb->join('demo.id_annee', 'a')
           ->join('demo.id_departement', 'd')
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

    public function getMapDensity(array $filters): array
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('d.nom_departement as name, demo.densite as val');

        $this->applyFilters($qb, $filters);

        return $qb->orderBy('demo.densite', 'DESC')
                  ->setMaxResults(8)
                  ->getQuery()
                  ->getResult();
    }

    public function getVariationPop(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('AVG(demo.variation_population)');

        $this->applyFilters($qb, $filters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }


    public function getDynamiquesDemographiques(array $filters): array
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('
                d.nom_departement as dept, 
                AVG(demo.solde_naturel) as naturel, 
                AVG(demo.solde_migratoire) as migratoire, 
                (AVG(demo.solde_naturel) + AVG(demo.solde_migratoire)) as variation
            ');
            
        $this->applyFilters($qb, $filters);
        
        $qb->groupBy('d.id')
           ->orderBy('variation', 'DESC')
           ->setMaxResults(15);
           
        return $qb->getQuery()->getResult();
    }
}