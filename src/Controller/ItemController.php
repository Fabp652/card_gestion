<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
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
        $categories = $categoryRepo->findWithoutActualCategory($category);

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

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'collection' => $collection,
            'request' => $request,
            'category' => $category,
            'categories' => $categories
        ]);
    }


    #[Route('/collection/{collectionId}/item/add', name: 'app_item_add', requirements: ['collectionId' => '\d+'])]
    #[Route(
        '/collection/{collectionId}/item/{itemId}/edit',
        name: 'app_item_edit',
        requirements: ['collectionId' => '\d+', 'itemId' => '\d+']
    )]
    public function form(
        Request $request,
        CategoryRepository $categoryRepo,
        int $collectionId,
        ?int $itemId
    ): Response {
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
            if ($categoryId = $request->query->get('categoryId')) {
                $category = $categoryRepo->find($categoryId);
                $item->setCategory($category);
            }
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
    public function delete(int $id): Response
    {
        $item = $this->itemRepo->find($id);
        if ($item) {
            $this->em->remove($item);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'objet est déjà supprimé']);
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

    #[Route('/item/search', name: 'app_item_search')]
    public function search(Request $request): Response
    {
        if ($search = $request->query->get('search')) {
            $items = $this->itemRepo->findByFilter(['search' => $search])
                ->select('i.id', 'i.name', 'i.reference, c.name AS collectionName')
                ->leftJoin('i.collection', 'c')
                ->getQuery()
                ->getResult()
            ;

            $render = $this->render('item/search/result.html.twig', [
                'items' => $items
            ]);

            return $this->json(['result' => true, 'searchResult' => $render->getContent()]);
        }

        return $this->json(['result' => false]);
    }

    #[Route(
        '/item/{itemId}/update',
        name: 'app_item_update',
        requirements: ['itemId' => '\d+']
    )]
    public function update(Request $request, int $itemId): Response
    {
        $flush = false;
        $item = $this->itemRepo->find($itemId);

        $datas = $request->request->all();
        foreach ($datas as $dataKey => $dataValue) {
            if ($dataKey == 'number' && $dataValue != $item->getNumber()) {
                $item->setNumber($dataValue);
                $flush = true;
            } elseif ($dataKey == 'price' && $dataValue != $item->getPrice()) {
                $item->setPrice($dataValue);
                $flush = true;
            }
        }

        if ($flush) {
            $this->em->flush();
        }
        return $this->json(['result' => true]);
    }
}
