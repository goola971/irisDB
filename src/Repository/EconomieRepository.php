<?php

namespace App\Repository;

use App\Entity\Economie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Economie>
 */
class EconomieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Economie::class);
    }

    //    /**
    //     * @return Economie[] Returns an array of Economie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Economie
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // 1. Corrélation Pauvreté / Logement Social (Scatter Plot)
    public function getAverageChomage(int $annee): float 
{
    return (float) $this->createQueryBuilder('e')
        ->select('AVG(e.taux_chomage)')
        ->join('e.id_annee', 'a')
        ->where('a.annee = :annee')
        ->setParameter('annee', $annee)
        ->getQuery()
        ->getSingleScalarResult();
}
}
