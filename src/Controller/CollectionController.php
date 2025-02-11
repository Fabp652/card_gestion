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
        $stats = $this->collectionRepo->createQueryBuilder('c')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    CASE WHEN COUNT(i.id) > 0 THEN SUM(i.number) ELSE 0 END AS totalItem,
                    c.name AS collectionName,
                    c.id AS collectionId,
                    SUM(i.price * i.number) / SUM(i.number) AS average,
                    cat.name As category
                '
            )
            ->leftJoin('c.items', 'i')
            ->leftJoin('c.category', 'cat')
            ->groupBy('c.id')
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

    #[Route(
        '/collection/{collectionId}',
        name: 'app_collection_view',
        requirements: ['collectionId' => '\d+', 'itemId' => '\d+']
    )]
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
