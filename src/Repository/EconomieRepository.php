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

    public function getCorrelationData(int $annee, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('d.code_departement as code, d.nom_departement as name, e.taux_pauvrete as x, l.logements_sociaux as y')
            ->join('e.id_departement', 'd')
            ->join('App\Entity\Logement', 'l', 'WITH', 'l.id_departement = d.id')
            ->join('e.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee);

        // Si on a une recherche, on filtre par nom ou par code
        if ($search) {
            $qb->andWhere('d.nom_departement LIKE :search OR d.code_departement = :searchExact')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('searchExact', $search);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTopChomageLoyer(int $annee, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('d.nom_departement as name, e.taux_chomage as chomage, l.loyer_social as loyer, l.logements_total as size')
            ->join('e.id_departement', 'd')
            ->join('App\Entity\Logement', 'l', 'WITH', 'l.id_departement = d.id')
            ->join('e.id_annee', 'a')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee);

        if ($search) {
            $qb->andWhere('d.nom_departement LIKE :search OR d.code_departement = :searchExact')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('searchExact', $search);
        }

        return $qb->orderBy('e.taux_chomage', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
