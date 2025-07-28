<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Service\EntityManager;
use App\Service\FileManager;
use App\Service\Validate;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemController extends AbstractController
{
    private const FOLDER = 'item';
    private const ITEM_NOT_FOUND = 'L\'objet est introuvable.';
    private const COLLECTION_NOT_FOUND = 'La collection est introuvable.';

    public function __construct(private ItemRepository $itemRepo)
    {
    }

    #[Route(
        '/collection/{collectionId}/category/{categoryId}/item',
        'app_item_list',
        ['collectionId' => '\d+', 'categoryId' => '\d+']
    )]
    public function list(
        Request $request,
        PaginatorInterface $paginator,
        CategoryRepository $categoryRepo,
        CollectionsRepository $collectionRepo,
        int $collectionId,
        int $categoryId
    ): Response {
        $collection = $collectionRepo->find($collectionId);
        if (!$collection) {
            $this->addFlash('danger', self::COLLECTION_NOT_FOUND);
            return $this->redirectToRoute('app_collection_list');
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

        $query = $request->query;
        $filters = $query->all('filter');
        $filters = array_filter(
            $filters,
            function ($filter) {
                return !empty($filter) || $filter == 0;
            }
        );

        $items = $this->itemRepo->findByFilter($filters, $collectionId, $categoryId);
        $items = $paginator->paginate($items, $query->get('page', 1), $query->get('limit', 10));

        return $this->render('item/index.html.twig', [
            'items' => $items,
            'collection' => $collection,
            'request' => $request,
            'category' => $category ?? null,
            'categories' => $categories ?? null
        ]);
    }


    #[Route('/collection/{collectionId}/item/add', 'app_item_add', ['collectionId' => '\d+'])]
    #[Route('/item/{itemId}/edit', 'app_item_edit', ['itemId' => '\d+'])]
    public function form(
        Request $request,
        CategoryRepository $categoryRepo,
        FileManager $fileManager,
        CollectionsRepository $collectionRepo,
        EntityManager $em,
        Validate $validate,
        ?int $collectionId,
        ?int $itemId
    ): Response {
        if ($collectionId) {
            $collection = $collectionRepo->find($collectionId);
            if (!$collection) {
                return $this->json(['result' => false, 'message' => self::COLLECTION_NOT_FOUND]);
            }
        }

        if ($itemId) {
            $item = $this->itemRepo->find($itemId);
            if (!$item) {
                return $this->json(['result' => false, 'message' => self::ITEM_NOT_FOUND]);
            }
            $collection = $item->getCollection();
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

                    $result = $em->remove($file);
                    if (!$result['result']) {
                        return $this->json($result);
                    }
                    $this->addFlash('success', 'Fichier retiré avec succès.');
                }
            }

            if ($form->has('files')) {
                $nbUploadErr = 0;
                $files = $form->get('files')->getData();
                foreach ($files as $file) {
                    $result = $fileManager->addOrReplace($file, self::FOLDER, $item->getName());
                    if (!$result['result']) {
                        $nbUploadErr++;
                    }

                    $fileManagerEntity = $result['newEntityFile'];
                    $item->addFile($fileManagerEntity);

                    $result = $em->persist($fileManagerEntity);
                    if (!$result['result']) {
                        $nbUploadErr++;
                    }
                }

                if ($nbUploadErr > 0) {
                    if ($nbUploadErr == count($files)) {
                        $this->addFlash('danger', 'Aucun fichier ajouté correctement veuillez réessayer');
                    } else {
                        $this->addFlash(
                            'warning',
                            $nbUploadErr . '/' . count($files) . ' fichiers non ajouté correctement veuillez réessayer.'
                        );
                    }
                } else {
                    $this->addFlash('success', 'Tous les fichiers ajouté avec succès.');
                }
            }

            if (!$item->getId()) {
                $addOrUpdateMessage = 'ajouté';
                $result = $em->persist($item, true);
            } else {
                $addOrUpdateMessage = 'modifé';
                $result = $em->flush();
            }

            $this->addFlash('success', 'Objet ' . $addOrUpdateMessage . ' avec succès.');
            return $this->json($result);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('item/form.html.twig', [
            'form' => $form->createView(),
            'itemId' => $itemId,
            'collectionId' => $collectionId,
            'files' => $itemId ? $item->getFiles() : null
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/item/{id}/delete', 'app_item_delete', ['id' => '\d+'])]
    public function delete(EntityManager $em, int $id): Response
    {
        $item = $this->itemRepo->find($id);
        if ($item) {
            if ($item->getItemQualities()->isEmpty()) {
                $result = $em->remove($item, true);
                $this->addFlash('success', 'Objet supprimé avec succès.');
                return $this->json($result);
            }
            return $this->json([
                'result' => false,
                'message' => 'L\'objet ne peut pas être supprimé si des objets sont possédés.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'L\'objet est déjà supprimé']);
        }
    }

    #[Route('/item/{id}', 'app_item_view', ['id' => '\d+'])]
    public function view(Request $request, int $id): Response
    {
        $item = $this->itemRepo->find($id);
        if (!$item) {
            $this->addFlash('danger', self::ITEM_NOT_FOUND);
            return $this->redirect($request->headers->get('referer'));
        }
        return $this->render('item/view.html.twig', ['item' => $item]);
    }

    #[Route('/item/search', 'app_item_search')]
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

    #[Route('/item/{itemId}/update', 'app_item_update', ['itemId' => '\d+'])]
    public function update(Request $request, EntityManager $em, int $itemId): Response
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
            return $this->json($em->flush());
        }
        return $this->json(['result' => true]);
    }
}
