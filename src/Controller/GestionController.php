<?php

namespace App\Controller;

use App\Repository\CardRepository;
use App\Repository\RarityRepository;
use Knp\Component\Pager\PaginatorInterface;
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
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        // $cards = $this->cardRepo->findBy($params, $orderBy);
        $cards = $this->cardRepo->createQueryBuilder('c')
            ->leftJoin('c.rarity', 'r')
        ;

        if ($rarityId = $request->query->get('rarity')) {
            $cards->andWhere('c.rarity = :rarity')
                ->setParameter('rarity', $rarityId)
            ;
        }

        $cards = $paginator->paginate(
            $cards,
            $request->query->get('page', 1),
            10
        );

        $total = $this->cardRepo->createQueryBuilder('card')
            ->select('SUM(card.price * card.number) AS totalAmount, SUM(card.number) AS total')
        ;

        if ($rarityId = $request->query->get('rarity')) {
            $total->andWhere('c.rarity = :rarity')
                ->setParameter('rarity', $rarityId)
            ;
        }

        $rarities = $this->rRepo->findAll();

        return $this->render('gestion/index.html.twig', [
            'cards' => $cards,
            'rarities' => $rarities,
            'total' => $total->getQuery()->getOneOrNullResult()
        ]);
    }
}
