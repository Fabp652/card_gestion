<?php

namespace App\Controller;

use App\Repository\MarketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MarketController extends AbstractController
{
    public function __construct(private MarketRepository $marketRepo)
    {
    }

    #[Route('/market', name: 'app_market_list')]
    public function list(): Response
    {
        return $this->render('market/index.html.twig', [
            'controller_name' => 'MarketController',
        ]);
    }

    #[Route('/market/search', 'app_market_search')]
    public function search(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $markets = $this->marketRepo->findByFilter(['search' => $search])
            ->select('m.id', 'm.name')
            ->orderBy('m.name')
            ->getQuery()
            ->getResult()
        ;

        return $this->json(['result' => true, 'searchResults' => $markets]);
    }
}
