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

    // =========================================================================
    // HELPER : filtre commun (annee + dept ou region)
    // =========================================================================
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

    // =========================================================================
    // KPIs simples
    // =========================================================================

    /**
     * Taux de chômage moyen sur le périmètre filtré.
     * Utilisé par Module1Controller et Module2Controller.
     */
    public function getAverageChomage(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('e')
            ->select('AVG(e.taux_chomage)');

        $this->applyFilters($qb, $filters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Taux de pauvreté moyen sur le périmètre filtré.
     * Utilisé par Module2Controller.
     */
    public function getAveragePauvrete(array $filters): ?float
    {
        $qb = $this->createQueryBuilder('e')
            ->select('AVG(e.taux_pauvrete)');

        $this->applyFilters($qb, $filters);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    // =========================================================================
    // MODULE 2 — Logement Social & Précarité
    // =========================================================================

    /**
     * Scatter Plot : taux_de_pauvreté vs taux_de_logements_sociaux par département.
     * Module 2 — Graphique 1.
     *
     * Jointure avec Logement pour récupérer le taux social sur la même année.
     * Shape : [{ x: taux_pauvrete, y: taux_social, dept: nom_departement }, ...]
     */
    public function getScatterPauvreteSocial(array $filters): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('
                d.nom_departement AS dept,
                AVG(e.taux_pauvrete) AS x,
                (SUM(l.logements_sociaux) * 100.0 / NULLIF(SUM(l.logements_total), 0)) AS y
            ')
            ->join(
                'App\Entity\Logement', 'l',
                'WITH',
                'l.id_departement = e.id_departement AND l.id_annee = e.id_annee'
            );

        $this->applyFilters($qb, $filters);

        $results = $qb->groupBy('d.id')
            ->orderBy('x', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => [
            'x'    => (float) ($r['x']    ?? 0),
            'y'    => (float) ($r['y']    ?? 0),
            'dept' => $r['dept'] ?? 'Inconnu',
        ], $results);
    }

    /**
     * Bubble Chart : Top 10 départements au chômage le plus élevé.
     * Axe X = taux_chomage, Axe Y = loyer_social moyen, rayon ∝ nb logements sociaux.
     * Module 2 — Graphique 2.
     *
     * Shape : [{ x: taux_chomage, y: loyer_moyen, r: rayon, dept: nom_departement }, ...]
     */
    public function getBubbleTop10Chomage(array $filters): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('
                d.nom_departement AS dept,
                AVG(e.taux_chomage)  AS taux_chomage,
                AVG(l.loyer_social)  AS loyer_moyen,
                SUM(l.logements_sociaux) AS nb_sociaux
            ')
            ->join(
                'App\Entity\Logement', 'l',
                'WITH',
                'l.id_departement = e.id_departement AND l.id_annee = e.id_annee'
            );

        $this->applyFilters($qb, $filters);

        $results = $qb->groupBy('d.id')
            ->orderBy('taux_chomage', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Normaliser le rayon entre 5 et 20 px pour ChartJS
        $maxSociaux = max(array_column($results, 'nb_sociaux') ?: [1]);

        return array_map(function ($r) use ($maxSociaux) {
            $nb = (float) ($r['nb_sociaux'] ?? 0);
            return [
                'x'    => (float) ($r['taux_chomage'] ?? 0),
                'y'    => (float) ($r['loyer_moyen']  ?? 0),
                'r'    => max(5, (int) round(($nb / $maxSociaux) * 20)),
                'dept' => $r['dept'] ?? 'Inconnu',
            ];
        }, $results);
    }

    /**
     * Tension : variation du taux de chômage ET variation du taux de vacance
     * entre 2021 et 2023, par département (top 15 pour la lisibilité).
     * Line Chart double-axe Module 2 — Graphique 3.
     *
     * Jointure avec Logement pour obtenir le delta vacance.
     * Shape : [{ nom_departement, delta_chomage, delta_vacance }, ...]
     */
    public function getTensionEvolution(array $filters): array
    {
        // --- Chômage 2021 ---
        $chomage2021 = $this->createQueryBuilder('e21')
            ->select('d21.id AS dept_id, AVG(e21.taux_chomage) AS tc')
            ->join('e21.id_annee', 'a21')
            ->join('e21.id_departement', 'd21')
            ->where('a21.annee = 2021')
            ->groupBy('d21.id')
            ->getQuery()
            ->getResult();

        // --- Chômage 2023 ---
        $chomage2023 = $this->createQueryBuilder('e23')
            ->select('d23.id AS dept_id, d23.nom_departement AS nom, AVG(e23.taux_chomage) AS tc')
            ->join('e23.id_annee', 'a23')
            ->join('e23.id_departement', 'd23')
            ->where('a23.annee = 2023');

        if (!empty($filters['region'])) {
            $chomage2023->join('d23.id_region', 'r23')
                        ->andWhere('r23.id = :regionId')
                        ->setParameter('regionId', $filters['region']);
        }

        $chomage2023 = $chomage2023->groupBy('d23.id')
            ->getQuery()
            ->getResult();

        // --- Vacance 2021 & 2023 via LogementRepository (DQL cross-entity) ---
        $vacancePivot = $this->getEntityManager()->createQuery('
            SELECT d.id AS dept_id,
                   SUM(CASE WHEN a.annee = 2021 THEN l.logements_vacants ELSE 0 END)
                   * 100.0 / NULLIF(SUM(CASE WHEN a.annee = 2021 THEN l.logements_total ELSE 0 END), 0)
                   AS v21,
                   SUM(CASE WHEN a.annee = 2023 THEN l.logements_vacants ELSE 0 END)
                   * 100.0 / NULLIF(SUM(CASE WHEN a.annee = 2023 THEN l.logements_total ELSE 0 END), 0)
                   AS v23
            FROM App\Entity\Logement l
            JOIN l.id_annee a
            JOIN l.id_departement d
            WHERE a.annee IN (2021, 2023)
            GROUP BY d.id
        ')->getResult();

        // --- Assembler les deltas ---
        $map21Chomage  = array_column($chomage2021,   'tc',  'dept_id');
        $mapVacance    = array_column($vacancePivot, null, 'dept_id');

        $result = [];
        foreach ($chomage2023 as $row) {
            $deptId = $row['dept_id'];
            $tc21   = (float) ($map21Chomage[$deptId] ?? $row['tc']);
            $tc23   = (float) $row['tc'];
            $v21    = (float) ($mapVacance[$deptId]['v21'] ?? 0);
            $v23    = (float) ($mapVacance[$deptId]['v23'] ?? 0);

            $result[] = [
                'nom_departement' => $row['nom'],
                'delta_chomage'   => round($tc23 - $tc21, 2),
                'delta_vacance'   => round($v23  - $v21,  2),
            ];
        }

        // Trier par delta_chomage décroissant et limiter à 15
        usort($result, fn($a, $b) => $b['delta_chomage'] <=> $a['delta_chomage']);

        return array_slice($result, 0, 15);
    }

    // =========================================================================
    // MÉTHODE HÉRITÉE (compatibilité Module2Controller d'origine)
    // =========================================================================

    /**
     * @deprecated Remplacée par getScatterPauvreteSocial() — conservée pour compatibilité.
     */
    public function getCorrelationData(array $filters): array
    {
        return $this->getScatterPauvreteSocial($filters);
    }
}