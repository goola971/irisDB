<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module4Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module4', name: 'api_dashboard_m4')]
    public function index(LogementRepository $logRepo, Request $request): JsonResponse
    {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int) $request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept'),
        ];

        // ── GROUPED BAR : vacance 2021 vs 2023 ──
        // Requête pivot sur deux millésimes pour chaque département (top 8)
        // Retourne : [{ nom_departement, taux_vacance_2021, taux_vacance_2023 }, ...]
        $vacanceEvolBrut = $logRepo->getVacanceEvolutionPivot([
            'region' => $filters['region'],
            'dept'   => $filters['dept'],
            'limit'  => 8,
        ]);

        // ── FLUIDITÉ DU PARC ──
        // Ratio : SUM(parc_social_logements_mis_en_location) / SUM(parc_social_nombre_de_logements) * 100
        $fluiditeStats = $logRepo->getFluiditeStats($filters);

        // ── PART DU PARC SOCIAL ──
        $socialStats = $logRepo->getSocialStats($filters);

        // ── CALCUL ÉCART VACANCE GLOBAL (moyenne 2023 - moyenne 2021) ──
        $ecartVacance = 0;
        if (count($vacanceEvolBrut) > 0) {
            $moy21 = array_sum(array_column($vacanceEvolBrut, 'taux_vacance_2021')) / count($vacanceEvolBrut);
            $moy23 = array_sum(array_column($vacanceEvolBrut, 'taux_vacance_2023')) / count($vacanceEvolBrut);
            $ecartVacance = $moy23 - $moy21;
        }

        $tauxFluidite = (float) ($fluiditeStats['tauxFluidite'] ?? 0);
        $partSociale  = (float) ($socialStats['partSociale']   ?? 0);

        // ── VENTES À DES PERSONNES PHYSIQUES ──
        // Requête : SUM(parc_social_ventes_à_des_personnes_physiques) GROUP BY code_region
        // Retourne : [{ nom_region, nb_ventes }, ...]
        $ventes = $logRepo->getVentesParRegion($filters);

        return $this->json([
            'kpis' => [
                'loyerMoyen' => [
                    'value' => number_format($socialStats['loyerMoyen'] ?? 0, 2, ',', ' ') . ' €/m²',
                ],
                'tauxFluidite' => [
                    'value' => round($tauxFluidite, 1) . ' %',
                    'raw'   => $tauxFluidite,
                ],
                'partSociale' => [
                    'value' => round($partSociale, 1) . ' %',
                    'raw'   => $partSociale,
                ],
                'ecartVacance' => [
                    'value' => ($ecartVacance > 0 ? '+' : '') . round($ecartVacance, 1) . ' pt',
                    'raw'   => $ecartVacance,
                ],
            ],

            // Graphique 1 — Grouped Bar (2 datasets : 2021 et 2023)
            // Shape : [{ nom_departement, taux_vacance_2021, taux_vacance_2023 }, ...]
            'vacanceEvol' => $vacanceEvolBrut,

            // Graphique 2 — Doughnut fluidité + doughnut part sociale (mêmes données KPI)

            // Graphique 3 — Bar ventes
            // Shape : [{ nom_region, nb_ventes }, ...]
            'ventes' => $ventes,
        ]);
    }
}