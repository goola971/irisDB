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
        LogementRepository $logRepo, 
        EconomieRepository $ecoRepo, 
        DemographieRepository $demoRepo,
        Request $request
    ): JsonResponse {
        $filters = [
            'annee'  => (int) $request->query->get('annee', 2023),
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept')
        ];

        // Récupération des données depuis les différents repositories
        $logStats = $logRepo->getKpiTotals($filters);
        $avgChomage = $ecoRepo->getAverageChomage($filters);
        $avgPop = $demoRepo->getVariationPop($filters);

        return $this->json([
            'kpis' => [
                'logementsSociaux' => [
                    'value' => '15.8%', // Calculez le % réel si vous avez la formule
                    'label' => 'Log. Sociaux'
                ],
                'chomage' => [
                    'value' => round($avgChomage ?? 0, 1) . ' %',
                    'label' => 'Taux Chômage'
                ],
                'vacance' => [
                    'value' => '9.2%', // À lier à une méthode de LogementRepository
                    'label' => 'Logts Vacants'
                ],
                'loyer' => [
                    'value' => number_format($logStats['loyerMoyen'] ?? 0, 2) . ' €/m²',
                    'label' => 'Loyer Social'
                ],
                'parcTotal' => [
                    'value' => $this->formatM($logStats['totalLogements'] ?? 0),
                    'label' => 'Parc Total'
                ],
                'population' => [
                    'value' => '67.9M', // À lier à une méthode de DemographieRepository
                    'label' => 'Population'
                ]
            ],
            'map' => $demoRepo->getMapDensity($filters),
            'top5' => $logRepo->getTop5Construction($filters)
        ]);
    }
    private function formatM($n) {
        return round($n / 1000000, 1) . 'M';
    }

    
}

