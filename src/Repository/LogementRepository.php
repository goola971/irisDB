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
}
