<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Collections;
use App\Form\CollectionType;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use App\Repository\RarityRepository;
use App\Service\EntityManager;
use App\Service\FileManager;
use App\Service\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    private const FOLDER = 'collection';

    public function __construct(private CollectionsRepository $collectionRepo)
    {
    }

    #[Route(name: 'app_collection')]
    public function index(): Response
    {
        $stats = $this->collectionRepo->stats();
        return $this->render('collection/index.html.twig', ['stats' => $stats]);
    }

    #[Route('/collection/{collectionId}', 'app_collection_view', ['collectionId' => '\d+'])]
    public function view(ItemRepository $itemRepo, RarityRepository $rarityRepository, int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        $categories = $collection->getCategory() ? $collection->getCategory()->getChilds() : [];

        if ($collection->hasRarities()) {
            $statRarities = $rarityRepository->stats($collectionId);
        }

        $mostExpensives = [];
        foreach ($categories as $category) {
            $index = $category->getName() . '_' . $category->getId();
            $mostExpensives[$index] = $itemRepo->findMostExpensives($collectionId, $category->getId());
        }

        $noCategory = $itemRepo->findMostExpensives($collectionId, null);
        if (!empty($noCategory)) {
            $mostExpensives['divers_0'] = $noCategory;
        }

        return $this->render('collection/view.html.twig', [
            'statRarities' => $statRarities ?? null,
            'mostExpensives' => $mostExpensives,
            'collection' => $collection
        ]);
    }

    #[Route('/collection/{collectionId}/dropdown', 'app_collection_dropdown', ['collectionId' => '\d+'])]
    public function dropdown(int $collectionId): Response
    {
        $actualCollection = $this->collectionRepo->find($collectionId);
        $collections = $this->collectionRepo->findCollectionsWithoutActual($collectionId);
        return $this->render('collection/partial/dropdown.html.twig', [
            'actualCollection' => $actualCollection,
            'collections' => $collections
        ]);
    }

    #[Route('/collection/add', 'app_collection_add')]
    #[Route('/collection/{collectionId}/edit', 'app_collection_edit', ['collectionId' => '\d+'])]
    public function form(
        Request $request,
        FileManager $fileManager,
        Validate $validate,
        EntityManager $em,
        ?int $collectionId
    ): Response {
        $collection = new Collections();
        if ($collectionId) {
            $collection = $this->collectionRepo->find($collectionId);
        }

        $form = $this->createForm(CollectionType::class, $collection, ['post' => $request->isMethod('POST')])
            ->handleRequest($request)
        ;

        if ($form->isSubmitted()) {
            if (!$collection->getCategory() && $categoryData = $form->get('category')->getData()) {
                $category = new Category();
                $category->setName($categoryData);

                $categoryViolations = $validate->validate($category);
                if (!empty($messages)) {
                    $messages = $validate->getFormErrors($form);
                    $messages['category'] = $categoryViolations['name'];

                    return $this->json(['result' => false, 'messages' => $messages]);
                }

                $result = $em->persist($category);
                if (!$result['result']) {
                    return $this->json($result);
                }
                $this->addFlash('success', 'Catégorie ajouté avec succès.');
                $collection->setCategory($category);
            } elseif (!$form->isValid()) {
                $messages = $validate->getFormErrors($form);
                return $this->json(['result' => false, 'messages' => $messages]);
            }

            $file = $form->get('file')->getData();
            if ($file) {
                $result = $fileManager->addOrReplace(
                    $file,
                    self::FOLDER,
                    $collection->getName(),
                    $collection->getFile()
                );

                if (!$result['result']) {
                    return $this->json($result);
                }

                if ($collection->getFile()) {
                    $result = $em->remove($collection->getFile());
                    if (!$result['result']) {
                        return $this->json($result);
                    }
                }

                $fileManagerEntity = $result['newEntityFile'];
                $result = $em->persist($fileManagerEntity);
                if (!$result['result']) {
                    return $this->json($result);
                }
                $this->addFlash('success', 'Fichier ajouté avec succès.');
                $collection->setFile($fileManagerEntity);
            }

            $violations = $validate->validate($collection);
            if (!empty($violations)) {
                return $this->json(['result' => false, 'messages' => $violations]);
            }

            if (!$collection->getId()) {
                $addOrUpdateMessage = 'ajoutée';
                $result = $em->persist($collection, true);
            } else {
                $addOrUpdateMessage = 'modifiée';
                $result = $em->flush();
            }

            if ($result['result']) {
                $this->addFlash('success', 'Collection ' . $addOrUpdateMessage . ' avec succès.');
            }
            return $this->json($result);
        }

        $render = $this->render('collection/form.html.twig', [
            'form' => $form->createView(),
            'collectionId' => $collectionId,
            'file' => $collection->getFile()
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/collection/{collectionId}/delete', 'app_collection_delete', ['collectionId' => '\d+'])]
    public function delete(EntityManager $em, int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        if ($collection) {
            if ($collection->getItems()->isEmpty()) {
                $result = $em->remove($collection, true);
                $this->addFlash('success', 'Collection supprimée avec succès.');
                return $this->json($result);
            }
            return $this->json([
                'result' => false,
                'message' => 'La collection ne peut pas être supprimée si elle contient des objets.'
            ]);
        } else {
            return $this->json(['result' => false, 'message' => 'La collection est déjà supprimée']);
        }
    }

    #[Route('/collection/{collectionId}/complete', 'app_collection_complete', ['collectionId' => '\d+'])]
    public function complete(Request $request, EntityManager $em, int $collectionId): Response
    {
        /** @var Collections $collection */
        $collection = $this->collectionRepo->find($collectionId);
        if (!$collection) {
            return $this->json(['result' => false, 'message' => 'Une erreur est survenue.']);
        }

        $flush = false;
        $complete = $request->request->has('complete') ?
            $request->request->get('complete') == 'true' : null
        ;
        if (is_bool($complete) && $complete != $collection->isComplete()) {
            $collection->setComplete($complete);
            $flush = true;
        }

        if ($flush) {
            $result = $em->flush();
            if (!$result['result']) {
                return $this->json($result);
            }
        }
        return $this->json(['result' => true, 'message' => 'Mis à jour avec succès']);
    }
}
