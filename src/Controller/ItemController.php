<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
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
        if (!$collection) {
            return $this->render('error/not_found.html.twig', [
                'message' => 'La collection est introuvable.'
            ]);
        }

        if ($categoryId) {
            $category = $categoryRepo->find($categoryId);
            $categories = $categoryRepo->findWithoutActualCategory($category);
            if ($this->itemRepo->hasItemWithoutCategory($collectionId)) {
                $categories[] = 'Divers';
            }
        } elseif ($collectionCategory = $collection->getCategory()) {
            $categories = $categoryRepo->findBy(['parent' => $collectionCategory]);
        }

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
            'category' => $category ?? null,
            'categories' => $categories ?? null
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
        FileManager $fileManager,
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
            $item->setCollection($collection);
            if ($categoryId = $request->query->get('categoryId')) {
                $category = $categoryRepo->find($categoryId);
                $item->setCategory($category);
            }
        }

        $form = $this->createForm(ItemType::class, $item, ['collection' => $collection])->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->request->has('removeFiles')) {
                $removeFilesStr = $request->request->get('removeFiles');
                $removeFiles = strlen($removeFilesStr) > 0 ? explode(',', $request->request->get('removeFiles')) : [];
                foreach ($removeFiles as $removeFile) {
                    $file = $fileManager->removeFileById((int) $removeFile);
                    if (!$file) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue lors de la suppression du fichier.'
                        ]);
                    }

                    $this->em->remove($file);
                }
            }

            if ($form->has('files')) {
                foreach ($form->get('files')->getData() as $file) {
                    $fileManagerEntity = $fileManager->upload('item', $item->getName(), $file);

                    if (!$fileManagerEntity) {
                        return $this->json([
                            'result' => false,
                            'message' => 'Une erreur est survenue pendant le téléchargement du fichier.'
                        ]);
                    }
                    $this->em->persist($fileManagerEntity);
                    $item->addFile($fileManagerEntity);
                }
            }

            if (!$item->getId()) {
                $this->em->persist($item);
            }
            $this->em->flush();

            return $this->json(['result' => true]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
        }

        $render = $this->render('item/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'collectionId' => $collectionId,
            'files' => $itemId ? $item->getFiles() : null
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/{id}/delete', name: 'app_item_delete', requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $item = $this->itemRepo->find($id);
        if ($item) {
            if ($item->getItemQualities()->isEmpty()) {
                $this->em->remove($item);
                $this->em->flush();

                return $this->json(['result' => true]);
            }
            return $this->json([
                'result' => false,
                'message' => 'L\'objet ne peut pas être supprimé si des objets sont possédés.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'objet est déjà supprimé']);
        }
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
        $search = $request->query->get('search', '');
        $items = $this->itemRepo->findByFilter(['search' => $search]);
        if ($request->query->get('searchBar')) {
            $render = $this->render('item/search/result.html.twig', [
                'items' => $items->orderBy('i.name')->getQuery()->getResult()
            ]);

            return $this->json(['result' => true, 'searchResult' => $render->getContent()]);
        } else {
            $concat = "CASE WHEN i.reference IS NOT NULL THEN CONCAT(i.reference, ' - ', i.name, ";
            $concat .= "' (', c.name, ')') ELSE CONCAT(i.name, ' (', c.name, ')') END AS text";

            $items = $items->select('i.id', $concat)
                ->leftJoin('i.collection', 'c')
                ->orderBy('i.name')
                ->getQuery()
                ->getResult()
            ;
            return $this->json(['result' => true, 'searchResults' => $items]);
        }
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
