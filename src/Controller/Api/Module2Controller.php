<?php

namespace App\Controller\Api;

use App\Repository\EconomieRepository;
use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module2Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module2', name: 'api_dashboard_m2')]
    public function dashboard(
        EconomieRepository $ecoRepo,
        LogementRepository $logRepo,
        Request            $request
    ): JsonResponse {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int) $request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept'),
        ];

        // ── KPIs ──
        $avgChomage  = $ecoRepo->getAverageChomage($filters);
        $avgPauvrete = $ecoRepo->getAveragePauvrete($filters);
        $logStats    = $logRepo->getKpiTotals($filters);

        // ── SCATTER : taux_de_pauvreté_en vs taux_de_logements_sociaux_en ──
        // Retourne pour chaque département :
        // { x: taux_de_pauvreté_en, y: taux_de_logements_sociaux_en, dept: nom_departement }
        $scatter = $ecoRepo->getScatterPauvreteSocial($filters);

        // ── BUBBLE : Top 10 chômage élevé ──
        // Retourne pour chaque département :
        // { x: taux_chomage, y: parc_social_loyer_moyen_en_€_m²_mois,
        //   r: (taille bulle, ex: nb logements sociaux / 5000), dept: nom_departement }
        $bubble = $ecoRepo->getBubbleTop10Chomage($filters);

        // ── TENSION : variation chômage et variation vacance sociale 2021→2023 ──
        // Retourne pour chaque département sélectionné (limiter à 15-20 pour la lisibilité) :
        // { nom_departement, delta_chomage (pts), delta_vacance (pts) }
        $tension = $ecoRepo->getTensionEvolution([
            'region' => $filters['region'],
            'dept'   => $filters['dept'],
        ]);

        return $this->json([
            'kpis' => [
                'chomage' => [
                    'value' => round($avgChomage ?? 0, 1) . ' %',
                    'label' => 'Taux de chômage',
                ],
                'pauvrete' => [
                    'value' => round($avgPauvrete ?? 0, 1) . ' %',
                    'label' => 'Taux de pauvreté',
                ],
                'loyer' => [
                    'value' => number_format($logStats['loyerMoyen'] ?? 0, 2, ',', ' ') . ' €/m²',
                    'label' => 'Loyer moyen social',
                ],
            ],

            // Graphique 1 — Scatter Plot
            // Shape : [{ x, y, dept }, ...]
            'scatter' => $scatter,

            // Graphique 2 — Bubble Chart
            // Shape : [{ x, y, r, dept }, ...]
            'bubble' => $bubble,

            // Graphique 3 — Line Chart double axe
            // Shape : [{ nom_departement, delta_chomage, delta_vacance }, ...]
            'tension' => $tension,
        ]);
    }
}