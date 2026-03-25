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
        Request $request
    ): JsonResponse {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int)$request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept')
        ];

        $scatterData = $ecoRepo->getCorrelationData($filters);
        
        $avgChomage = $ecoRepo->getAverageChomage($filters);
        $avgPauvrete = $ecoRepo->getAveragePauvrete($filters);
        $logStats = $logRepo->getKpiTotals($filters);

        return $this->json([
            'kpis' => [
                'chomage' => [
                    'value' => round($avgChomage ?? 0, 1) . ' %',
                    'label' => 'Taux de chômage'
                ],
                'pauvrete' => [
                    'value' => round($avgPauvrete ?? 0, 1) . ' %',
                    'label' => 'Taux de pauvreté'
                ],
                'loyer' => [
                    'value' => number_format($logStats['loyerMoyen'] ?? 0, 2, ',', ' ') . ' €',
                    'label' => 'Loyer moyen'
                ]
            ],
            'scatter' => $scatterData, 
            'distribution' => array_slice($scatterData, 0, 8) 
        ]);
    }
}