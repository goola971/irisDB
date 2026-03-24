<?php

namespace App\Controller\Api;

use App\Repository\DepartementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/api/stats/search-suggestions', name: 'api_search_suggestions')]
    public function suggestions(Request $request, DepartementRepository $deptRepo): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $suggestions = $deptRepo->findSuggestions($query);
        return $this->json($suggestions);
    }
}