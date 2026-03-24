<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module4Controller extends AbstractController
{
    #[Route('/api/stats/module4', name: 'api_module4')]
    public function index(LogementRepository $logRepo, Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        
        // 1. Données pour le graphique groupé (2021 vs 2023)
        $vacanceRaw = $logRepo->getVacanceEvolution($search);
        
        $formattedVacance = [];
        foreach ($vacanceRaw as $row) {
            $nom = $row['nom'];
            if (!isset($formattedVacance[$nom])) {
                $formattedVacance[$nom] = ['nom' => $nom, 'vacants2021' => 0, 'vacants2023' => 0];
            }
            if ($row['annee'] == 2021) $formattedVacance[$nom]['vacants2021'] = (float)$row['vacants'];
            if ($row['annee'] == 2023) $formattedVacance[$nom]['vacants2023'] = (float)$row['vacants'];
        }

        // 2. Données pour le Doughnut et le KPI
        $repartition = $logRepo->getRepartitionEtLoyer(2023, $search);

        return $this->json([
            'vacance' => array_values($formattedVacance), // On remet les clés à zéro pour React
            'partSociale' => round((float)$repartition['part_sociale'], 2),
            'loyerMoyen' => round((float)$repartition['loyer_moyen'], 2)
        ]);
    }
}