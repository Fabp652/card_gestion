<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepo,
        private ItemRepository $itemRepo,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/category', name: 'app_category_list')]
    public function list(): Response
    {
        $stats = $this->categoryRepo->createQueryBuilder('c')
            ->select(
                '
                    SUM(i.price * i.number) AS totalAmount,
                    CASE WHEN COUNT(i.id) > 0 THEN SUM(i.number) ELSE 0 END AS totalItem,
                    c.name AS categoryName,
                    c.id AS categoryId,
                    SUM(i.price * i.number) / SUM(i.number) AS average
                '
            )
            ->leftJoin('c.collections', 'col')
            ->leftJoin('col.items', 'i')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult()
        ;

        return $this->render('category/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/collection/{collectionId}/category', name: 'app_category_nav')]
    public function nav(CollectionsRepository $collectionsRepository, int $collectionId): Response
    {
        $subQuery = $collectionsRepository->createQueryBuilder('co')
            ->select('category.id')
            ->leftJoin('co.category', 'category')
            ->where('co.id = :collectionId')
        ;

        $categories = $this->categoryRepo->createQueryBuilder('c')
            ->where('c.parent = (' . $subQuery->getDQL() . ')')
            ->orderBy('c.name')
            ->setParameter('collectionId', $collectionId)
            ->getQuery()
            ->getResult()
        ;

        return $this->render('category/partial/nav.html.twig', [
            'categories' => $categories,
            'collectionId' => $collectionId
        ]);
    }

    #[Route(
        '/category/{categoryId}',
        name: 'app_category_view',
        requirements: ['categoryId' => '\d+']
    )]
    public function view(int $categoryId): Response
    {
        $category = $this->categoryRepo->find($categoryId);
        $childs = $category->getChilds()->toArray();

        $mostExpensives = [];
        foreach ($childs as $child) {
            $index = $child->getName() . '_' . $child->getId();
            $mostExpensives[$index] = $this->itemRepo->createQueryBuilder('ime')
                ->andWhere('ime.category = :category')
                ->setParameter('category', $child)
                ->select('ime.price, ime.number, ime.name, rme.name AS rarityName')
                ->leftJoin('ime.rarity', 'rme')
                ->orderBy('ime.price', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult()
            ;
        }

        return $this->render('category/view.html.twig', [
            'mostExpensives' => $mostExpensives,
            'category' => $category,
            'childs' => $childs
        ]);
    }

    #[Route('/category/add', name: 'app_category_add')]
    #[Route(
        '/category/{categoryId}/edit',
        name: 'app_category_edit',
        requirements: ['categoryId' => '\d+']
    )]
    #[Route(
        '/category/{parentId}/child/add',
        name: 'app_category_add_child',
        requirements: ['parentId' => '\d+']
    )]
    public function form(Request $request, ?int $categoryId, ?int $parentId): Response
    {
        if ($categoryId) {
            $category = $this->categoryRepo->find($categoryId);
        } else {
            $category = new Category();
        }
        $form = $this->createForm(CategoryType::class, $category)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($parentId) {
                $parent = $this->categoryRepo->find($parentId);
                $category->setParent($parent);
            }
            $this->em->persist($category);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('category/form.html.twig', [
            'form' => $form->createView(),
            'categoryId' => $categoryId,
            'parentId' => $parentId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/category/{categoryId}/delete',
        name: 'app_category_delete',
        requirements: ['categoryId' => '\d+']
    )]
    public function delete(Request $request, int $categoryId): Response
    {
        $referer = $request->headers->get('referer');

        $collection = $this->categoryRepo->find($categoryId);
        if ($collection) {
            $this->em->remove($collection);
            $this->em->flush();
            $this->addFlash('success', "La catégorie est supprimé");
        } else {
            $this->addFlash('warning', "La catégorie est déjà supprimer");
        }

        return $this->redirect($referer);
    }
}
