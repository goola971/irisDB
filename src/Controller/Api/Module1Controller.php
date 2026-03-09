<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use App\Repository\EconomieRepository;
use App\Repository\DemographieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Module1Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module1', name: 'api_dashboard_m1')]
    public function dashboard(
        LogementRepository $logRepo, 
        EconomieRepository $ecoRepo, 
        DemographieRepository $demoRepo
    ): JsonResponse {
        $annee = 2023;
        
        // 1. Calcul des KPIs
        $logKpis = $logRepo->getKpiTotals($annee);
        $demoKpis = $demoRepo->getNationalStats($annee);
        
        return $this->json([
            'kpis' => [
                'logementsSociaux' => [
                    'value' => round($logKpis['totalSociaux'] / 1000000, 1) . ' Million',
                    'label' => 'Logements Sociaux'
                ],
                'chomage' => [
                    'value' => round($ecoRepo->getAverageChomage($annee), 1) . ' %',
                    'label' => 'Taux de chômage'
                ],
                'population' => [
                    'value' => round($demoKpis['avgVar'], 1) . ' %',
                    'label' => 'Variation population'
                ],
                'logementsTotal' => [
                    'value' => round($logKpis['totalLogements'] / 1000000, 1) . ' Millions',
                    'label' => 'Nombre logements FR'
                ]
            ],
            'map' => $demoRepo->getMapDensity($annee),
            'top5' => $logRepo->getTop5Construction($annee)
        ]);
    }
}