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
    #[Route('/api/stats/module5', name: 'api_module5')]
    public function index(DemographieRepository $demoRepo, LogementRepository $logRepo, Request $request): JsonResponse
    {
        $search = $request->query->get('search');
        $annee = 2023;

        return $this->json([
            'dynamics' => $demoRepo->getDemographieDynamics($annee, $search),
            'typology' => $logRepo->getTypologieParc($annee, $search)
        ]);
    }
}