<?php

namespace App\Controller\Api;

use App\Repository\EconomieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request; // Ajout pour gérer les paramètres
use Symfony\Component\Routing\Annotation\Route;

class Module2Controller extends AbstractController
{
    #[Route('/api/stats/module2', name: 'api_module2')]
    public function index(EconomieRepository $ecoRepo, Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $annee = 2023;

        return $this->json([
            'scatter' => $ecoRepo->getCorrelationData($annee, $search),
            'bubble' => $ecoRepo->getTopChomageLoyer($annee, $search),
            
            'evolution' => [
                ['label' => '2021', 'chomage' => 7.2, 'vacance' => 3.1],
                ['label' => '2023', 'chomage' => 6.9, 'vacance' => 3.5],
            ]
        ]);
    }
}