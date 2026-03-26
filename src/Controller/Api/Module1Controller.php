<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use App\Repository\EconomieRepository;
use App\Repository\DemographieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module1Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module1', name: 'api_dashboard_m1')]
    public function dashboard(
        LogementRepository    $logRepo,
        EconomieRepository    $ecoRepo,
        DemographieRepository $demoRepo,
        Request               $request
    ): JsonResponse {

        $filters = [
            'annee'  => (int) $request->query->get('annee', 2023),
            'region' => $request->query->get('region') ?: null,
            'dept'   => $request->query->get('dept')   ?: null,
        ];

        // ── KPIs ──
        $logStats   = $logRepo->getKpiTotals($filters);
        $avgChomage = $ecoRepo->getAverageChomage($filters);

        // ── Graphique 1a : Top 10 ──
        $top10 = $logRepo->getTop10TauxSociaux($filters);

        // ── Graphique 1b : Bottom 10 ──
        $bottom10 = $logRepo->getBottom10TauxSociaux($filters);

        // ── Graphique 2 : Évolution 2021-2023 par région ──
        $evolution = $logRepo->getEvolutionParRegion([
            'region' => $filters['region'],
            'dept'   => $filters['dept'],
        ]);

        // ── Graphique 3 : Construction comparatif ──
        $construction = $logRepo->getConstructionComparatif([
            'region' => $filters['region'],
            'dept'   => $filters['dept'],
        ]);

        $tauxSociaux = (float) ($logStats['tauxSociaux']    ?? 0);
        $tauxVacance = (float) ($logStats['tauxVacance']    ?? 0);
        $loyerMoyen  = (float) ($logStats['loyerMoyen']     ?? 0);
        $totalLog    = (float) ($logStats['totalLogements']  ?? 0);

        return $this->json([
            'kpis' => [
                'logementsSociaux' => ['value' => round($tauxSociaux, 1) . ' %',  'label' => 'Log. Sociaux'],
                'chomage'          => ['value' => round((float)($avgChomage ?? 0), 1) . ' %', 'label' => 'Taux Chômage'],
                'vacance'          => ['value' => round($tauxVacance, 1) . ' %',  'label' => 'Logts Vacants'],
                'loyer'            => ['value' => number_format($loyerMoyen, 2, ',', ' ') . ' €/m²', 'label' => 'Loyer Social'],
                'parcTotal'        => ['value' => $this->formatMillions($totalLog), 'label' => 'Parc Total'],
                'population'       => ['value' => '—', 'label' => 'Population'],
            ],
            'top10'        => $top10,
            'bottom10'     => $bottom10,
            'evolution'    => $evolution,
            'construction' => $construction,
        ]);
    }

    private function formatMillions(float $n): string
    {
        if ($n >= 1_000_000) return round($n / 1_000_000, 1) . ' M';
        if ($n >= 1_000)     return round($n / 1_000, 1)     . ' k';
        return (string) round($n, 0);
    }
}