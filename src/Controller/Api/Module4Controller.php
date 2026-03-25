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
            'annee'  => $request->query->get('annee') ? (int)$request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept')
        ];

        // On récupère les stats sociales de l'année sélectionnée
        $socialStats = $logRepo->getSocialStats($filters);
        
        // On récupère l'évolution de la vacance entre 2021 et 2023
        $vacanceEvolBrut = $logRepo->getVacanceEvolution($filters);
        
        // Formatage des données d'évolution pour Recharts [{dept: '...', v21: X, v23: Y}]
        $vacanceEvolFormatte = [];
        $ecartGlobal = 0;
        
        foreach ($vacanceEvolBrut as $row) {
            $dept = $row['dept'];
            if (!isset($vacanceEvolFormatte[$dept])) {
                $vacanceEvolFormatte[$dept] = ['dept' => $dept, 'v21' => 0, 'v23' => 0];
            }
            if ($row['annee'] == 2021) $vacanceEvolFormatte[$dept]['v21'] = round($row['taux'], 1);
            if ($row['annee'] == 2023) $vacanceEvolFormatte[$dept]['v23'] = round($row['taux'], 1);
        }
        
        // On ne garde que les valeurs formatées (sans les clés) et on limite au Top 8
        $vacanceEvolFormatte = array_slice(array_values($vacanceEvolFormatte), 0, 8);

        // Calcul de l'écart global moyen si on a des données
        if (count($vacanceEvolFormatte) > 0) {
            $v21Moyen = array_sum(array_column($vacanceEvolFormatte, 'v21')) / count($vacanceEvolFormatte);
            $v23Moyen = array_sum(array_column($vacanceEvolFormatte, 'v23')) / count($vacanceEvolFormatte);
            $ecartGlobal = $v23Moyen - $v21Moyen;
        }

        $partSociale = (float)($socialStats['partSociale'] ?? 0);

        return $this->json([
            'kpis' => [
                'loyerMoyen' => [
                    'value' => number_format($socialStats['loyerMoyen'] ?? 0, 2, ',', ' ') . ' €/m²'
                ],
                'partSociale' => [
                    'value' => round($partSociale, 1) . ' %',
                    'raw' => $partSociale // Pour le graphique PieChart
                ],
                'ecartVacance' => [
                    'value' => ($ecartGlobal > 0 ? '+' : '') . round($ecartGlobal, 1) . ' pt',
                    'raw' => $ecartGlobal
                ]
            ],
            'vacanceEvol' => $vacanceEvolFormatte
        ]);
    }
}