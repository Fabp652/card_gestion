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
        $filters = array_filter($filters);

        $criterias = $this->criteriaRepo->findByFilter($filters);

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
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $field = $error->getOrigin()->getName();
                $messages[$field] = $error->getMessage();
            }
            return $this->json(['result' => false, 'messages' => $messages]);
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
    public function delete(int $criteriaId): Response
    {
        $item = $this->criteriaRepo->find($criteriaId);
        if ($item) {
            $this->em->remove($item);
            $this->em->flush();

            return $this->json(['result' => true]);
        } else {
            return $this->json(['result' => false, 'message' => 'Le critère est déjà supprimé']);
        }
    }
}
