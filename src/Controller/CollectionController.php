<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Collections;
use App\Form\CollectionType;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    public function __construct(
        private CollectionsRepository $collectionRepo,
        private ItemRepository $itemRepo,
        private EntityManagerInterface $em
    ) {
    }

    #[Route(name: 'app_collection')]
    public function index(): Response
    {
        $stats = $this->collectionRepo->stats();

        return $this->render(
            'collection/index.html.twig',
            [
                'stats' => $stats
            ]
        );
    }

    #[Route(
        '/collection/{collectionId}',
        name: 'app_collection_view',
        requirements: ['collectionId' => '\d+']
    )]
    public function view(int $collectionId): Response
    {
        $collection = $this->collectionRepo->find($collectionId);
        $categories = $collection->getCategory()->getChilds();

        if (!$collection->getRarities()->isEmpty()) {
            $statRarities = $this->itemRepo->statByRarity($collectionId);
        }

        $mostExpensives = [];
        foreach ($categories as $category) {
            $index = $category->getName() . '_' . $category->getId();
            $mostExpensives[$index] = $this->itemRepo->findMostExpensives($collectionId, $category->getId());
        }

        return $this->render('collection/view.html.twig', [
            'statRarities' => $statRarities ?? null,
            'mostExpensives' => $mostExpensives,
            'collection' => $collection
        ]);
    }

    #[Route(
        '/collection/{collectionId}/dropdown',
        name: 'app_collection_dropdown',
        requirements: ['collectionId' => '\d+']
    )]
    public function dropdown(int $collectionId): Response
    {
        $actualCollection = $this->collectionRepo->find($collectionId);

        $collections = $this->collectionRepo->findCollectionsWithoutActual($collectionId);

        return $this->render('collection/partial/dropdown.html.twig', [
            'actualCollection' => $actualCollection,
            'collections' => $collections
        ]);
    }

    #[Route('/collection/add', name: 'app_collection_add')]
    #[Route(
        '/collection/{collectionId}/edit',
        name: 'app_collection_edit',
        requirements: ['collectionId' => '\d+']
    )]
    public function form(Request $request, ?int $collectionId): Response
    {
        if ($collectionId) {
            $collection = $this->collectionRepo->find($collectionId);
        } else {
            $collection = new Collections();
        }
        $form = $this->createForm(CollectionType::class, $collection)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$collection->getCategory() || ( $collection->getId() && $request->get('newCategory'))) {
                $newCategoryName = $request->get('newCategory');
                if (!$newCategoryName) {
                    return $this->json([
                        'result' => false,
                        'message' => 'Une catégorie doit être attribué à la collection.'
                    ]);
                }
                $category = new Category();
                $category->setName($newCategoryName);
                $this->em->persist($category);

                $collection->setCategory($category);
            }
            $this->em->persist($collection);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('collection/form.html.twig', [
            'form' => $form->createView(),
            'collectionId' => $collectionId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/collection/{collectionId}/delete',
        name: 'app_collection_delete',
        requirements: ['collectionId' => '\d+']
    )]
    public function delete(Request $request, int $collectionId): Response
    {
        $referer = $request->headers->get('referer');

        $collection = $this->collectionRepo->find($collectionId);
        if ($collection) {
            $this->em->remove($collection);
            $this->em->flush();
            $this->addFlash('success', "L'objet est supprimé");
        } else {
            $this->addFlash('warning', "L'objet est déjà supprimer");
        }

        return $this->redirect($referer);
    }
}
