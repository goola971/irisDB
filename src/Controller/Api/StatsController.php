<?php

namespace App\Controller\Api;

use App\Repository\DepartementRepository;
use App\Repository\AnneeRepository;
use App\Repository\LogementRepository;
use App\Repository\EconomieRepository;
use App\Repository\DemographieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    /**
     * Route pour les statistiques de la page d'accueil (Home.tsx)
     */
    #[Route('/api/stats/global', name: 'api_stats_global')]
    public function getGlobalStats(
        DepartementRepository $deptRepo,
        AnneeRepository $anneeRepo,
        LogementRepository $logRepo
    ): JsonResponse {
        return $this->json([
            ['val' => (string)$deptRepo->count([]), 'label' => 'Départements'],
            ['val' => (string)$anneeRepo->count([]), 'label' => 'Années'],
            ['val' => '5', 'label' => 'Thématiques'],
            ['val' => '~' . round($logRepo->count([]) / 1000) . 'k', 'label' => 'Enregistrements'],
        ]);
    }

    /**
     * Route pour les KPIs et graphiques du Module 1
     */
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

        // Récupération des données filtrées via tes Repositories
        $logStats = $logRepo->getKpiTotals($filters);
        $avgChomage = $ecoRepo->getAverageChomage($filters);

        return $this->json([
            'kpis' => [
                'logementsSociaux' => ['value' => '15.8%', 'label' => 'Log. Sociaux'],
                'chomage' => ['value' => round($avgChomage ?? 0, 1) . ' %', 'label' => 'Taux Chômage'],
                'vacance' => ['value' => '9.2%', 'label' => 'Logts Vacants'],
                'loyer' => ['value' => number_format($logStats['loyerMoyen'] ?? 0, 2) . ' €/m²', 'label' => 'Loyer Social'],
                'parcTotal' => ['value' => round(($logStats['totalLogements'] ?? 0) / 1000000, 1) . 'M', 'label' => 'Parc Total'],
                'population' => ['value' => '67.9M', 'label' => 'Population']
            ],
            'map' => $demoRepo->getMapDensity($filters),
            'top5' => $logRepo->getTop5Construction($filters)
        ]);
    }
}