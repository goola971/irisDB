<?php

namespace App\Controller\Api;

use App\Repository\DemographieRepository;
use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module5Controller extends AbstractController
{
    #[Route('/api/stats/dashboard-module5', name: 'api_dashboard_m5')]
    public function index(DemographieRepository $demoRepo, LogementRepository $logRepo, Request $request): JsonResponse
    {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int)$request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept')
        ];

        $dynamiques = $demoRepo->getDynamiquesDemographiques($filters);
        $typologyStats = $logRepo->getTypologyStats($filters);
        $avgVariation = 0;
        $avgMigratoire = 0;
        if (count($dynamiques) > 0) {
            $avgVariation = array_sum(array_column($dynamiques, 'variation')) / count($dynamiques);
            $avgMigratoire = array_sum(array_column($dynamiques, 'migratoire')) / count($dynamiques);
        }

        return $this->json([
            'kpis' => [
                'variationDemo' => [
                    'value' => ($avgVariation > 0 ? '+' : '') . round($avgVariation, 2) . ' %',
                ],
                'soldeMigratoire' => [
                    'value' => ($avgMigratoire > 0 ? '+' : '') . round($avgMigratoire, 2) . ' %',
                ],
                'maisonsIndiv' => [
                    'value' => round((float) ($typologyStats['partIndividuelle'] ?? 0), 1) . ' %',
                ]
            ],
            'dynamiques' => $dynamiques,
            'typology' => [
                [
                    'nom' => 'Moyenne Globale', 
                    'individuels' => round((float) ($typologyStats['partIndividuelle'] ?? 0), 1), 
                    'sociaux' => round((float) ($typologyStats['partSociale'] ?? 0), 1)
                ]
            ]
        ]);
    }
}