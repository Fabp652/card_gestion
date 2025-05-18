<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $stats = $this->categoryRepo->stats();

        return $this->render('category/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route(
        'collection/{collectionId}/category/{categoryId}/nav',
        name: 'app_category_nav',
        requirements: ['categoryId' => '\d+', 'collectionId' => '\d+']
    )]
    public function nav(int $collectionId, int $categoryId): Response
    {
        $categories = $this->categoryRepo->findBy(['parent' => $categoryId]);

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
        $childs = $category->getChilds();

        $mostExpensives = [];
        foreach ($category->getChilds() as $child) {
            $index = $child->getName() . '_' . $child->getId();
            $mostExpensives[$index] = $this->itemRepo->findMostExpensives(null, $child->getId());
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
    public function form(
        Request $request,
        ?int $categoryId,
        ?int $parentId
    ): Response {
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
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
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
    public function delete(int $categoryId): Response
    {
        $collection = $this->categoryRepo->find($categoryId);
        if ($collection) {
            $this->em->remove($collection);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'La catégorie est déjà supprimée']);
        }
    }

    #[Route('/category/search', name: 'app_category_search')]
    public function search(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $onlyParent = $request->query->get('onlyParent') == 1;
        $parentId = $request->query->get('parentId');

        $categories = $this->categoryRepo->search($search, $parentId, $onlyParent);

        return $this->json(['result' => true, 'searchResults' => $categories]);
    }
}
