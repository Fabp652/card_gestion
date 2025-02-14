<?php

namespace App\Controller;

use App\Entity\Criteria;
use App\Form\CriteriaType;
use App\Repository\CategoryRepository;
use App\Repository\CriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CriteriaController extends AbstractController
{
    public function __construct(
        private CriteriaRepository $criteriaRepo,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/criteria', name: 'app_criteria_list')]
    public function list(Request $request, PaginatorInterface $paginator, CategoryRepository $categoryRepo): Response
    {
        $filters = $request->query->all('filter');

        $criterias = $this->criteriaRepo->createQueryBuilder('c');
        foreach ($filters as $filterKey => $filterValue) {
            if (!empty($filterValue)) {
                if ($filterKey == 'name') {
                    $criterias->andWhere('c.' . $filterKey . ' LIKE :' . $filterKey)
                        ->setParameter($filterKey, $filterValue . '%')
                    ;
                } elseif ($filterKey == 'category') {
                    $criterias->join('c.categories', 'cat')
                        ->join('cat.childs', 'child')
                        ->andWhere('cat.id = :category OR child.id = :category')
                        ->setParameter('category', $filterValue)
                    ;
                } else {
                    if (is_numeric($filterValue)) {
                        $filterValue = (int) $filterValue;
                    }
                    $criterias->andWhere('c.' . $filterKey . ' = ' . ':' . $filterKey)
                        ->setParameter($filterKey, $filterValue)
                    ;
                }
            }
        }

        $criterias = $paginator->paginate(
            $criterias,
            $request->query->get('page', 1),
            $request->query->get('limit', 10)
        );

        $categories = $categoryRepo->findAll();

        return $this->render('criteria/index.html.twig', [
            'criterias' => $criterias,
            'request' => $request,
            'categories' => $categories
        ]);
    }

    #[Route('/criteria/add', name: 'app_criteria_add')]
    #[Route('/criteria/{criteriaId}/edit', name: 'app_criteria_edit')]
    public function form(Request $request, ?int $criteriaId): Response
    {
        if ($criteriaId) {
            $criteria = $this->criteriaRepo->find($criteriaId);
        } else {
            $criteria = new Criteria();
        }
        $form = $this->createForm(CriteriaType::class, $criteria)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categories = $criteria->getCategories()->toArray();
            foreach ($categories as $category) {
                if ($criteria->getCategories()->contains($category->getParent())) {
                    $criteria->removeCategory($category);
                }
            }

            $this->em->persist($criteria);
            $this->em->flush();

            return $this->json(['result' => true]);
        }

        $render = $this->render('criteria/form.html.twig', [
            'form' => $form->createView(),
            'criteriaId' => $criteriaId
        ]);

        return $this->json(['result' => true, 'content' => $render->getContent()]);
    }

    #[Route(
        '/criteria/{criteriaId}/delete',
        name: 'app_criteria_delete',
        requirements: ['criteriaId' => '\d+']
    )]
    public function delete(Request $request, int $criteriaId): Response
    {
        $referer = $request->headers->get('referer');

        $item = $this->criteriaRepo->find($criteriaId);
        if ($item) {
            $this->em->remove($item);
            $this->em->flush();
            $this->addFlash('success', "Le critère est supprimé");
        } else {
            $this->addFlash('warning', "Le critère est déjà supprimé");
        }

        return $this->redirect($referer);
    }
}
