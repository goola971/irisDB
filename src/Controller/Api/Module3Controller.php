<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module3Controller extends AbstractController
{
    // On change 'module3' en 'dashboard-module3' pour la cohérence avec ton Front-end
    #[Route('/api/stats/dashboard-module3', name: 'api_dashboard_m3')]
    public function index(LogementRepository $logRepo, Request $request): JsonResponse
    {
        // Récupération des filtres globaux
        $filters = [
            'annee'  => $request->query->get('annee') ? (int)$request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept')
        ];

        // On récupère les stats de vacance et la distribution
        $stats = $logRepo->getVacanceStats($filters);
        $distribution = $logRepo->getVacanceDistribution($filters);

        return $this->json([
            'kpis' => [
                'tauxVacance' => [
                    'value' => round($stats['moyenneVacance'] ?? 0, 1) . ' %',
                    'label' => 'Taux de vacance'
                ],
                'totalVacants' => [
                    'value' => number_format($stats['totalVacants'] ?? 0, 0, ',', ' '),
                    'label' => 'Logements vacants'
                ],
                'residencesSecondaires' => [
                    'value' => round($stats['moyenneSecondaire'] ?? 0, 1) . ' %',
                    'label' => 'Rés. Secondaires'
                ]
            ],
            'distribution' => $distribution,
            
        ]);
    }
}