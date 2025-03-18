<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\ItemQuality;
use App\Form\ItemQualityType;
use App\Form\ItemType;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\ItemQualityRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
use App\Service\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends AbstractController
{
    public function __construct(
        private ItemRepository $itemRepo,
        private RarityRepository $rRepo,
        private CollectionsRepository $collectionRepo,
        private EntityManagerInterface $em
    ) {
    }

    #[Route(
        '/collection/{collectionId}/category/{categoryId}/item',
        name: 'app_item_list',
        requirements: ['collectionId' => '\d+', 'categoryId' => '\d+']
    )]
    public function list(
        Request $request,
        PaginatorInterface $paginator,
        CategoryRepository $categoryRepo,
        int $collectionId,
        int $categoryId
    ): Response {
        $collection = $this->collectionRepo->find($collectionId);
        $category = $categoryRepo->find($categoryId);

        $filters = $request->query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $items = $this->itemRepo->findByFilter($filters, $collectionId, $categoryId);

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
            'request' => $request,
            'category' => $category
        ]);
    }


    #[Route('/collection/{collectionId}/item/add', name: 'app_item_add', requirements: ['collectionId' => '\d+'])]
    #[Route(
        '/collection/{collectionId}/item/{itemId}/update',
        name: 'app_item_edit',
        requirements: ['collectionId' => '\d+', 'itemId' => '\d+']
    )]
    public function form(Request $request, int $collectionId, ?int $itemId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        if (!$collection) {
            return $this->json(['result' => false, 'message' => 'Collection introuvable']);
        }

        if ($itemId) {
            $item = $this->itemRepo->find($itemId);
            if (!$item) {
                return $this->json(['result' => false, 'message' => 'Objet introuvable']);
            }
        } else {
            $item = new Item();
        }

        $form = $this->createForm(ItemType::class, $item, ['collection' => $collection])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $item->setCollection($collection);
            $this->em->persist($item);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('item/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'collectionId' => $collectionId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/{id}/delete', name: 'app_item_delete', requirements: ['id' => '\d+'])]
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

    #[Route(
        '/item/{id}',
        name: 'app_item_view',
        requirements: ['id' => '\d+']
    )]
    public function view(int $id): Response
    {
        $item = $this->itemRepo->find($id);

        return $this->render('item/view.html.twig', [
            'item' => $item
        ]);
    }

    #[Route(
        '/item/{itemId}/quality/add',
        name: 'app_item_quality_add',
        requirements: ['itemId' => '\d+']
    )]
    #[Route(
        '/item/quality/{itemQualityId}/update',
        name: 'app_item_quality_edit',
        requirements: ['itemQualityId' => '\d+']
    )]
    public function evaluate(
        Request $request,
        FileManager $fileManager,
        ItemQualityRepository $itemQualityRepository,
        ?int $itemId,
        ?int $itemQualityId
    ): Response {
        $options = [];
        if ($itemQualityId) {
            $itemQuality = $itemQualityRepository->find($itemQualityId);
            $item = $itemQuality->getItem();
        } else {
            $item = $this->itemRepo->find($itemId);
            $itemQuality = new ItemQuality();
        }
        $options['category'] = $item->getCategory();

        $form = $this->createForm(
            ItemQualityType::class,
            $itemQuality,
            $options
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->get('perfect')) {
                $itemQuality->setQuality(10);
            } elseif ($itemQuality->getCriterias()->isEmpty()) {
                $itemQuality->setQuality(null);
            } elseif ($itemQuality->getQuality() < 0) {
                $itemQuality->setQuality(0);
            }

            if (!$itemQuality->getId()) {
                $itemQuality->setItem($item);
            }

            if ($form->has('file')) {
                $file = $form->get('file')->getData();
                if ($file) {
                    $fileManagerEntity = $fileManager->upload('item', $item->getName(), $file);

                    if (!$fileManagerEntity) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue pendant le téléchargement du fichier.'
                        ]);
                    }
                    $this->em->persist($fileManagerEntity);
                    $itemQuality->setFile($fileManagerEntity);
                }
            }

            $itemQuality->setSort($item->getItemQualities()->count() + 1);

            $this->em->persist($itemQuality);
            $this->em->flush();
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('item/quality/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'itemQualityId' => $itemQualityId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/search', name: 'app_item_search')]
    public function search(Request $request, ItemQualityRepository $itemQualityRepository): Response
    {
        if ($search = $request->query->get('search')) {
            $storageId = $request->query->get('storage');

            if ($storageId) {
                $items = $itemQualityRepository->search($search);
            } else {
                $items = $this->itemRepo->findByFilter(['search' => $search])
                    ->select('i.id', 'i.name', 'i.reference, c.name AS collectionName')
                    ->leftJoin('i.collection', 'c')
                    ->getQuery()
                    ->getResult()
                ;
            }

            $render = $this->render('item/search/result.html.twig', [
                'items' => $items,
                'storageId' => $storageId
            ]);

            return $this->json(['result' => true, 'searchResult' => $render->getContent()]);
        }

        return $this->json(['result' => false]);
    }
}
