<?php

namespace App\Controller;

use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
class DashboardController extends AbstractController
{
    public function __construct(private CollectionsRepository $collectionRepo, private ItemRepository $itemRepo)
    {
    }

    #[Route(name: 'app_dashboard')]
    public function index(): Response
    {
        $stats = $this->itemRepo->createQueryBuilder('i')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    SUM(i.number) AS totalItem,
                    c.name AS collectionName,
                    c.id AS collectionId,
                    SUM(i.price * i.number) / SUM(i.number) AS average
                '
            )
            ->join('i.collection', 'c')
            ->groupBy('i.collection')
            ->getQuery()
            ->getResult()
        ;

        return $this->render(
            'dashboard/index.html.twig',
            [
                'stats' => $stats
            ]
        );
    }

    #[Route('/stats/collection/{collectionId}', name: 'app_stat_collection')]
    public function collectionStat(int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);

        $statCategories = $this->itemRepo->createQueryBuilder('i')
            ->andWhere('i.collection = :collection')
            ->setParameter('collection', $collection)
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    SUM(i.number) AS totalItem,
                    c.name AS categoryName,
                    r.name AS rarityName
                '
            )
            ->join('i.category', 'c')
            ->leftJoin('i.rarity', 'r')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;

        if (!$collection->getRarities()->isEmpty()) {
            $statRarities = $this->itemRepo->createQueryBuilder('ir')
                ->andWhere('ir.collection = :collection')
                ->setParameter('collection', $collection)
                ->select(
                    '
                        SUM(ir.price * ir.number) AS totalAmount,
                        SUM(i.number) AS totalItem,
                        r.name AS rarityName
                    '
                )
                ->join('i.rarity', 'r')
                ->groupBy('i.rarity')
                ->getQuery()
                ->getResult()
            ;
        }

        $mostExpensive = $this->itemRepo->createQueryBuilder('ime')
            ->andWhere('ime.collection = :collection')
            ->setParameter('collection', $collection)
            ->select('ime.price, ime.number, ime.name, rme.name AS rarityName')
            ->leftJoin('ime.rarity', 'rme')
            ->orderBy('i.price', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;

        $lessExpensive = $this->itemRepo->createQueryBuilder('ile')
            ->andWhere('ile.collection = :collection')
            ->setParameter('collection', $collection)
            ->select('ile.price, ile.number, ile.name, rle.name AS rarityName')
            ->leftJoin('ile.rarity', 'rme')
            ->orderBy('i.price', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;

        return $this->render('dashboard/_detail.html.twig', [
            'statCategories' => $statCategories,
            'statRarities' => $statRarities ?? null,
            'mostExpensive' => $mostExpensive,
            'lessExpensive' => $lessExpensive
        ]);
    }
}
