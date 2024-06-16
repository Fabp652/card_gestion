<?php

namespace App\Controller;

use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/item')]
class ItemController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepo,
        private RarityRepository $rRepo,
        private CollectionsRepository $collectionRepo
    ) {
    }

    #[Route('/list/collection/{collectionId}', name: 'app_item_list')]
    public function list(Request $request, PaginatorInterface $paginator, int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);

        $items = $this->itemRepo->createQueryBuilder('i')
            ->leftJoin('i.rarity', 'r')
            ->where('i.collection = :collectionId')
            ->setParameter('collectionId', $collectionId)
        ;

        if ($rarityId = $request->query->get('rarity')) {
            $items->andWhere('c.rarity = :rarity')
                ->setParameter('rarity', $rarityId)
            ;
        }

        $items = $paginator->paginate(
            $items,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        // $total = $this->itemRepo->createQueryBuilder('item')
        //     ->select('SUM(item.price * item.number) AS totalAmount, SUM(item.number) AS total')
        //     ->where('collection = :collectionId')
        //     ->setParameter('collectionId', $collectionId)
        // ;

        // if ($rarityId = $request->query->get('rarity')) {
        //     $total->andWhere('c.rarity = :rarity')
        //         ->setParameter('rarity', $rarityId)
        //     ;
        // }

        $rarities = $this->rRepo->findAll();

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'rarities' => $rarities,
            'collection' => $collection
            // 'total' => $total->getQuery()->getOneOrNullResult()
        ]);
    }
}
