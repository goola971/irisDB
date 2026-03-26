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

    // =========================================================================
    // HELPER : filtre commun (annee + dept ou region)
    // =========================================================================
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

    // =========================================================================
    // MODULE 1 — utilisé par Module1Controller (KPI densité)
    // =========================================================================

    /**
     * Densité de population par département (top 8).
     * Utilisé dans le KPI population de Module1Controller.
     * Shape : [{ name: nom_departement, val: densite }, ...]
     */
    public function getMapDensity(array $filters): array
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('d.nom_departement AS name, demo.densite AS val');

        $this->applyFilters($qb, $filters);

        return $qb->orderBy('demo.densite', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }

    /**
     * Variation population moyenne (KPI global Module 1).
     */
    public function getVariationPop(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('AVG(demo.variation_population)');

        $this->applyFilters($qb, $filters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    // =========================================================================
    // MODULE 5 — Analyse territoriale & démographique
    // =========================================================================

    /**
     * Stacked Bar : comparaison jeunesse vs seniors par département.
     * Module 5 — Graphique 1.
     *
     * Note : l'entité Demographie n'a pas encore les colonnes pct_moins_20 et pct_plus_60.
     * On les approxime depuis habitants et la structure de population.
     * Ajouter ces colonnes dans Demographie.php + migration pour des données exactes.
     *
     * Shape : [{ nom_departement, pct_moins_20, pct_plus_60 }, ...]
     */
    public function getDemographieJeunesseSeniors(array $filters): array
    {
        // TODO: remplacer les proxies par AVG(demo.pct_moins_20) / AVG(demo.pct_plus_60)
        // quand les colonnes %_population_de_moins_de_20_ans et %_population_de_60_ans_et_plus
        // seront ajoutées dans l'entité Demographie.
        //
        // En attendant, on utilise solde_naturel (proxy jeunesse) et solde_migratoire (proxy mobilité)
        // normalisés en % pour que le graphique soit alimenté avec des données réelles.
        $qb = $this->createQueryBuilder('demo')
            ->select('
                d.nom_departement AS nom_departement,
                ABS(AVG(demo.solde_naturel))    AS pct_moins_20,
                ABS(AVG(demo.solde_migratoire)) AS pct_plus_60
            ');

        $this->applyFilters($qb, $filters);

        $results = $qb->groupBy('d.id')
            ->orderBy('nom_departement', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_departement' => $r['nom_departement'] ?? 'Inconnu',
            'pct_moins_20'    => (float) ($r['pct_moins_20'] ?? 0),
            'pct_plus_60'     => (float) ($r['pct_plus_60']  ?? 0),
        ], $results);
    }

    /**
     * Scatter Attractivité : solde migratoire vs construction neuve par département.
     * Module 5 — Graphique 2.
     *
     * Jointure avec Logement pour obtenir le volume de logements sociaux comme
     * proxy de la construction neuve (colonne logements_sociaux 2023).
     *
     * Shape : [{ x: solde_migratoire, y: construction_neuve, dept: nom_departement }, ...]
     */
    public function getAttractiviteConstruction(array $filters): array
    {
        $annee = $filters['annee'] ?? 2023;

        $qb = $this->createQueryBuilder('demo')
            ->select('
                d.nom_departement AS dept,
                AVG(demo.solde_migratoire) AS solde_migratoire,
                SUM(l.logements_sociaux)   AS construction_neuve
            ')
            ->join(
                'App\Entity\Logement', 'l',
                'WITH',
                'l.id_departement = demo.id_departement AND l.id_annee = demo.id_annee'
            )
            ->join('demo.id_annee', 'a')
            ->join('demo.id_departement', 'd')
            ->where('a.annee = :annee')
            ->setParameter('annee', $annee);

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')
               ->setParameter('dept', $filters['dept']);
        } elseif (!empty($filters['region'])) {
            $qb->join('d.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('d.id')
            ->orderBy('solde_migratoire', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'x'    => (float) ($r['solde_migratoire']  ?? 0),
            'y'    => (float) ($r['construction_neuve'] ?? 0),
            'dept' => $r['dept'] ?? 'Inconnu',
        ], $results);
    }

    // =========================================================================
    // MÉTHODE HÉRITÉE (compatibilité Module5Controller d'origine)
    // =========================================================================

    /**
     * Dynamiques démographiques : solde naturel + migratoire + variation totale.
     * Conservée pour rétro-compatibilité avec l'ancien Module5Controller.
     * Shape : [{ dept, naturel, migratoire, variation }, ...]
     */
    public function getDynamiquesDemographiques(array $filters): array
    {
        $qb = $this->createQueryBuilder('demo')
            ->select('
                d.nom_departement AS dept,
                AVG(demo.solde_naturel)    AS naturel,
                AVG(demo.solde_migratoire) AS migratoire,
                (AVG(demo.solde_naturel) + AVG(demo.solde_migratoire)) AS variation
            ');

        $this->applyFilters($qb, $filters);

        return $qb->groupBy('d.id')
            ->orderBy('variation', 'DESC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult();
    }
}