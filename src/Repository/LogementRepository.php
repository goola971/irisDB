<?php

namespace App\Repository;

use App\Entity\Logement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Logement>
 */
class LogementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logement::class);
    }

    //    /**
    //     * @return Logement[] Returns an array of Logement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Logement
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function getKpiTotals(int $annee): array
    {
        return $this->createQueryBuilder('l')
            ->select('SUM(l.logements_sociaux) as totalSociaux, SUM(l.logements_total) as totalLogements')
            ->join('l.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getSingleResult();
    }

    public function getTop5Construction(int $annee): array
    {
        // On simule la construction par la somme des logements total pour l'exemple
        return $this->createQueryBuilder('l')
            ->select('d.code_departement as code, SUM(l.logements_total) as value')
            ->join('l.id_departement', 'd')
            ->join('l.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee)
            ->groupBy('d.code_departement')
            ->orderBy('value', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
    public function getVacanceParRegion(int $annee, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('r.nom_region as region, AVG(l.logements_vacants) as taux_vacance')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->join('l.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee)
            ->groupBy('r.id')
            ->orderBy('taux_vacance', 'DESC');

        if ($search) {
            $qb->andWhere('d.nom_departement LIKE :search OR d.code_departement = :searchExact')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchExact', $search);
        }

        return $qb->getQuery()->getResult();
    }

    public function getIndividuelVsSocialCorrelation(int $annee, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement as nom, l.logements_individuels as x, l.logements_sociaux as y')
            ->join('l.id_departement', 'd')
            ->join('l.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee);

        if ($search) {
            $qb->andWhere('d.nom_departement LIKE :search OR d.code_departement = :searchExact')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchExact', $search);
        }

        return $qb->getQuery()->getResult();
    }

    public function getKpiPrincipaux(int $annee, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('SUM(l.logements_principaux) as principaux, SUM(l.logements_total) as total')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee);

        if ($search) {
            $qb->andWhere('d.nom_departement LIKE :search OR d.code_departement = :searchExact')
               ->setParameter('search', '%' . $search . '%')
               ->setParameter('searchExact', $search);
        }

        return $qb->getQuery()->getSingleResult();
    }
}
