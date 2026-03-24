<?php

namespace App\Controller\Api;

use App\Repository\LogementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Module3Controller extends AbstractController
{
    #[Route('/api/stats/module3', name: 'api_module3')]
    public function index(LogementRepository $logRepo, Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $annee = 2023;

        return $this->json([
            'vacance' => $logRepo->getVacanceParRegion($annee, $search),
            'scatter' => $logRepo->getIndividuelVsSocialCorrelation($annee, $search),
            'kpi' => $logRepo->getKpiPrincipaux($annee, $search)
        ]);
    }
}