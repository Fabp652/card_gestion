<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Repository\RarityRepository;
use NumberFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GestionController extends AbstractController
{
    public function __construct(private CardRepository $cardRepo, private RarityRepository $rRepo)
    {
    }

    #[Route('/', name: 'app_gestion')]
    public function index(Request $request): Response
    {
        $orderBy = ['price' => 'DESC'];
        $params = [];
        if ($request->query->get('rarity')) {
            $params['rarity'] = $request->query->get('rarity');
        }

        $numberFormatter = new NumberFormatter('fr-FR', NumberFormatter::CURRENCY);
        $cards = $this->cardRepo->findBy($params, $orderBy);

        $total = count($cards);
        $totalAmount = 0;
        foreach ($cards as $card) {
            $price = $card->getPrice() * $card->getNumber();
            $totalAmount += $price;
        }

        $totalAmount = $numberFormatter->format($totalAmount);

        $rarities = $this->rRepo->findAll();

        return $this->render('gestion/index.html.twig', [
            'cards' => $cards,
            'totalAmount' => $totalAmount,
            'rarities' => $rarities,
            'total' => $total
        ]);
    }
}
