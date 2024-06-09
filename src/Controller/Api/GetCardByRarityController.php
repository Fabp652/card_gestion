<?php

namespace App\Controller\Api;

use App\Entity\Rarity;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GetCardByRarityController extends AbstractController
{
    public function __invoke(Request $request, CardRepository $cardRepo, Rarity $rarity)
    {
        $page = (int) $request->query->get('page', 1);
        $order = $request->query->all('order', null);

        return $cardRepo->findCardsByRarity($rarity->getId(), $page, $order);
    }
}
