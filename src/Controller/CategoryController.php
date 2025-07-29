<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Service\EntityManager;
use App\Service\Validate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    public function __construct(private CategoryRepository $categoryRepo)
    {
    }

    #[Route(name: 'app_category_list')]
    public function list(): Response
    {
        $stats = $this->categoryRepo->stats();
        return $this->render('category/index.html.twig', ['stats' => $stats]);
    }

    #[Route('/nav', 'app_category_nav')]
    public function nav(Request $request): Response
    {
        return $this->render('category/partial/nav.html.twig', [
            'categories' => $this->categoryRepo->findBy(['parent' => null]),
            'categoryId' => $request->query->get('categoryId')
        ]);
    }

    #[Route('/{categoryId}', 'app_category_view', ['categoryId' => '\d+'])]
    public function view(ItemRepository $itemRepo, int $categoryId): Response
    {
        $category = $this->categoryRepo->find($categoryId);
        if (!$category) {
            $this->addFlash('danger', 'La catégorie est introuvable.');
            return $this->redirectToRoute('app_category_list');
        }
        $childs = $category->getChilds();

        $mostExpensives = [];
        foreach ($category->getChilds() as $child) {
            $index = $child->getName() . '_' . $child->getId();
            $mostExpensives[$index] = $itemRepo->findMostExpensives(null, $child->getId());
        }

        return $this->render('category/view.html.twig', [
            'mostExpensives' => $mostExpensives,
            'category' => $category,
            'childs' => $childs
        ]);
    }

    #[Route('/add', 'app_category_add')]
    #[Route('/{categoryId}/edit', 'app_category_edit', ['categoryId' => '\d+'])]
    #[Route('/{parentId}/child/add', 'app_category_add_child', ['parentId' => '\d+'])]
    public function form(
        Request $request,
        Validate $validate,
        EntityManager $em,
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

            if (!$category->getId()) {
                $addOrUpdateMessage = 'ajoutée';
                $result = $em->persist($category, true);
            } else {
                $addOrUpdateMessage = 'modifiée';
                $result = $em->flush();
            }
            if ($result['result']) {
                $this->addFlash('success', 'Catégorie ' . $addOrUpdateMessage . ' avec succès.');
            }
            return $this->json($result);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return $this->json(['result' => false, 'messages' => $validate->getFormErrors($form)]);
        }

        $render = $this->render('category/form.html.twig', [
            'form' => $form->createView(),
            'categoryId' => $categoryId,
            'parentId' => $parentId
        ]);
        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route('/{categoryId}/delete', 'app_category_delete', ['categoryId' => '\d+'])]
    public function delete(EntityManager $em, int $categoryId): Response
    {
        $category = $this->categoryRepo->find($categoryId);
        if ($category) {
            $result = $em->remove($category, true);
            $this->addFlash('success', 'Catégorie supprimée avec succès.');
            return $this->json($result);
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

        return $this->json([
            'result' => true,
            'searchResults' => $this->categoryRepo->search($search, $parentId, $onlyParent)
        ]);
    }
}
