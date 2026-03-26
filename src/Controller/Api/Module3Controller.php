<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module3Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module3', name: 'api_dashboard_m3')]
    public function index(LogementRepository $logRepo, Request $request): JsonResponse
    {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int) $request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept'),
        ];

        // ── KPIs ──
        $stats = $logRepo->getKpiEnergie($filters);

        // ── HORIZONTAL BAR : énergivores E/F/G par région ──
        // Requête : GROUP BY code_region — AVG(parc_social_taux_de_logements_énergivores_e_f_g_en)
        // Retourne : [{ nom_region, taux_energivores }, ...]
        $energivoresParRegion = $logRepo->getEnergyvoresParRegion($filters);

        // ── SCATTER : âge moyen du parc vs taux énergivores ──
        // Requête par département : parc_social_âge_moyen_du_parc_en_années vs
        //                           parc_social_taux_de_logements_énergivores_e_f_g_en
        // Retourne : [{ x: age_moyen, y: taux_energivores, dept: nom_departement }, ...]
        $ageEnergie = $logRepo->getScatterAgeEnergie($filters);

        // ── BAR : indicateur de renouvellement ──
        // Requête : SUM(parc_social_logements_démolis) / SUM(parc_social_nombre_de_logements) * 100
        // GROUP BY code_region
        // Retourne : [{ nom_region, logements_demolis, parc_total, taux_renouvellement }, ...]
        $renouvellement = $logRepo->getRenouvellementParRegion($filters);

        return $this->json([
            'kpis' => [
                'tauxEnergivores' => [
                    'value' => round((float) ($stats['tauxEnergivoresMoyen'] ?? 0), 1) . ' %',
                    'label' => 'Logts énergivores (E/F/G)',
                ],
                'ageMoyen' => [
                    'value' => round((float) ($stats['ageMoyenParc'] ?? 0), 0) . ' ans',
                    'label' => 'Âge moyen du parc',
                ],
                'logementsDemolis' => [
                    'value' => number_format($stats['totalDemolis'] ?? 0, 0, ',', ' '),
                    'label' => 'Logements démolis 2023',
                ],
            ],

            // Graphique 1 — Horizontal Bar Chart (trié côté front)
            // Shape : [{ nom_region, taux_energivores }, ...]
            'energivoresParRegion' => $energivoresParRegion,

            // Graphique 2 — Scatter Plot
            // Shape : [{ x, y, dept }, ...]
            'ageEnergie' => $ageEnergie,

            // Graphique 3 — Bar Chart vertical
            // Shape : [{ nom_region, logements_demolis, parc_total, taux_renouvellement }, ...]
            'renouvellement' => $renouvellement,
        ]);
    }
}