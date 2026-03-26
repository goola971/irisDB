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
    public function index(
        DemographieRepository $demoRepo,
        LogementRepository    $logRepo,
        Request               $request
    ): JsonResponse {
        $filters = [
            'annee'  => $request->query->get('annee') ? (int) $request->query->get('annee') : 2023,
            'region' => $request->query->get('region'),
            'dept'   => $request->query->get('dept'),
        ];

        // ── STACKED BAR : JEUNESSE vs SENIORS ──
        // Requête par département :
        //   %_population_de_moins_de_20_ans et %_population_de_60_ans_et_plus
        // Retourne : [{ nom_departement, pct_moins_20, pct_plus_60 }, ...]
        $demographie = $demoRepo->getDemographieJeunesseSeniors($filters);

        // ── SCATTER : ATTRACTIVITÉ ──
        // Requête par département :
        //   x = solde_migratoire, y = parc_social_logements_mis_en_service (construction neuve)
        // Retourne : [{ x, y, dept: nom_departement }, ...]
        $attractivite = $demoRepo->getAttractiviteConstruction($filters);

        // ── GROUPED BAR : TYPOLOGIES ──
        // Compare, par région :
        //   pct_individuel_social  = (parc_social_maisons_individuelles / parc_social_nb_total) * 100
        //   pct_individuel_general = (logements_individuels_total / nb_logements_total) * 100
        // Retourne : [{ nom_region, pct_individuel_social, pct_individuel_general }, ...]
        $typologies = $logRepo->getTypologiesComparatives($filters);

        // ── KPIs ──
        $avgMoins20         = 0;
        $avgPlus60          = 0;
        $avgIndividuelSocial = 0;

        if (count($demographie) > 0) {
            $avgMoins20 = array_sum(array_column($demographie, 'pct_moins_20')) / count($demographie);
            $avgPlus60  = array_sum(array_column($demographie, 'pct_plus_60'))  / count($demographie);
        }
        if (count($typologies) > 0) {
            $avgIndividuelSocial = array_sum(array_column($typologies, 'pct_individuel_social')) / count($typologies);
        }

        return $this->json([
            'kpis' => [
                'pctMoins20' => [
                    'value' => round($avgMoins20, 1) . ' %',
                    'label' => '< 20 ans (moy.)',
                ],
                'pctPlus60' => [
                    'value' => round($avgPlus60, 1) . ' %',
                    'label' => '≥ 60 ans (moy.)',
                ],
                'pctIndividuelSocial' => [
                    'value' => round($avgIndividuelSocial, 1) . ' %',
                    'label' => 'Individuel dans le social',
                ],
            ],

            // Graphique 1 — Stacked Bar Chart
            // Shape : [{ nom_departement, pct_moins_20, pct_plus_60 }, ...]
            'demographie' => $demographie,

            // Graphique 2 — Scatter Plot
            // Shape : [{ x: solde_migratoire, y: construction_neuve, dept }, ...]
            'attractivite' => $attractivite,

            // Graphique 3 — Grouped Bar Chart
            // Shape : [{ nom_region, pct_individuel_social, pct_individuel_general }, ...]
            'typologies' => $typologies,
        ]);
    }
}