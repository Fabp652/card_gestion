<?php

namespace App\Controller;

use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    public function __construct(private CollectionsRepository $collectionRepo, private ItemRepository $itemRepo)
    {
    }

    #[Route(name: 'app_collection')]
    public function index(): Response
    {
        $stats = $this->itemRepo->createQueryBuilder('i')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    SUM(i.number) AS totalItem,
                    c.name AS collectionName,
                    c.id AS collectionId,
                    SUM(i.price * i.number) / SUM(i.number) AS average,
                    cat.name As category
                '
            )
            ->join('i.collection', 'c')
            ->leftJoin('c.category', 'cat')
            ->groupBy('i.collection')
            ->getQuery()
            ->getResult()
        ;

        return $this->render(
            'collection/index.html.twig',
            [
                'stats' => $stats
            ]
        );
    }

    #[Route('/collection/{collectionId}', name: 'app_collection_view')]
    public function view(int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        $categories = $collection->getCategory()->getChilds()->toArray();

        if (!$collection->getRarities()->isEmpty()) {
            $statRarities = $this->itemRepo->createQueryBuilder('ir')
                ->andWhere('ir.collection = :collection')
                ->setParameter('collection', $collection)
                ->select(
                    '
                        SUM(ir.price * ir.number) AS totalAmount,
                        SUM(ir.number) AS totalItem,
                        r.name AS rarityName,
                        SUM(ir.price * ir.number) / SUM(ir.number) AS average
                    '
                )
                ->join('ir.rarity', 'r')
                ->groupBy('ir.rarity')
                ->getQuery()
                ->getResult()
            ;
        }

        $mostExpensives = [];
        foreach ($categories as $category) {
            $index = $category->getName() . '_' . $category->getId();
            $mostExpensives[$index] = $this->itemRepo->createQueryBuilder('ime')
                ->andWhere('ime.collection = :collection')
                ->setParameter('collection', $collection)
                ->andWhere('ime.category = :category')
                ->setParameter('category', $category)
                ->select('ime.price, ime.number, ime.name, rme.name AS rarityName')
                ->leftJoin('ime.rarity', 'rme')
                ->leftJoin('ime.category', 'cme')
                ->orderBy('ime.price', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult()
            ;
        }

        return $this->render('collection/view.html.twig', [
            'statRarities' => $statRarities ?? null,
            'mostExpensives' => $mostExpensives,
            'collection' => $collection
        ]);
    }

    #[Route('/collection/{collectionId}/dropdown', name: 'app_collection_dropdown')]
    public function dropdown(int $collectionId): Response
    {
        $actualCollection = $this->collectionRepo->find($collectionId);

        $collections = $this->collectionRepo->createQueryBuilder('c')
            ->select('c.id, c.name')
            ->where('c.id != :collection')
            ->setParameter('collection', $collectionId)
            ->getQuery()
            ->getResult()
        ;

        return $this->render('collection/partial/dropdown.html.twig', [
            'actualCollection' => $actualCollection,
            'collections' => $collections
        ]);
    }
}
