<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/item')]
class ItemController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepo,
        private RarityRepository $rRepo,
        private CollectionsRepository $collectionRepo,
        private EntityManagerInterface $em
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
                    $filterExplode = explode('-', $filterValue);
                    if (count($filterExplode) == 1) {
                        $items->andWhere('i.' . $filterKey . ' = :' . $filterKey)
                            ->setParameter($filterKey, $filterValue)
                        ;
                    } elseif (empty($filterExplode[0])) {
                        $items->andWhere('i.' . $filterKey . ' < :' . $filterKey)
                            ->setParameter($filterKey, $filterExplode[1])
                        ;
                    } else {
                        $items->andWhere('i. ' . $filterKey . ' BETWEEN :min AND :max')
                            ->setParameter('min', $filterExplode[0])
                            ->setParameter('max', $filterExplode[1])
                        ;
                    }
                } elseif ($filterKey == 'number') {
                    $comparator = $filterValue == 1 ? '>' : '=';
                    $items->andWhere('i.number ' . $comparator . ' 1');
                } else {
                    if (is_numeric($filterValue)) {
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


    #[Route('/collection/{collectionId}', name: 'app_item_add', requirements: ['collectionId' => '\d+'])]
    #[Route(
        '/{itemId}/collection/{collectionId}',
        name: 'app_item_edit',
        requirements: ['collectionId' => '\d+', 'itemId' => '\d+']
    )]
    public function form(Request $request, int $collectionId, ?int $itemId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        if (!$collection) {
            return new JsonResponse(['result' => false, 'message' => 'Collection introuvable']);
        }

        if ($itemId) {
            $item = $this->itemRepo->find($itemId);
            if (!$item) {
                return new JsonResponse(['result' => false, 'message' => 'Objet introuvable']);
            }
        } else {
            $item = new Item();
        }

        $form = $this->createForm(ItemType::class, $item, ['collection' => $collection])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $item->setCollection($collection);
            $this->em->persist($item);
            $this->em->flush();

            return new JsonResponse(['result' => true]);
        }

        $render = $this->render('item/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'collectionId' => $collectionId
        ]);

        return new JsonResponse(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/{id}', name: 'app_item_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): Response
    {
        $referer = $request->headers->get('referer');

        $item = $this->itemRepo->find($id);
        if ($item) {
            $this->em->remove($item);
            $this->em->flush();
            $this->addFlash('success', "L'objet est supprimé");
        } else {
            $this->addFlash('warning', "L'objet est déjà supprimer");
        }

        return $this->redirect($referer);
    }
}
