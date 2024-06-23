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
        $filters = $request->query->all('filter');

        $items = $this->itemRepo->createQueryBuilder('i')
            ->leftJoin('i.rarity', 'r')
            ->where('i.collection = :collectionId')
            ->setParameter('collectionId', $collectionId)
        ;

        foreach ($filters as $filterKey => $filterValue) {
            if (!empty($filterValue)) {
                if ($filterKey == 'name' || $filterKey == 'reference') {
                    $items->andWhere('i.' . $filterKey . ' LIKE :' . $filterKey)
                        ->setParameter($filterKey, $filterValue . '%')
                    ;
                } elseif ($filterKey == 'price' || $filterKey == 'quality') {
                    $priceExplode = explode('-', $filterValue);
                    if (count($priceExplode) == 1) {
                        $items->andWhere('i.' . $filterKey . ' = :' . $filterKey)
                            ->setParameter($filterKey, $filterValue)
                        ;
                    } elseif (empty($priceExplode[0])) {
                        $items->andWhere('i.' . $filterKey . ' < :' . $filterKey)
                            ->setParameter($filterKey, $priceExplode[1])
                        ;
                    } else {
                        $items->andWhere('i.price BETWEEN :min AND :max')
                            ->setParameter('min', $priceExplode[0])
                            ->setParameter('max', $priceExplode[1])
                        ;
                    }
                } elseif ($filterKey == 'number') {
                    $comparator = $filterValue == 1 ? '>' : '=';
                    $items->andWhere('i.number ' . $comparator . ' 1');
                } else {
                    if ($filterKey == 'rarity') {
                        $filterValue = (int) $filterValue;
                    }
                    $items->andWhere('i.' . $filterKey . ' = ' . ':' . $filterKey)
                        ->setParameter($filterKey, $filterValue)
                    ;
                }
            }
        }

        $items = $paginator->paginate(
            $items,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        $minAndMaxPrice = $this->itemRepo->getMinAndMaxPrice($collectionId);
        $prices = [];
        if ($minAndMaxPrice['minPrice'] < 1) {
            $prices = [
                '-1' => 'Moins de 1 €',
                '1-5' => 'Plus de 1 €'
            ];
            $range = 5;
            $actual = 5;
        } elseif ($minAndMaxPrice['minPrice'] < 5) {
            $prices = [
                '-5' => 'Moins de 5 €',
                '5-10' => 'Plus de 5 €'
            ];
            $range = 10;
            $actual = 10;
        } else {
            $prices = [
                '-10' => 'Moins de 10 €',
                '10-20' => 'Plus de 10 €'
            ];
            $range = 10;
            $actual = 10;
        }

        while ($actual < $minAndMaxPrice['maxPrice']) {
            $max = $actual + $range;
            $key = $actual . '-' . $max;
            $prices[$key] = 'Plus de ' . $actual . ' €';

            $actual = $max;
        }

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'collection' => $collection,
            'prices' => $prices,
            'request' => $request
        ]);
    }
}
