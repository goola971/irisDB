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

    // =========================================================================
    // HELPER : filtre commun (annee + dept ou region)
    // =========================================================================
    private function applyFilters(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $qb->join('l.id_annee', 'a')
           ->join('l.id_departement', 'd')
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
    // MODULE 1 — État de l'offre
    // =========================================================================

    /**
     * KPIs globaux : total logements sociaux, loyer moyen, total logements, taux vacance, taux social.
     * Utilisé par Module1Controller, Module2Controller, Module4Controller.
     */
    public function getKpiTotals(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                SUM(l.logements_sociaux)  AS totalSociaux,
                SUM(l.logements_total)    AS totalLogements,
                AVG(l.loyer_social)       AS loyerMoyen,
                (SUM(l.logements_vacants)  * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS tauxVacance,
                (SUM(l.logements_sociaux) * 100.0 / NULLIF(SUM(l.logements_total), 0))  AS tauxSociaux
            ');

        $this->applyFilters($qb, $filters);

        $row = $qb->getQuery()->getSingleResult();

        return [
            'totalSociaux'   => (float)  ($row['totalSociaux']   ?? 0),
            'totalLogements' => (float)  ($row['totalLogements'] ?? 0),
            'loyerMoyen'     => (float)  ($row['loyerMoyen']     ?? 0),
            'tauxVacance'    => (float)  ($row['tauxVacance']    ?? 0),
            'tauxSociaux'    => (float)  ($row['tauxSociaux']    ?? 0),
            // population non disponible dans l'entité Logement — sera 0 si non jointé
            'population'     => 0,
        ];
    }

    /**
     * Top 10 taux de logements sociaux — graphique 1a Module 1.
     * Shape retournée : [{ nom_departement, taux_de_logements_sociaux_en }, ...]
     */
    public function getTop10TauxSociaux(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                d.nom_departement AS nom_departement,
                (SUM(l.logements_sociaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS taux_de_logements_sociaux_en
            ');

        $this->applyFilters($qb, $filters);

        $results = $qb->groupBy('d.id')
            ->orderBy('taux_de_logements_sociaux_en', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_departement'               => $r['nom_departement'] ?? 'Inconnu',
            'taux_de_logements_sociaux_en'  => (float) ($r['taux_de_logements_sociaux_en'] ?? 0),
        ], $results);
    }

    /**
     * Bottom 10 taux de logements sociaux — graphique 1b Module 1.
     * Shape retournée : [{ nom_departement, taux_de_logements_sociaux_en }, ...]
     */
    public function getBottom10TauxSociaux(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                d.nom_departement AS nom_departement,
                (SUM(l.logements_sociaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS taux_de_logements_sociaux_en
            ');

        $this->applyFilters($qb, $filters);

        $results = $qb->andWhere('l.logements_sociaux > 0')
            ->groupBy('d.id')
            ->orderBy('taux_de_logements_sociaux_en', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_departement'               => $r['nom_departement'] ?? 'Inconnu',
            'taux_de_logements_sociaux_en'  => (float) ($r['taux_de_logements_sociaux_en'] ?? 0),
        ], $results);
    }

    /**
     * Évolution du parc social 2021-2023 par région, pivotée en 3 colonnes.
     * Graphique Line Chart Module 1.
     * Shape : [{ nom_region, annee_2021, annee_2022, annee_2023 }, ...]
     */
    public function getEvolutionParRegion(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('r.nom_region AS nom_region')
            ->addSelect('SUM(CASE WHEN a.annee = 2021 THEN l.logements_sociaux ELSE 0 END) AS annee_2021')
            ->addSelect('SUM(CASE WHEN a.annee = 2022 THEN l.logements_sociaux ELSE 0 END) AS annee_2022')
            ->addSelect('SUM(CASE WHEN a.annee = 2023 THEN l.logements_sociaux ELSE 0 END) AS annee_2023')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->where('a.annee IN (2021, 2022, 2023)');

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')->setParameter('dept', $filters['dept']);
        } elseif (!empty($filters['region'])) {
            $qb->andWhere('r.id = :regionId')->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('r.id')
            ->orderBy('r.nom_region', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region'  => $r['nom_region'] ?? 'Inconnu',
            'annee_2021'  => (float) ($r['annee_2021'] ?? 0),
            'annee_2022'  => (float) ($r['annee_2022'] ?? 0),
            'annee_2023'  => (float) ($r['annee_2023'] ?? 0),
        ], $results);
    }

    /**
     * Comparatif construction : moyenne estimée 10 ans vs 2023 par région.
     * Grouped Bar Chart Module 1.
     * Shape : [{ nom_region, construction_2023, moyenne_10ans }, ...]
     *
     * Note : la table Logement ne stocke pas directement les mises en service annuelles.
     * On approxime : construction_2023 ≈ 5 % de logements_sociaux 2023 (flux entrant estimé).
     * Remplacer par la vraie colonne le jour où elle existe dans le schéma.
     */
    public function getConstructionComparatif(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('r.nom_region AS nom_region')
            // On simplifie la syntaxe mathématique avec des parenthèses claires pour que Doctrine la comprenne
            ->addSelect('(SUM(CASE WHEN a.annee = 2023 THEN l.logements_sociaux ELSE 0 END) * 0.05) AS construction_2023')
            ->addSelect('(SUM(l.logements_sociaux) * 0.04) AS moyenne_10ans')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r');

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')->setParameter('dept', $filters['dept']);
        } elseif (!empty($filters['region'])) {
            $qb->andWhere('r.id = :regionId')->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('r.id')
            ->orderBy('r.nom_region', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region'        => $r['nom_region']        ?? 'Inconnu',
            'construction_2023' => (float) ($r['construction_2023'] ?? 0),
            'moyenne_10ans'     => (float) ($r['moyenne_10ans']     ?? 0),
        ], $results);
    }

    // =========================================================================
    // MODULE 3 — Rénovation énergétique
    // =========================================================================

    /**
     * KPIs énergie.
     * Note : l'entité Logement n'a pas encore de colonne énergie — retourne des zéros
     * en attendant la migration. Ajouter les champs taux_energivores, age_moyen,
     * logements_demolis dans l'entité Logement puis adapter les SELECT ci-dessous.
     */
    public function getKpiEnergie(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                AVG(l.taux_energivores) AS tauxEnergivoresMoyen,
                AVG(l.age_moyen)        AS ageMoyenParc,
                SUM(l.logements_demolis) AS totalDemolis
            ');

        $this->applyFilters($qb, $filters);

        $row = $qb->getQuery()->getSingleResult();

        return [
            'tauxEnergivoresMoyen' => (float) ($row['tauxEnergivoresMoyen'] ?? 0),
            'ageMoyenParc'         => (float) ($row['ageMoyenParc']         ?? 0),
            'totalDemolis'         => (int)   ($row['totalDemolis']          ?? 0),
        ];
    }

    public function getEnergyvoresParRegion(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('r.nom_region AS nom_region, AVG(0) AS taux_energivores')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->where('a.annee = :annee')
            ->setParameter('annee', $filters['annee'] ?? 2023);

        if (!empty($filters['region'])) {
            $qb->andWhere('r.id = :regionId')->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('r.id')
            ->orderBy('taux_energivores', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region'       => $r['nom_region']       ?? 'Inconnu',
            'taux_energivores' => (float) ($r['taux_energivores'] ?? 0),
        ], $results);
    }

    /**
     * Scatter : âge moyen du parc vs taux énergivores par département.
     * Module 3.
     * Shape : [{ x: age_moyen, y: taux_energivores, dept: nom_departement }, ...]
     *
     * Colonnes cibles : l.age_moyen, l.taux_energivores (à ajouter dans Logement.php)
     */
    public function getScatterAgeEnergie(array $filters): array
    {
        // TODO: remplacer 0 par l.age_moyen et l.taux_energivores quand colonnes dispo
        $qb = $this->createQueryBuilder('l')
            ->select('
                d.nom_departement AS dept,
                AVG(0) AS age_moyen,
                AVG(0) AS taux_energivores
            ');

        $this->applyFilters($qb, $filters);

        $results = $qb->groupBy('d.id')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'x'    => (float) ($r['age_moyen']       ?? 0),
            'y'    => (float) ($r['taux_energivores'] ?? 0),
            'dept' => $r['dept'] ?? 'Inconnu',
        ], $results);
    }

    /**
     * Indicateur de renouvellement : démolitions / parc total par région.
     * Bar Chart Module 3.
     * Shape : [{ nom_region, logements_demolis, parc_total, taux_renouvellement }, ...]
     *
     * Colonne cible : l.logements_demolis (à ajouter dans Logement.php)
     */
    public function getRenouvellementParRegion(array $filters): array
    {
        // TODO: remplacer 0 par l.logements_demolis quand la colonne existe
        $qb = $this->createQueryBuilder('l')
            ->select('
                r.nom_region AS nom_region,
                SUM(0) AS logements_demolis,
                SUM(l.logements_total) AS parc_total,
                (SUM(0) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS taux_renouvellement
            ')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->where('a.annee = :annee')
            ->setParameter('annee', $filters['annee'] ?? 2023);

        if (!empty($filters['region'])) {
            $qb->andWhere('r.id = :regionId')->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('r.id')
            ->orderBy('taux_renouvellement', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region'          => $r['nom_region']          ?? 'Inconnu',
            'logements_demolis'   => (int)   ($r['logements_demolis']   ?? 0),
            'parc_total'          => (int)   ($r['parc_total']          ?? 0),
            'taux_renouvellement' => (float) ($r['taux_renouvellement'] ?? 0),
        ], $results);
    }

    // =========================================================================
    // MODULE 4 — Dynamique & Mobilité
    // =========================================================================

    /**
     * Évolution vacance pivotée : taux_vacance_2021 et taux_vacance_2023 côte à côte.
     * Grouped Bar Module 4.
     * Shape : [{ nom_departement, taux_vacance_2021, taux_vacance_2023 }, ...]
     */
    public function getVacanceEvolutionPivot(array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 8);

        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement AS nom_departement')
            ->addSelect('
                SUM(CASE WHEN a.annee = 2021 THEN l.logements_vacants ELSE 0 END)
                * 100.0
                / NULLIF(SUM(CASE WHEN a.annee = 2021 THEN l.logements_total ELSE 0 END), 0)
                AS taux_vacance_2021
            ')
            ->addSelect('
                SUM(CASE WHEN a.annee = 2023 THEN l.logements_vacants ELSE 0 END)
                * 100.0
                / NULLIF(SUM(CASE WHEN a.annee = 2023 THEN l.logements_total ELSE 0 END), 0)
                AS taux_vacance_2023
            ')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->where('a.annee IN (2021, 2023)');

        if (!empty($filters['dept'])) {
            $qb->andWhere('d.nom_departement = :dept')->setParameter('dept', $filters['dept']);
        } elseif (!empty($filters['region'])) {
            $qb->join('d.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('d.id')
            ->orderBy('taux_vacance_2023', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_departement'   => $r['nom_departement']   ?? 'Inconnu',
            'taux_vacance_2021' => (float) ($r['taux_vacance_2021'] ?? 0),
            'taux_vacance_2023' => (float) ($r['taux_vacance_2023'] ?? 0),
        ], $results);
    }

    /**
     * Fluidité : ratio logements_principaux remis en location / total social.
     * On approxime à partir de logements_principaux / logements_total.
     * Doughnut Module 4.
     */
    public function getFluiditeStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                (SUM(l.logements_principaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS tauxFluidite
            ');

        $this->applyFilters($qb, $filters);

        $row = $qb->getQuery()->getSingleResult();

        return [
            'tauxFluidite' => (float) ($row['tauxFluidite'] ?? 0),
        ];
    }

    /**
     * Stats sociales (loyer moyen + part sociale).
     * Utilisé par Module4Controller (KPIs + doughnut part sociale).
     */
    public function getSocialStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                AVG(l.loyer_social) AS loyerMoyen,
                (SUM(l.logements_sociaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS partSociale
            ');

        $this->applyFilters($qb, $filters);

        $row = $qb->getQuery()->getSingleResult();

        return [
            'loyerMoyen'  => (float) ($row['loyerMoyen']  ?? 0),
            'partSociale' => (float) ($row['partSociale'] ?? 0),
        ];
    }

    /**
     * Ventes à des personnes physiques par région.
     * Bar Chart Module 4.
     * Shape : [{ nom_region, nb_ventes }, ...]
     *
     * Colonne cible : l.ventes_personnes_physiques (à ajouter dans Logement.php)
     * En attendant, on retourne logements_sociaux * 0.002 comme proxy.
     */
    public function getVentesParRegion(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                r.nom_region AS nom_region,
                (SUM(l.logements_sociaux) * 0.002) AS nb_ventes_raw
            ')
            // ... reste de la requête identique ...
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->where('a.annee = :annee')
            ->setParameter('annee', $filters['annee'] ?? 2023);

        $results = $qb->groupBy('r.id')
            ->orderBy('nb_ventes_raw', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region' => $r['nom_region'] ?? 'Inconnu',
            'nb_ventes'  => (int) round($r['nb_ventes_raw'] ?? 0), // Arrondi effectué ici en PHP
        ], $results);
    }

    // =========================================================================
    // MODULE 5 — Analyse territoriale
    // =========================================================================

    /**
     * Typologies comparatives : part individuel dans le parc social vs parc général.
     * Grouped Bar Module 5.
     * Shape : [{ nom_region, pct_individuel_social, pct_individuel_general }, ...]
     */
    public function getTypologiesComparatives(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                r.nom_region AS nom_region,
                (SUM(l.logements_individuels) * 100.0 / NULLIF(SUM(l.logements_sociaux),  0)) AS pct_individuel_social,
                (SUM(l.logements_individuels) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS pct_individuel_general
            ')
            ->join('l.id_annee', 'a')
            ->join('l.id_departement', 'd')
            ->join('d.id_region', 'r')
            ->where('a.annee = :annee')
            ->setParameter('annee', $filters['annee'] ?? 2023);

        if (!empty($filters['region'])) {
            $qb->andWhere('r.id = :regionId')->setParameter('regionId', $filters['region']);
        }

        $results = $qb->groupBy('r.id')
            ->orderBy('r.nom_region', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'nom_region'             => $r['nom_region']             ?? 'Inconnu',
            'pct_individuel_social'  => (float) ($r['pct_individuel_social']  ?? 0),
            'pct_individuel_general' => (float) ($r['pct_individuel_general'] ?? 0),
        ], $results);
    }

    // =========================================================================
    // MÉTHODES HÉRITÉES (conservées pour compatibilité ascendante)
    // =========================================================================

    public function getTop5Construction(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('d.nom_departement AS name, l.logements_total AS val');

        $this->applyFilters($qb, $filters);

        return $qb->orderBy('l.logements_total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function getVacanceStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                (SUM(l.logements_vacants)    * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS moyenneVacance,
                (SUM(l.logements_principaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS moyennePrincipale,
                ((SUM(l.logements_total) - SUM(l.logements_principaux) - SUM(l.logements_vacants))
                 * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS moyenneSecondaire
            ');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getSingleResult() ?? [];
    }

    public function getVacanceDistribution(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                d.nom_departement AS name,
                (l.logements_vacants * 100.0 / NULLIF(l.logements_total, 0)) AS val
            ');

        $this->applyFilters($qb, $filters);

        return $qb->orderBy('val', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function getTypologyStats(array $filters): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('
                AVG(l.logements_individuels) AS partIndividuelle,
                AVG(l.logements_sociaux)     AS partSociale
            ');

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->getSingleResult() ?? [];
    }
    public function getModule1Top10(array $filters): array
    {
        return $this->getTop10TauxSociaux($filters);
    }
    public function getModule1Bottom10(array $filters): array
    {
        return $this->getBottom10TauxSociaux($filters);
    }
    public function getModule1Evolution(array $filters): array
    {
        return $this->getEvolutionParRegion($filters);
    }
    public function getModule1Construction(array $filters): array
    {
        return $this->getConstructionComparatif($filters);
    }
}