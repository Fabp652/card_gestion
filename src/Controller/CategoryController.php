<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\CollectionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    public function __construct(private CategoryRepository $categoryRepo)
    {
    }

    #[Route('/category', name: 'app_category_list')]
    public function list(): Response
    {
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
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
}
