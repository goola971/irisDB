<?php

namespace App\Repository;

use App\Entity\Demographie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demographie>
 */
class DemographieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demographie::class);
    }

    //    /**
    //     * @return Demographie[] Returns an array of Demographie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Demographie
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function getNationalStats(int $annee): array
    {
        return $this->createQueryBuilder('d')
            ->select('AVG(d.variation_population) as avgVar, AVG(d.densite) as avgDensite')
            ->join('d.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getSingleResult();
    }
    public function getMapDensity(int $annee): array
    {
        return $this->createQueryBuilder('d')
            ->select('dept.code_departement as code, d.densite as value')
            ->join('d.id_departement', 'dept')
            ->join('d.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getResult();
    }
}
