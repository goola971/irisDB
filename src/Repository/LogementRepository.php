<?php

namespace App\Repository;

use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Logement>
 */
class LogementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logement::class);
    }

    private function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $qb->join('l.id_annee', 'a')
           ->join('l.id_departement', 'd')
           ->where('a.annee = :annee')
           ->setParameter('annee', $filters['annee'] ?? 2023);

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')
               ->setParameter('dept', $filters['dept']);
        } 
        elseif (!empty($filters['region'])) {
            $qb->join('d.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $filters['region']);
        }

        return $qb;
    }

   
    public function getKpiTotals(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                SUM(l.logements_sociaux) as totalSociaux, 
                AVG(l.loyer_social) as loyerMoyen,
                SUM(l.logements_total) as totalLogements
            ');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getSingleResult();
    }

    
    public function getTop5Construction(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement as name, l.logements_total as val');

        $this->applyFilters($qb, $filters);

        return $qb->orderBy('l.logements_total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    
    public function getVacanceParRegion(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('r.nom_region as region, (SUM(l.logements_vacants) / NULLIF(SUM(l.logements_total), 0)) * 100 as taux_vacance');

        // 1. On appelle applyFilters EN PREMIER pour qu'il crée proprement l'alias 'd' (Département)
        $this->applyFilters($qb, $filters);

        // 2. Maintenant que 'd' existe dans la requête, on peut joindre la Région ('r') en toute sécurité
        return $qb->join('d.id_region', 'r')
            ->groupBy('r.id')
            ->orderBy('taux_vacance', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTypologieParc(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement as nom, l.logements_individuels as individuels, l.logements_sociaux as sociaux');

        $this->applyFilters($qb, $filters);

        return $qb->setMaxResults(15)->getQuery()->getResult();
    }


    public function getVacanceStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                (SUM(l.logements_vacants) * 100.0 / NULLIF(SUM(l.logements_total), 0)) as moyenneVacance, 
                (SUM(l.logements_principaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) as moyennePrincipale,
                ((SUM(l.logements_total) - SUM(l.logements_principaux) - SUM(l.logements_vacants)) * 100.0 / NULLIF(SUM(l.logements_total), 0)) as moyenneSecondaire
            ');
        
        $this->applyFilters($qb, $filters);
        
        return $qb->getQuery()->getSingleResult() ?? [];
    }

    public function getVacanceDistribution(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                d.nom_departement as name, 
                (l.logements_vacants * 100.0 / NULLIF(l.logements_total, 0)) as val
            ');
            
        $this->applyFilters($qb, $filters);
        $qb->orderBy('val', 'DESC')->setMaxResults(10);
        
        return $qb->getQuery()->getResult();
    }

    public function getSocialStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                AVG(l.loyer_social) as loyerMoyen, 
                AVG(l.logements_sociaux) as partSociale
            ');
            
        $this->applyFilters($qb, $filters);
        
        return $qb->getQuery()->getSingleResult() ?? [];
    }

    public function getVacanceEvolution(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement as dept, a.annee, AVG(l.logements_vacants) as taux')
            ->where('a.annee IN (2021, 2023)');

        $filtersGeographiques = $filters;
        unset($filtersGeographiques['annee']);
        
        $this->applyFilters($qb, $filtersGeographiques);

        $qb->groupBy('d.id', 'a.annee')
           ->orderBy('taux', 'DESC');
           
        return $qb->getQuery()->getResult();
    }

    public function getTypologyStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                AVG(l.logements_individuels) as partIndividuelle, 
                AVG(l.logements_sociaux) as partSociale
            ');
            
        $this->applyFilters($qb, $filters);
        
        return $qb->getQuery()->getSingleResult() ?? [];
    }
}